<?php
/**
 * Submission rate limiting.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Submission;

/**
 * Ogranicza wielokrotne wysyłki formularza.
 */
final class SubmitLimiter {

	private const DEFAULT_HOURLY_LIMIT = 20;

	private int $form_id;

	/** @var array<string, mixed> */
	private array $settings;

	/** @var array<string, mixed> */
	private array $post_data;

	/**
	 * @param int                  $form_id   ID formularza.
	 * @param array<string, mixed> $settings  Ustawienia formularza.
	 * @param array<string, mixed> $post_data Dane POST (do fingerprintu).
	 */
	public function __construct( int $form_id, array $settings, array $post_data = array() ) {
		$this->form_id   = $form_id;
		$this->settings  = $settings;
		$this->post_data = $post_data;
	}

	/**
	 * Sprawdza, czy wysyłka jest dozwolona.
	 */
	public function check(): ?string {
		if ( $this->is_hourly_rate_exceeded() ) {
			return $this->hourly_rate_message();
		}

		if ( ! empty( $this->settings['submitOnce'] ) && $this->has_submitted_cookie() ) {
			return $this->settings['cooldownMessage'] ?? __( 'This form has already been submitted.', 'fast-forms' );
		}

		$cooldown = (int) ( $this->settings['cooldownSeconds'] ?? 0 );
		if ( $cooldown > 0 && $this->has_cooldown_transient() ) {
			return $this->settings['cooldownMessage'] ?? __( 'You can submit this form again shortly.', 'fast-forms' );
		}

		return null;
	}

	/**
	 * Zwiększa licznik prób wysyłki (wywołać po pozytywnym check(), przed walidacją).
	 */
	public function bump_rate_limit(): void {
		$limit = $this->get_hourly_limit();

		if ( $limit <= 0 ) {
			return;
		}

		$key   = $this->hourly_rate_key();
		$count = (int) get_transient( $key );

		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	}

	/**
	 * Zapisuje informację o wysłaniu formularza.
	 */
	public function record(): void {
		if ( ! empty( $this->settings['submitOnce'] ) ) {
			$expire = time() + YEAR_IN_SECONDS;
			setcookie(
				$this->cookie_name(),
				'1',
				$expire,
				COOKIEPATH ? COOKIEPATH : '/',
				COOKIE_DOMAIN,
				is_ssl(),
				true
			);
			$_COOKIE[ $this->cookie_name() ] = '1';
		}

		$cooldown = (int) ( $this->settings['cooldownSeconds'] ?? 0 );
		if ( $cooldown > 0 ) {
			set_transient( $this->transient_key(), 1, $cooldown );
		}
	}

	private function has_submitted_cookie(): bool {
		return isset( $_COOKIE[ $this->cookie_name() ] );
	}

	private function has_cooldown_transient(): bool {
		return (bool) get_transient( $this->transient_key() );
	}

	private function cookie_name(): string {
		if ( ! empty( $this->settings['enableFingerprint'] ) ) {
			return 'ff_submitted_' . $this->form_id . '_' . substr( $this->get_fingerprint(), 0, 12 );
		}

		return 'ff_submitted_' . $this->form_id;
	}

	private function transient_key(): string {
		if ( ! empty( $this->settings['enableFingerprint'] ) ) {
			return 'ff_cooldown_' . $this->form_id . '_' . $this->get_fingerprint();
		}

		$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

		return 'ff_cooldown_' . $this->form_id . '_' . md5( $ip );
	}

	private function get_fingerprint(): string {
		$ip    = (string) ( $_SERVER['REMOTE_ADDR'] ?? '' );
		$agent = (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' );
		$email = $this->find_email_in_post();

		return md5( $ip . '|' . $agent . '|' . $email );
	}

	private function find_email_in_post(): string {
		foreach ( $this->post_data as $value ) {
			if ( is_string( $value ) && is_email( $value ) ) {
				return strtolower( $value );
			}
		}

		return '';
	}

	private function get_hourly_limit(): int {
		/**
		 * Maksymalna liczba prób wysyłki formularza na IP w ciągu godziny.
		 * Wartość 0 wyłącza limit.
		 *
		 * @param int $limit   Domyślny limit.
		 * @param int $form_id ID formularza.
		 */
		$limit = (int) apply_filters( 'ff_max_submissions_per_hour', self::DEFAULT_HOURLY_LIMIT, $this->form_id );

		return max( 0, $limit );
	}

	private function is_hourly_rate_exceeded(): bool {
		$limit = $this->get_hourly_limit();

		if ( $limit <= 0 ) {
			return false;
		}

		$count = (int) get_transient( $this->hourly_rate_key() );

		return $count >= $limit;
	}

	private function hourly_rate_key(): string {
		return 'ff_rate_' . $this->form_id . '_' . $this->get_client_hash();
	}

	private function get_client_hash(): string {
		$ip = (string) ( $_SERVER['REMOTE_ADDR'] ?? 'unknown' );

		return md5( $ip );
	}

	private function hourly_rate_message(): string {
		return $this->settings['cooldownMessage'] ?? __( 'Too many submission attempts. Please try again later.', 'fast-forms' );
	}
}
