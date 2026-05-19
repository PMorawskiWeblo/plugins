<?php
/**
 * Layout entity.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Domain\Layout;

defined( 'ABSPATH' ) || exit;

/**
 * Class Layout
 */
class Layout {

	/**
	 * Layout ID.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Layout title.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * Raw config array.
	 *
	 * @var array
	 */
	private $config;

	/**
	 * Constructor.
	 *
	 * @param int    $id     ID.
	 * @param string $title  Title.
	 * @param array  $config Config.
	 */
	public function __construct( $id, $title, array $config ) {
		$this->id     = $id;
		$this->title  = $title;
		$this->config = $config;
	}

	/**
	 * Get ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get title.
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Get full config for frontend.
	 *
	 * @return array
	 */
	public function to_array() {
		return $this->config;
	}

	/**
	 * Image slots.
	 *
	 * @return array
	 */
	public function get_image_slots() {
		return $this->config['image_slots'] ?? array();
	}

	/**
	 * Text fields.
	 *
	 * @return array
	 */
	public function get_text_fields() {
		return $this->config['text_fields'] ?? array();
	}

	/**
	 * Canvas config.
	 *
	 * @return array
	 */
	public function get_canvas() {
		return $this->config['canvas'] ?? array();
	}

	/**
	 * Limits.
	 *
	 * @return array
	 */
	public function get_limits() {
		return $this->config['limits'] ?? array();
	}
}
