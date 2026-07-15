<?php
/**
 * Global plugin settings storage.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Support;

/**
 * Ustawienia globalne wtyczki (opcje WordPress).
 */
final class GlobalSettings {

	public const OPTION_KEY = 'ff_global_settings';

	public const CAPTCHA_NONE       = 'none';
	public const CAPTCHA_RECAPTCHA   = 'recaptcha';
	public const CAPTCHA_TURNSTILE   = 'turnstile';

	/**
	 * @return array<string, mixed>
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION_KEY, array() );

		return self::sanitize( is_array( $stored ) ? $stored : array() );
	}

	/**
	 * @param array<string, mixed> $settings Ustawienia.
	 */
	public static function save( array $settings ): void {
		update_option( self::OPTION_KEY, self::sanitize( $settings ) );
	}

	/**
	 * @param array<string, mixed> $settings Surowe ustawienia.
	 * @return array<string, mixed>
	 */
	public static function sanitize( array $settings ): array {
		$provider = sanitize_key( (string) ( $settings['captchaProvider'] ?? '' ) );

		if ( ! in_array( $provider, array( self::CAPTCHA_NONE, self::CAPTCHA_RECAPTCHA, self::CAPTCHA_TURNSTILE ), true ) ) {
			$provider = ! empty( $settings['recaptchaEnabled'] ) ? self::CAPTCHA_RECAPTCHA : self::CAPTCHA_NONE;
		}

		$action = sanitize_key( (string) ( $settings['recaptchaAction'] ?? 'fast_forms_submit' ) );

		return array(
			'captchaProvider'       => $provider,
			'recaptchaSiteKey'      => sanitize_text_field( $settings['recaptchaSiteKey'] ?? '' ),
			'recaptchaSecretKey'    => sanitize_text_field( $settings['recaptchaSecretKey'] ?? '' ),
			'recaptchaAction'       => '' !== $action ? $action : 'fast_forms_submit',
			'recaptchaMinScore'     => self::sanitize_score( $settings['recaptchaMinScore'] ?? 0.5 ),
			'turnstileSiteKey'      => sanitize_text_field( $settings['turnstileSiteKey'] ?? '' ),
			'turnstileSecretKey'    => sanitize_text_field( $settings['turnstileSecretKey'] ?? '' ),
			'uploadPath'            => UploadPath::sanitize_pattern( (string) ( $settings['uploadPath'] ?? UploadPath::DEFAULT_PATTERN ) ),
			'deleteDataOnUninstall' => ! empty( $settings['deleteDataOnUninstall'] ),
		);
	}

	/**
	 * Aktywny dostawca captchy.
	 */
	public static function get_captcha_provider(): string {
		$settings = self::get();
		$provider = (string) ( $settings['captchaProvider'] ?? self::CAPTCHA_NONE );

		return in_array( $provider, array( self::CAPTCHA_NONE, self::CAPTCHA_RECAPTCHA, self::CAPTCHA_TURNSTILE ), true )
			? $provider
			: self::CAPTCHA_NONE;
	}

	/**
	 * Czy jakakolwiek captcha jest skonfigurowana i włączona.
	 */
	public static function is_captcha_active(): bool {
		return self::is_recaptcha_active() || self::is_turnstile_active();
	}

	/**
	 * Czy reCAPTCHA jest skonfigurowana i wybrana.
	 */
	public static function is_recaptcha_active(): bool {
		if ( self::CAPTCHA_RECAPTCHA !== self::get_captcha_provider() ) {
			return false;
		}

		$settings = self::get();

		return '' !== $settings['recaptchaSiteKey'] && '' !== $settings['recaptchaSecretKey'];
	}

	/**
	 * Czy Turnstile jest skonfigurowany i wybrany.
	 */
	public static function is_turnstile_active(): bool {
		if ( self::CAPTCHA_TURNSTILE !== self::get_captcha_provider() ) {
			return false;
		}

		$settings = self::get();

		return '' !== $settings['turnstileSiteKey'] && '' !== $settings['turnstileSecretKey'];
	}

	/**
	 * @param mixed $score Minimalny wynik.
	 */
	private static function sanitize_score( $score ): float {
		$value = (float) $score;

		if ( $value < 0 ) {
			return 0.0;
		}

		if ( $value > 1 ) {
			return 1.0;
		}

		return $value;
	}
}
