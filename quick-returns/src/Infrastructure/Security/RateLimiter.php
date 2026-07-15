<?php

namespace Weblo\QuickReturns\Infrastructure\Security;

class RateLimiter {

	private const TRANSIENT_PREFIX = 'qr_rate_';

	public function is_allowed( string $action, string $identifier, int $max_attempts = 5, int $window = 300 ): bool {
		$key     = self::TRANSIENT_PREFIX . md5( $action . '|' . $identifier );
		$attempts = (int) get_transient( $key );

		if ( $attempts >= $max_attempts ) {
			return false;
		}

		set_transient( $key, $attempts + 1, $window );
		return true;
	}

	public function reset( string $action, string $identifier ): void {
		$key = self::TRANSIENT_PREFIX . md5( $action . '|' . $identifier );
		delete_transient( $key );
	}
}
