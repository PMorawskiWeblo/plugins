<?php
/**
 * Consent field HTML sanitization.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Support;

/**
 * Dozwolony podzbiór HTML w treści pola zgody.
 */
final class ConsentHtml {

	/**
	 * Dozwolone tagi i atrybuty.
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function allowed_tags(): array {
		return array(
			'a'      => array(
				'href'   => true,
				'title'  => true,
				'target' => true,
				'rel'    => true,
			),
			'br'     => array(),
			'strong' => array(),
			'b'      => array(),
			'em'     => array(),
			'i'      => array(),
			'u'      => array(),
			'p'      => array(),
			'span'   => array(
				'class' => true,
			),
		);
	}

	/**
	 * Sanityzuje treść zgody (br, linki, strong itd.).
	 */
	public static function sanitize( string $html ): string {
		$html = TextEncoding::repair_unicode_escapes( $html );

		return wp_kses( $html, self::allowed_tags() );
	}
}
