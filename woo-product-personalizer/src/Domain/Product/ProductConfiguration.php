<?php
/**
 * Product personalization configuration.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Domain\Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductConfiguration
 */
class ProductConfiguration {

	/**
	 * Product ID.
	 *
	 * @var int
	 */
	private $product_id;

	/**
	 * Enabled flag.
	 *
	 * @var bool
	 */
	private $enabled;

	/**
	 * Layout ID.
	 *
	 * @var int
	 */
	private $layout_id;

	/**
	 * Validation enabled.
	 *
	 * @var bool
	 */
	private $validation_enabled;

	/**
	 * Acceptance required.
	 *
	 * @var bool
	 */
	private $acceptance_required;

	/**
	 * Acceptance text.
	 *
	 * @var string
	 */
	private $acceptance_text;

	/**
	 * Button label override.
	 *
	 * @var string
	 */
	private $button_label;

	/**
	 * Constructor.
	 *
	 * @param int    $product_id           Product ID.
	 * @param bool   $enabled              Enabled.
	 * @param int    $layout_id            Layout ID.
	 * @param bool   $validation_enabled   Validation.
	 * @param bool   $acceptance_required  Acceptance.
	 * @param string $acceptance_text      Acceptance text.
	 * @param string $button_label         Button label.
	 */
	public function __construct(
		$product_id,
		$enabled,
		$layout_id,
		$validation_enabled,
		$acceptance_required,
		$acceptance_text,
		$button_label
	) {
		$this->product_id          = $product_id;
		$this->enabled             = $enabled;
		$this->layout_id           = $layout_id;
		$this->validation_enabled  = $validation_enabled;
		$this->acceptance_required = $acceptance_required;
		$this->acceptance_text     = $acceptance_text;
		$this->button_label        = $button_label;
	}

	/**
	 * Is personalization enabled and layout assigned.
	 *
	 * @return bool
	 */
	public function is_active() {
		return $this->enabled && $this->layout_id > 0;
	}

	/**
	 * @return int
	 */
	public function get_product_id() {
		return $this->product_id;
	}

	/**
	 * @return bool
	 */
	public function is_enabled() {
		return $this->enabled;
	}

	/**
	 * @return int
	 */
	public function get_layout_id() {
		return $this->layout_id;
	}

	/**
	 * @return bool
	 */
	public function is_validation_enabled() {
		return $this->validation_enabled;
	}

	/**
	 * @return bool
	 */
	public function is_acceptance_required() {
		return $this->acceptance_required;
	}

	/**
	 * @return string
	 */
	public function get_acceptance_text() {
		return $this->acceptance_text;
	}

	/**
	 * @return string
	 */
	public function get_button_label() {
		return $this->button_label;
	}
}
