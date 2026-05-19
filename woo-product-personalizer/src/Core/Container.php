<?php
/**
 * Simple service container.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class Container
 */
class Container {

	/**
	 * Service definitions.
	 *
	 * @var array<string, callable|object>
	 */
	private $services = array();

	/**
	 * Resolved instances.
	 *
	 * @var array<string, object>
	 */
	private $resolved = array();

	/**
	 * Register a service factory or instance.
	 *
	 * @param string          $id       Service id.
	 * @param callable|object $factory  Factory or instance.
	 * @return void
	 */
	public function set( $id, $factory ) {
		$this->services[ $id ] = $factory;
	}

	/**
	 * Get a service.
	 *
	 * @param string $id Service id.
	 * @return mixed
	 */
	public function get( $id ) {
		if ( isset( $this->resolved[ $id ] ) ) {
			return $this->resolved[ $id ];
		}

		if ( ! isset( $this->services[ $id ] ) ) {
			throw new \InvalidArgumentException( sprintf( 'Service "%s" is not registered.', $id ) );
		}

		$factory = $this->services[ $id ];

		if ( is_object( $factory ) && ! is_callable( $factory ) ) {
			$this->resolved[ $id ] = $factory;
			return $factory;
		}

		$instance              = call_user_func( $factory, $this );
		$this->resolved[ $id ] = $instance;

		return $instance;
	}
}
