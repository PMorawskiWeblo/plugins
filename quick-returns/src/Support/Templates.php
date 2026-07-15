<?php

namespace Weblo\QuickReturns\Support;

class Templates {

	public static function render( string $name, array $args = [] ): void {
		$path = QUICK_RETURNS_PATH . 'templates/' . $name . '.php';
		if ( ! file_exists( $path ) ) {
			return;
		}
		if ( ! empty( $args ) ) {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			extract( $args, EXTR_SKIP );
		}
		include $path;
	}

	public static function get( string $name, array $args = [] ): string {
		ob_start();
		self::render( $name, $args );
		return (string) ob_get_clean();
	}
}
