<?php

/**
 * Frontend personalizer orchestration.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Frontend;

use WooProductPersonalizer\Core\Logger;
use WooProductPersonalizer\Domain\Product\ProductConfiguration;
use WooProductPersonalizer\Helpers\LayoutAssetResolver;
use WooProductPersonalizer\Helpers\UploadMimeTypes;
use WooProductPersonalizer\Helpers\UploadSession;
use WooProductPersonalizer\Infrastructure\Repository\LayoutRepository;
use WooProductPersonalizer\Infrastructure\Repository\ProductSettingsRepository;
use WooProductPersonalizer\Infrastructure\Repository\SettingsRepository;
use WooProductPersonalizer\Infrastructure\Uploads\UploadsManager;

defined('ABSPATH') || exit;

/**
 * Class PersonalizerController
 */
class PersonalizerController
{

	/**
	 * Settings.
	 *
	 * @var SettingsRepository
	 */
	private $settings;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Uploads.
	 *
	 * @var UploadsManager
	 */
	private $uploads;

	/**
	 * Products.
	 *
	 * @var ProductSettingsRepository
	 */
	private $products;

	/**
	 * Layouts.
	 *
	 * @var LayoutRepository
	 */
	private $layouts;

	/**
	 * Whether assets were enqueued.
	 *
	 * @var bool
	 */
	private $assets_enqueued = false;

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $settings Settings.
	 * @param Logger             $logger   Logger.
	 * @param UploadsManager     $uploads  Uploads.
	 */
	public function __construct(SettingsRepository $settings, Logger $logger, UploadsManager $uploads)
	{
		$this->settings = $settings;
		$this->logger   = $logger;
		$this->uploads  = $uploads;
		$this->products = new ProductSettingsRepository();
		$this->layouts  = new LayoutRepository();

		add_action('wp_ajax_wpp_upload_temp', array($this, 'ajax_upload_temp'));
		add_action('wp_ajax_nopriv_wpp_upload_temp', array($this, 'ajax_upload_temp'));
		add_action('wp_ajax_wpp_client_log', array($this, 'ajax_client_log'));
	}

	/**
	 * Render personalizer for current product.
	 *
	 * @param int|null $product_id Product ID.
	 * @return string
	 */
	public function render($product_id = null)
	{
		$product_id = $product_id ? absint($product_id) : get_the_ID();
		if (! $product_id || ! is_product()) {
			return '';
		}

		$config = $this->products->get($product_id);
		if (! $config->is_active()) {
			return '';
		}

		$layout = $this->layouts->find($config->get_layout_id());
		if (! $layout) {
			$this->logger->warning('Layout not found for product.', array('product_id' => $product_id));
			return '';
		}

		$this->enqueue_assets($product_id, $config, $layout);

		ob_start();
		$mode            = $this->settings->get_frontend_mode();
		$button_label    = $config->get_button_label() ?: $this->settings->get('default_button_label');
		$accept_text     = $config->get_acceptance_text() ?: $this->settings->get('default_accept_text');
		$validation      = $config->is_validation_enabled();
		$accept_required = $config->is_acceptance_required();
		$debug_enabled   = $this->settings->is_debug_enabled();
		$personalizer_nonce = wp_create_nonce('wpp_personalizer');

		include WPP_PLUGIN_PATH . 'templates/frontend/personalizer.php';
		return (string) ob_get_clean();
	}

	/**
	 * Render trigger button only (for modal mode).
	 *
	 * @return string
	 */
	public function render_button( $product_id = null )
	{
		$product_id = $product_id ? absint( $product_id ) : get_the_ID();
		if ( ! $product_id || ! is_product() ) {
			return '';
		}

		$config = $this->products->get( $product_id );

		if (! $config->is_active()) {
			return '';
		}

		$layout = $this->layouts->find($config->get_layout_id());
		if (! $layout) {
			$this->logger->warning('Layout not found for product button.', array('product_id' => $product_id));
			return '';
		}

		$label = $config->get_button_label() ?: $this->settings->get('default_button_label');

		if ('modal' === $this->settings->get_frontend_mode()) {
			$modal_html = $this->render($product_id);
			if ('' === $modal_html) {
				return '';
			}

			$button_html = sprintf(
				'<p class="wpp-open-personalizer-wrap"><button type="button" class="button alt wpp-open-personalizer" data-product-id="%1$d">%2$s</button></p>',
				(int) $product_id,
				esc_html($label)
			);

			return $modal_html . $button_html;
		}

		return $this->render($product_id);
	}

	/**
	 * Enqueue frontend assets with localized data.
	 *
	 * @param int                  $product_id Product ID.
	 * @param ProductConfiguration $config     Config.
	 * @param \WooProductPersonalizer\Domain\Layout\Layout $layout Layout.
	 * @return void
	 */
	private function enqueue_assets($product_id, ProductConfiguration $config, $layout)
	{
		if ($this->assets_enqueued) {
			return;
		}

		UploadSession::ensure_session();
		UploadSession::get_token();

		$debug_enabled = $this->settings->is_debug_enabled();

		wp_enqueue_style('wpp-frontend');
		wp_enqueue_script('konva');

		$personalizer_deps = array('jquery', 'konva', 'wpp-google-fonts', 'wpp-mask-border');
		if ($debug_enabled) {
			wp_enqueue_script('wpp-debug');
			$personalizer_deps[] = 'wpp-debug';
		}

		wp_deregister_script('wpp-personalizer');
		wp_register_script(
			'wpp-personalizer',
			WPP_PLUGIN_URL . 'assets/js/personalizer.js',
			$personalizer_deps,
			WPP_VERSION,
			true
		);
		wp_enqueue_script('wpp-personalizer');

		wp_localize_script(
			'wpp-personalizer',
			'wppData',
			array(
				'ajaxUrl'            => admin_url('admin-ajax.php'),
				'nonce'              => wp_create_nonce('wpp_personalizer'),
				'uploadNonce'        => wp_create_nonce('wpp_upload'),
				'debugEnabled'       => $debug_enabled,
				'debugLogNonce'      => wp_create_nonce('wpp_debug_log'),
				'productId'          => $product_id,
				'layout'             => LayoutAssetResolver::resolve($layout->to_array()),
				'layoutId'           => $layout->get_id(),
				'mode'               => $this->settings->get_frontend_mode(),
				'validationEnabled'      => $config->is_validation_enabled(),
				'buttonLabel'            => $config->get_button_label() ?: $this->settings->get('default_button_label'),
				'buttonLabelCompleted'   => $this->settings->get('default_button_label_completed'),
				'acceptanceRequired'     => $config->is_acceptance_required(),
				'maxUploadMb'        => (int) $this->settings->get('max_upload_mb', 10),
				'previewExportScale' => (int) $this->settings->get('preview_export_scale', 2),
				'allowedMimeTypes'   => (array) $this->settings->get('allowed_mime_types', array()),
				'i18n'               => array(
					'uploadImage'    => __('Upload image', 'woo-product-personalizer'),
					'chooseFile'     => __('Choose file', 'woo-product-personalizer'),
					'moveLeft'       => __('Move left', 'woo-product-personalizer'),
					'moveRight'      => __('Move right', 'woo-product-personalizer'),
					'moveUp'         => __('Move up', 'woo-product-personalizer'),
					'moveDown'       => __('Move down', 'woo-product-personalizer'),
					'zoomIn'         => __('Zoom in', 'woo-product-personalizer'),
					'zoomOut'        => __('Zoom out', 'woo-product-personalizer'),
					'rotate'         => __('Rotate', 'woo-product-personalizer'),
					'flipH'          => __('Flip horizontal', 'woo-product-personalizer'),
					'flipV'          => __('Flip vertical', 'woo-product-personalizer'),
					'autofit'        => __('Auto-fit', 'woo-product-personalizer'),
					'reset'          => __('Reset', 'woo-product-personalizer'),
					'requiredField'  => __('This field is required.', 'woo-product-personalizer'),
					'invalidFile'    => __('Invalid file type or size.', 'woo-product-personalizer'),
					'removeImage'    => __('Remove', 'woo-product-personalizer'),
					'acceptRequired' => __('Please accept the preview before adding to cart.', 'woo-product-personalizer'),
					'personalized'   => _x('Personalized', 'completed personalize button', 'woo-product-personalizer'),
					'close'          => __('Close', 'woo-product-personalizer'),
					'save'           => __('Save personalization', 'woo-product-personalizer'),
					'selectFont'     => __('Font', 'woo-product-personalizer'),
				),
			)
		);

		$this->assets_enqueued = true;
	}

	/**
	 * AJAX: upload temp image.
	 *
	 * @return void
	 */
	public function ajax_upload_temp()
	{
		check_ajax_referer('wpp_upload', 'nonce');

		$product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
		$slot_id    = isset($_POST['slot_id']) ? sanitize_key(wp_unslash($_POST['slot_id'])) : '';

		$config = $this->products->get($product_id);
		if (! $config->is_active()) {
			wp_send_json_error(array('message' => __('Personalization is not available for this product.', 'woo-product-personalizer')));
		}

		if (empty($_FILES['file'])) {
			wp_send_json_error(array('message' => __('No file uploaded.', 'woo-product-personalizer')));
		}

		$max_mb  = (int) $this->settings->get('max_upload_mb', 10);
		$allowed = (array) $this->settings->get('allowed_mime_types', array());
		$file    = $_FILES['file'];

		if (empty($allowed)) {
			wp_send_json_error(array('message' => __('Uploads are disabled.', 'woo-product-personalizer')));
		}

		if ($file['size'] > $max_mb * 1024 * 1024) {
			wp_send_json_error(array('message' => __('File too large.', 'woo-product-personalizer')));
		}

		$token = UploadSession::get_token();
		if ('' === $token) {
			wp_send_json_error(array('message' => __('Upload session is not available.', 'woo-product-personalizer')));
		}

		$result = $this->uploads->store_temp_upload($file, $token, $allowed);

		if (is_wp_error($result)) {
			$this->logger->error(
				'Upload failed.',
				array(
					'slot_id'    => $slot_id,
					'product_id' => $product_id,
					'error'      => $result->get_error_message(),
				)
			);
			wp_send_json_error(array('message' => $result->get_error_message()));
		}

		$this->logger->debug(
			'Upload success.',
			array(
				'slot_id'    => $slot_id,
				'product_id' => $product_id,
				'type'       => $result['type'],
			)
		);

		wp_send_json_success(
			array(
				'url'  => $result['url'],
				'type' => $result['type'],
			)
		);
	}

	/**
	 * AJAX: receive debug log lines from the browser (logged-in shoppers only).
	 *
	 * @return void
	 */
	public function ajax_client_log()
	{
		check_ajax_referer('wpp_debug_log', 'nonce');

		if (! $this->settings->is_debug_enabled()) {
			wp_send_json_success();
		}

		$level   = isset($_POST['level']) ? sanitize_key(wp_unslash($_POST['level'])) : 'log';
		$message = isset($_POST['message']) ? sanitize_text_field(wp_unslash($_POST['message'])) : '';
		$context = array();

		if (! empty($_POST['context'])) {
			$decoded = json_decode(wp_unslash($_POST['context']), true);
			if (is_array($decoded)) {
				$context = $this->sanitize_log_context($decoded);
			}
		}

		if ('' === $message) {
			wp_send_json_success();
		}

		$log_message = '[JS] ' . $message;

		if ('error' === $level) {
			$this->logger->error($log_message, $context);
		} elseif ('warn' === $level) {
			$this->logger->warning($log_message, $context);
		} else {
			$this->logger->debug($log_message, $context);
		}

		wp_send_json_success();
	}

	/**
	 * Limit and sanitize log context from the browser.
	 *
	 * @param array $context Raw context.
	 * @return array
	 */
	private function sanitize_log_context(array $context)
	{
		$json = wp_json_encode($context);
		if (! $json || strlen($json) > 2048) {
			return array('_truncated' => true);
		}

		return $context;
	}
}