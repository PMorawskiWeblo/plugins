<?php
/**
 * Admin menu registration.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Admin_Menu {
	/**
	 * Menu slug.
	 *
	 * @var string
	 */
	const MENU_SLUG = 'storeguide-ai';
	const CAPABILITY = 'manage_options';
	const ACTION_SAVE = 'storeguide_ai_save_settings';
	const ACTION_REBUILD_INDEX = 'storeguide_ai_rebuild_index';
	const ACTION_TEST_CONNECTION = 'storeguide_ai_test_connection';
	const INDEX_PROGRESS_OPTION = 'storeguide_ai_index_progress';
	const INDEX_STATUS_OPTION = 'storeguide_ai_index_status';

	/**
	 * Register menu.
	 *
	 * @return void
	 */
	public function register() {
		add_menu_page(
			esc_html__( 'StoreGuide AI', 'storeguide-ai' ),
			esc_html__( 'StoreGuide AI', 'storeguide-ai' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render' ),
			'dashicons-format-chat',
			56
		);
	}

	/**
	 * Handle admin actions from plugin page.
	 *
	 * @return void
	 */
	public function handle_actions() {
		if ( ! is_admin() || ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$action = isset( $_POST['storeguide_ai_action'] ) ? sanitize_text_field( wp_unslash( $_POST['storeguide_ai_action'] ) ) : '';
		if ( '' === $action ) {
			return;
		}

		check_admin_referer( 'storeguide_ai_admin_action', 'storeguide_ai_nonce' );

		if ( self::ACTION_SAVE === $action ) {
			$this->save_settings();
			return;
		}

		if ( self::ACTION_REBUILD_INDEX === $action ) {
			$this->rebuild_index();
			return;
		}

		if ( self::ACTION_TEST_CONNECTION === $action ) {
			$this->test_provider_connection();
		}
	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'storeguide-ai' ) );
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard';
		$tabs       = $this->get_tabs();
		if ( ! isset( $tabs[ $active_tab ] ) ) {
			$active_tab = 'dashboard';
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'StoreGuide AI for WooCommerce', 'storeguide-ai' ); ?></h1>
			<?php $this->render_notices(); ?>

			<nav class="nav-tab-wrapper storeguide-ai-tabs">
				<?php foreach ( $tabs as $key => $label ) : ?>
					<?php
					$tab_url = add_query_arg(
						array(
							'page' => self::MENU_SLUG,
							'tab'  => $key,
						),
						admin_url( 'admin.php' )
					);
					?>
					<a href="<?php echo esc_url( $tab_url ); ?>" class="nav-tab <?php echo $active_tab === $key ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<form method="post" class="storeguide-ai-admin-form">
				<input type="hidden" name="storeguide_ai_action" value="<?php echo esc_attr( self::ACTION_SAVE ); ?>" />
				<input type="hidden" name="storeguide_ai_active_tab" value="<?php echo esc_attr( $active_tab ); ?>" />
				<?php wp_nonce_field( 'storeguide_ai_admin_action', 'storeguide_ai_nonce' ); ?>
				<?php $this->render_tab_content( $active_tab ); ?>
				<?php submit_button( esc_html__( 'Save Settings', 'storeguide-ai' ) ); ?>
			</form>

			<?php if ( 'knowledge' === $active_tab ) : ?>
				<form method="post" class="storeguide-ai-tools-form">
					<input type="hidden" name="storeguide_ai_action" value="<?php echo esc_attr( self::ACTION_REBUILD_INDEX ); ?>" />
					<?php wp_nonce_field( 'storeguide_ai_admin_action', 'storeguide_ai_nonce' ); ?>
					<?php submit_button( esc_html__( 'Run Index Batch', 'storeguide-ai' ), 'secondary', 'submit', false ); ?>
				</form>
			<?php endif; ?>

			<?php if ( 'providers' === $active_tab ) : ?>
				<form method="post" class="storeguide-ai-tools-form">
					<input type="hidden" name="storeguide_ai_action" value="<?php echo esc_attr( self::ACTION_TEST_CONNECTION ); ?>" />
					<?php wp_nonce_field( 'storeguide_ai_admin_action', 'storeguide_ai_nonce' ); ?>
					<?php submit_button( esc_html__( 'Test Connection', 'storeguide-ai' ), 'secondary', 'submit', false ); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Enqueue admin assets for plugin page.
	 *
	 * @param string $hook Hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( 'toplevel_page_' . self::MENU_SLUG !== $hook ) {
			return;
		}

		$dev_options   = get_option( 'storeguide_ai_dev_options', array() );
		$asset_version = ! empty( $dev_options['asset_version'] ) ? sanitize_text_field( (string) $dev_options['asset_version'] ) : STOREGUIDE_AI_VERSION;

		wp_enqueue_style( 'storeguide-ai-admin', STOREGUIDE_AI_PLUGIN_URL . 'assets/css/admin.css', array(), $asset_version );
		if ( ! wp_script_is( 'selectWoo', 'registered' ) && class_exists( 'WooCommerce' ) && function_exists( 'WC' ) ) {
			wp_register_script(
				'selectWoo',
				WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.full.min.js',
				array( 'jquery' ),
				'1.0.6',
				true
			);
		}
		if ( ! wp_style_is( 'selectWoo', 'registered' ) && class_exists( 'WooCommerce' ) && function_exists( 'WC' ) ) {
			wp_register_style(
				'selectWoo',
				WC()->plugin_url() . '/assets/css/select2.css',
				array(),
				'1.0.6'
			);
		}

		if ( wp_style_is( 'selectWoo', 'registered' ) ) {
			wp_enqueue_style( 'selectWoo' );
		} elseif ( wp_style_is( 'select2', 'registered' ) ) {
			wp_enqueue_style( 'select2' );
		}
		$deps = array( 'jquery' );
		if ( wp_script_is( 'selectWoo', 'registered' ) ) {
			wp_enqueue_script( 'selectWoo' );
			$deps[] = 'selectWoo';
		} elseif ( wp_script_is( 'select2', 'registered' ) ) {
			wp_enqueue_script( 'select2' );
			$deps[] = 'select2';
		}
		wp_enqueue_script( 'storeguide-ai-admin', STOREGUIDE_AI_PLUGIN_URL . 'assets/js/admin.js', $deps, $asset_version, true );
		wp_localize_script(
			'storeguide-ai-admin',
			'StoreGuideAIAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'storeguide_ai_search_coupons' ),
			)
		);
	}

	/**
	 * Ajax coupon search for large stores.
	 *
	 * @return void
	 */
	public function ajax_search_coupons() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		check_ajax_referer( 'storeguide_ai_search_coupons', 'nonce' );

		$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
		if ( strlen( $term ) > 100 ) {
			$term = substr( $term, 0, 100 );
		}
		$results = $this->search_coupons_for_select( $term );
		wp_send_json( array( 'results' => $results ) );
	}

	/**
	 * Save options from admin panel.
	 *
	 * @return void
	 */
	private function save_settings() {
		$active_tab = isset( $_POST['storeguide_ai_active_tab'] ) ? sanitize_key( wp_unslash( $_POST['storeguide_ai_active_tab'] ) ) : 'dashboard';

		switch ( $active_tab ) {
			case 'general':
				update_option( 'storeguide_ai_options', $this->sanitize_general( isset( $_POST['storeguide_ai_options'] ) ? (array) wp_unslash( $_POST['storeguide_ai_options'] ) : array() ) );
				break;
			case 'widget':
				update_option( 'storeguide_ai_widget_options', $this->sanitize_widget( isset( $_POST['storeguide_ai_widget_options'] ) ? (array) wp_unslash( $_POST['storeguide_ai_widget_options'] ) : array() ) );
				break;
			case 'custom_css':
				$widget_options               = get_option( 'storeguide_ai_widget_options', array() );
				$custom_css_input             = isset( $_POST['storeguide_ai_custom_css'] ) ? (string) wp_unslash( $_POST['storeguide_ai_custom_css'] ) : '';
				$widget_options['custom_css'] = $this->sanitize_custom_css( $custom_css_input );
				update_option( 'storeguide_ai_widget_options', $widget_options );
				break;
			case 'assistant':
				update_option( 'storeguide_ai_persona_options', $this->sanitize_persona( isset( $_POST['storeguide_ai_persona_options'] ) ? (array) wp_unslash( $_POST['storeguide_ai_persona_options'] ) : array() ) );
				break;
			case 'knowledge':
				update_option( 'storeguide_ai_index_options', $this->sanitize_index( isset( $_POST['storeguide_ai_index_options'] ) ? (array) wp_unslash( $_POST['storeguide_ai_index_options'] ) : array() ) );
				break;
			case 'providers':
				update_option( 'storeguide_ai_provider_options', $this->sanitize_provider( isset( $_POST['storeguide_ai_provider_options'] ) ? (array) wp_unslash( $_POST['storeguide_ai_provider_options'] ) : array() ) );
				break;
			case 'limits':
				update_option( 'storeguide_ai_limits_options', $this->sanitize_limits( isset( $_POST['storeguide_ai_limits_options'] ) ? (array) wp_unslash( $_POST['storeguide_ai_limits_options'] ) : array() ) );
				break;
			case 'business':
				update_option( 'storeguide_ai_business_options', $this->sanitize_business( isset( $_POST['storeguide_ai_business_options'] ) ? (array) wp_unslash( $_POST['storeguide_ai_business_options'] ) : array() ) );
				break;
			case 'rules':
				update_option( 'storeguide_ai_rules_options', $this->sanitize_rules( isset( $_POST['storeguide_ai_rules_options'] ) ? (array) wp_unslash( $_POST['storeguide_ai_rules_options'] ) : array() ) );
				break;
			case 'optimization':
				update_option( 'storeguide_ai_optimization_options', $this->sanitize_optimization( isset( $_POST['storeguide_ai_optimization_options'] ) ? (array) wp_unslash( $_POST['storeguide_ai_optimization_options'] ) : array() ) );
				break;
			case 'faq':
				update_option( 'storeguide_ai_faq_options', $this->sanitize_faq( isset( $_POST['storeguide_ai_faq_options'] ) ? (array) wp_unslash( $_POST['storeguide_ai_faq_options'] ) : array() ) );
				break;
			case 'developer':
				update_option( 'storeguide_ai_dev_options', $this->sanitize_developer( isset( $_POST['storeguide_ai_dev_options'] ) ? (array) wp_unslash( $_POST['storeguide_ai_dev_options'] ) : array() ) );
				break;
		}

		$this->redirect_with_notice(
			array(
				'updated' => '1',
				'tab'     => $active_tab,
			)
		);
	}

	/**
	 * Rebuild product index.
	 *
	 * @return void
	 */
	private function rebuild_index() {
		$builder = new StoreGuide_AI_Index_Builder();
		$index_options = get_option( 'storeguide_ai_index_options', array() );
		$sources       = $this->parse_index_sources( isset( $index_options['sources'] ) ? $index_options['sources'] : array() );
		$batch_size    = isset( $index_options['batch_size'] ) ? max( 10, min( 2000, absint( $index_options['batch_size'] ) ) ) : 300;
		$progress      = get_option(
			self::INDEX_PROGRESS_OPTION,
			array(
				'products_last_id' => 0,
				'pages_last_id'    => 0,
				'posts_last_id'    => 0,
				'total_processed'  => 0,
			)
		);
		$processed_now = 0;
		$has_more      = false;

		if ( in_array( 'products', $sources, true ) ) {
			$product_chunk = $builder->index_products_chunk( $batch_size, isset( $progress['products_last_id'] ) ? absint( $progress['products_last_id'] ) : 0 );
			$progress['products_last_id'] = (int) $product_chunk['last_id'];
			$processed_now += (int) $product_chunk['processed'];
			$has_more = $has_more || ! empty( $product_chunk['has_more'] );
		}
		if ( in_array( 'pages', $sources, true ) ) {
			$page_chunk = $builder->index_content_chunk( 'page', $batch_size, isset( $progress['pages_last_id'] ) ? absint( $progress['pages_last_id'] ) : 0 );
			$progress['pages_last_id'] = (int) $page_chunk['last_id'];
			$processed_now += (int) $page_chunk['processed'];
			$has_more = $has_more || ! empty( $page_chunk['has_more'] );
		}
		if ( in_array( 'posts', $sources, true ) ) {
			$post_chunk = $builder->index_content_chunk( 'post', $batch_size, isset( $progress['posts_last_id'] ) ? absint( $progress['posts_last_id'] ) : 0 );
			$progress['posts_last_id'] = (int) $post_chunk['last_id'];
			$processed_now += (int) $post_chunk['processed'];
			$has_more = $has_more || ! empty( $post_chunk['has_more'] );
		}
		$progress['total_processed'] = isset( $progress['total_processed'] ) ? absint( $progress['total_processed'] ) + $processed_now : $processed_now;

		if ( $has_more ) {
			update_option( self::INDEX_PROGRESS_OPTION, $progress );
		} else {
			delete_option( self::INDEX_PROGRESS_OPTION );
			delete_option( 'storeguide_ai_qa_cache' );
		}
		update_option(
			self::INDEX_STATUS_OPTION,
			array(
				'is_running'   => $has_more ? 1 : 0,
				'started_at'   => current_time( 'timestamp' ),
				'last_run_at'  => current_time( 'timestamp' ),
				'last_batch'   => $processed_now,
				'last_error'   => '',
				'completed_at' => $has_more ? 0 : current_time( 'timestamp' ),
			)
		);

		$this->redirect_with_notice(
			array(
				'reindexed'      => (string) $processed_now,
				'reindex_total'  => (string) (int) $progress['total_processed'],
				'reindex_more'   => $has_more ? '1' : '0',
				'tab'            => 'knowledge',
			)
		);
	}

	/**
	 * Redirect to plugin page with query args.
	 *
	 * @param array<string, string> $args Query args.
	 * @return void
	 */
	private function redirect_with_notice( $args ) {
		$url = add_query_arg(
			array_merge(
				array(
					'page' => self::MENU_SLUG,
				),
				$args
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Render success notices.
	 *
	 * @return void
	 */
	private function render_notices() {
		if ( isset( $_GET['updated'] ) && '1' === $_GET['updated'] ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'storeguide-ai' ) . '</p></div>';
		}

		if ( isset( $_GET['reindexed'] ) ) {
			$count = absint( $_GET['reindexed'] );
			$total = isset( $_GET['reindex_total'] ) ? absint( $_GET['reindex_total'] ) : $count;
			$more  = isset( $_GET['reindex_more'] ) && '1' === (string) $_GET['reindex_more'];
			if ( $more ) {
				echo '<div class="notice notice-info is-dismissible"><p>' . esc_html( sprintf( __( 'Index batch done: %1$d items. Total processed: %2$d. Click "Run Index Batch" again to continue large-catalog indexing.', 'storeguide-ai' ), $count, $total ) ) . '</p></div>';
			} else {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( __( 'Indexing finished. Last batch: %1$d items. Total processed: %2$d.', 'storeguide-ai' ), $count, $total ) ) . '</p></div>';
			}
		}

		if ( isset( $_GET['connection_test'] ) ) {
			$status  = sanitize_key( wp_unslash( $_GET['connection_test'] ) );
			$message = isset( $_GET['connection_message'] ) ? sanitize_text_field( wp_unslash( $_GET['connection_message'] ) ) : __( 'Connection test completed.', 'storeguide-ai' );
			$class   = 'success' === $status ? 'notice-success' : 'notice-error';
			echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
		}
	}

	/**
	 * Define all panel tabs.
	 *
	 * @return array<string, string>
	 */
	private function get_tabs() {
		return array(
			'dashboard'  => __( 'Dashboard', 'storeguide-ai' ),
			'general'    => __( 'General', 'storeguide-ai' ),
			'widget'     => __( 'Chat Widget', 'storeguide-ai' ),
			'custom_css' => __( 'Custom CSS', 'storeguide-ai' ),
			'assistant'  => __( 'Assistant Style', 'storeguide-ai' ),
			'knowledge'  => __( 'Knowledge & Index', 'storeguide-ai' ),
			'providers'  => __( 'AI Providers', 'storeguide-ai' ),
			'limits'     => __( 'Limits & Budget', 'storeguide-ai' ),
			'business'   => __( 'Business Profile', 'storeguide-ai' ),
			'rules'      => __( 'Rules', 'storeguide-ai' ),
			'logs'       => __( 'Logs & Analytics', 'storeguide-ai' ),
			'faq'        => __( 'FAQ & Learning', 'storeguide-ai' ),
			'optimization' => __( 'Optimization', 'storeguide-ai' ),
			'developer'  => __( 'Developer', 'storeguide-ai' ),
		);
	}

	/**
	 * Render active tab content.
	 *
	 * @param string $tab Active tab key.
	 * @return void
	 */
	private function render_tab_content( $tab ) {
		switch ( $tab ) {
			case 'dashboard':
				$this->render_dashboard_tab();
				break;
			case 'general':
				$this->render_general_tab();
				break;
			case 'widget':
				$this->render_widget_tab();
				break;
			case 'assistant':
				$this->render_assistant_tab();
				break;
			case 'custom_css':
				$this->render_custom_css_tab();
				break;
			case 'knowledge':
				$this->render_knowledge_tab();
				break;
			case 'providers':
				$this->render_providers_tab();
				break;
			case 'limits':
				$this->render_limits_tab();
				break;
			case 'business':
				$this->render_business_tab();
				break;
			case 'rules':
				$this->render_rules_tab();
				break;
			case 'logs':
				$this->render_logs_tab();
				break;
			case 'faq':
				$this->render_faq_tab();
				break;
			case 'optimization':
				$this->render_optimization_tab();
				break;
			case 'developer':
				$this->render_developer_tab();
				break;
		}
	}

	private function render_dashboard_tab() {
		global $wpdb;
		$documents_table = $wpdb->prefix . 'storeguide_ai_documents';
		$logs_table      = $wpdb->prefix . 'storeguide_ai_logs';
		$products_index  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$documents_table} WHERE document_type = 'product'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$logs_count      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$logs_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$provider        = get_option( 'storeguide_ai_provider_options', array() );
		$active_provider = ! empty( $provider['provider'] ) ? $provider['provider'] : __( 'Not configured', 'storeguide-ai' );
		?>
		<div class="storeguide-ai-grid">
			<div class="storeguide-ai-card"><h3><?php echo esc_html__( 'Plugin Status', 'storeguide-ai' ); ?></h3><p><?php echo esc_html__( 'Active', 'storeguide-ai' ); ?></p></div>
			<div class="storeguide-ai-card"><h3><?php echo esc_html__( 'Indexed Products', 'storeguide-ai' ); ?></h3><p><?php echo esc_html( (string) $products_index ); ?></p></div>
			<div class="storeguide-ai-card"><h3><?php echo esc_html__( 'Active Provider', 'storeguide-ai' ); ?></h3><p><?php echo esc_html( ucfirst( (string) $active_provider ) ); ?></p></div>
			<div class="storeguide-ai-card"><h3><?php echo esc_html__( 'Log Entries', 'storeguide-ai' ); ?></h3><p><?php echo esc_html( (string) $logs_count ); ?></p></div>
		</div>
		<?php
	}

	private function render_general_tab() {
		$options = get_option( 'storeguide_ai_options', array() );
		?>
		<div class="storeguide-ai-card">
			<h3><?php echo esc_html__( 'General Settings', 'storeguide-ai' ); ?></h3>
			<p><label><input type="checkbox" name="storeguide_ai_options[enabled]" value="1" <?php checked( ! empty( $options['enabled'] ) ); ?> /> <?php echo esc_html__( 'Enable plugin', 'storeguide-ai' ); ?></label></p>
			<p><label><?php echo esc_html__( 'Assistant Name', 'storeguide-ai' ); ?><br><input class="regular-text" type="text" name="storeguide_ai_options[assistant_name]" value="<?php echo esc_attr( isset( $options['assistant_name'] ) ? $options['assistant_name'] : 'StoreGuide Assistant' ); ?>"></label></p>
		</div>
		<?php
	}

	private function render_widget_tab() {
		$options = get_option( 'storeguide_ai_widget_options', array() );
		$display_fields = isset( $options['result_fields'] ) && is_array( $options['result_fields'] ) ? $options['result_fields'] : array( 'thumbnail', 'name', 'price', 'link' );
		?>
		<div class="storeguide-ai-card">
			<h3><?php echo esc_html__( 'Chat Widget', 'storeguide-ai' ); ?></h3>
			<p><label><?php echo esc_html__( 'Widget Position', 'storeguide-ai' ); ?><br>
				<select name="storeguide_ai_widget_options[position]">
					<option value="bottom-right" <?php selected( isset( $options['position'] ) ? $options['position'] : 'bottom-right', 'bottom-right' ); ?>><?php echo esc_html__( 'Bottom right', 'storeguide-ai' ); ?></option>
					<option value="bottom-left" <?php selected( isset( $options['position'] ) ? $options['position'] : '', 'bottom-left' ); ?>><?php echo esc_html__( 'Bottom left', 'storeguide-ai' ); ?></option>
				</select></label></p>
			<p><label><?php echo esc_html__( 'Welcome Message', 'storeguide-ai' ); ?><br><input class="regular-text" type="text" name="storeguide_ai_widget_options[welcome]" value="<?php echo esc_attr( isset( $options['welcome'] ) ? $options['welcome'] : 'Hi! I can help you choose a product.' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Input Placeholder', 'storeguide-ai' ); ?><br><input class="regular-text" type="text" name="storeguide_ai_widget_options[placeholder]" value="<?php echo esc_attr( isset( $options['placeholder'] ) ? $options['placeholder'] : 'What are you looking for?' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Max results per answer', 'storeguide-ai' ); ?><br><input type="number" min="1" max="20" name="storeguide_ai_widget_options[results_limit]" value="<?php echo esc_attr( isset( $options['results_limit'] ) ? (string) $options['results_limit'] : '5' ); ?>"></label></p>
			<hr />
			<p><strong><?php echo esc_html__( 'Chat Button Style', 'storeguide-ai' ); ?></strong></p>
			<p><label><?php echo esc_html__( 'Button Text', 'storeguide-ai' ); ?><br><input class="regular-text" type="text" name="storeguide_ai_widget_options[button_text]" value="<?php echo esc_attr( isset( $options['button_text'] ) ? $options['button_text'] : 'Ask StoreGuide AI' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Button Icon', 'storeguide-ai' ); ?><br><input class="regular-text" type="text" name="storeguide_ai_widget_options[button_icon]" value="<?php echo esc_attr( isset( $options['button_icon'] ) ? $options['button_icon'] : '💬' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Button Background Color', 'storeguide-ai' ); ?><br><input type="color" name="storeguide_ai_widget_options[button_bg_color]" value="<?php echo esc_attr( isset( $options['button_bg_color'] ) ? $options['button_bg_color'] : '#2271b1' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Button Text Color', 'storeguide-ai' ); ?><br><input type="color" name="storeguide_ai_widget_options[button_text_color]" value="<?php echo esc_attr( isset( $options['button_text_color'] ) ? $options['button_text_color'] : '#ffffff' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Button Border Radius (px)', 'storeguide-ai' ); ?><br><input type="number" min="0" max="40" name="storeguide_ai_widget_options[button_radius]" value="<?php echo esc_attr( isset( $options['button_radius'] ) ? (string) $options['button_radius'] : '20' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Button Font Size (px)', 'storeguide-ai' ); ?><br><input type="number" min="10" max="30" name="storeguide_ai_widget_options[button_font_size]" value="<?php echo esc_attr( isset( $options['button_font_size'] ) ? (string) $options['button_font_size'] : '14' ); ?>"></label></p>
			<hr />
			<p><strong><?php echo esc_html__( 'Chat Form Theme', 'storeguide-ai' ); ?></strong></p>
			<p><label><?php echo esc_html__( 'Theme', 'storeguide-ai' ); ?><br>
				<select name="storeguide_ai_widget_options[chat_theme]">
					<option value="light" <?php selected( isset( $options['chat_theme'] ) ? $options['chat_theme'] : 'light', 'light' ); ?>><?php echo esc_html__( 'Light', 'storeguide-ai' ); ?></option>
					<option value="dark" <?php selected( isset( $options['chat_theme'] ) ? $options['chat_theme'] : '', 'dark' ); ?>><?php echo esc_html__( 'Dark', 'storeguide-ai' ); ?></option>
				</select></label></p>
			<hr />
			<p><strong><?php echo esc_html__( 'Send Button Style', 'storeguide-ai' ); ?></strong></p>
			<p><label><?php echo esc_html__( 'Send Button Text', 'storeguide-ai' ); ?><br><input class="regular-text" type="text" name="storeguide_ai_widget_options[send_button_text]" value="<?php echo esc_attr( isset( $options['send_button_text'] ) ? $options['send_button_text'] : 'Send' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Send Button Background Color', 'storeguide-ai' ); ?><br><input type="color" name="storeguide_ai_widget_options[send_button_bg_color]" value="<?php echo esc_attr( isset( $options['send_button_bg_color'] ) ? $options['send_button_bg_color'] : '#2271b1' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Send Button Text Color', 'storeguide-ai' ); ?><br><input type="color" name="storeguide_ai_widget_options[send_button_text_color]" value="<?php echo esc_attr( isset( $options['send_button_text_color'] ) ? $options['send_button_text_color'] : '#ffffff' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Send Button Border Radius (px)', 'storeguide-ai' ); ?><br><input type="number" min="0" max="30" name="storeguide_ai_widget_options[send_button_radius]" value="<?php echo esc_attr( isset( $options['send_button_radius'] ) ? (string) $options['send_button_radius'] : '8' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Send Button Font Size (px)', 'storeguide-ai' ); ?><br><input type="number" min="10" max="24" name="storeguide_ai_widget_options[send_button_font_size]" value="<?php echo esc_attr( isset( $options['send_button_font_size'] ) ? (string) $options['send_button_font_size'] : '13' ); ?>"></label></p>
			<hr />
			<p><strong><?php echo esc_html__( 'Results Display', 'storeguide-ai' ); ?></strong></p>
			<p><label><input type="checkbox" name="storeguide_ai_widget_options[result_fields][]" value="thumbnail" <?php checked( in_array( 'thumbnail', $display_fields, true ) ); ?> /> <?php echo esc_html__( 'Show thumbnail', 'storeguide-ai' ); ?></label></p>
			<p><label><input type="checkbox" name="storeguide_ai_widget_options[result_fields][]" value="name" <?php checked( in_array( 'name', $display_fields, true ) ); ?> /> <?php echo esc_html__( 'Show product name', 'storeguide-ai' ); ?></label></p>
			<p><label><input type="checkbox" name="storeguide_ai_widget_options[result_fields][]" value="price" <?php checked( in_array( 'price', $display_fields, true ) ); ?> /> <?php echo esc_html__( 'Show price', 'storeguide-ai' ); ?></label></p>
			<p><label><input type="checkbox" name="storeguide_ai_widget_options[result_fields][]" value="link" <?php checked( in_array( 'link', $display_fields, true ) ); ?> /> <?php echo esc_html__( 'Show product link', 'storeguide-ai' ); ?></label></p>
			<p><label><input type="checkbox" name="storeguide_ai_widget_options[result_fields][]" value="availability" <?php checked( in_array( 'availability', $display_fields, true ) ); ?> /> <?php echo esc_html__( 'Show availability', 'storeguide-ai' ); ?></label></p>
			<p><label><input type="checkbox" name="storeguide_ai_widget_options[show_related_products]" value="1" <?php checked( ! empty( $options['show_related_products'] ) ); ?> /> <?php echo esc_html__( 'Show related products suggestions', 'storeguide-ai' ); ?></label></p>
		</div>
		<?php
	}

	private function render_assistant_tab() {
		$options = get_option( 'storeguide_ai_persona_options', array() );
		?>
		<div class="storeguide-ai-card">
			<h3><?php echo esc_html__( 'Assistant Style', 'storeguide-ai' ); ?></h3>
			<p><label><?php echo esc_html__( 'Role', 'storeguide-ai' ); ?><br><input class="regular-text" type="text" name="storeguide_ai_persona_options[role]" value="<?php echo esc_attr( isset( $options['role'] ) ? $options['role'] : 'Product advisor' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Tone', 'storeguide-ai' ); ?><br><input class="regular-text" type="text" name="storeguide_ai_persona_options[tone]" value="<?php echo esc_attr( isset( $options['tone'] ) ? $options['tone'] : 'Professional and friendly' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Response Length', 'storeguide-ai' ); ?><br>
				<select name="storeguide_ai_persona_options[length]">
					<option value="short" <?php selected( isset( $options['length'] ) ? $options['length'] : 'short', 'short' ); ?>><?php echo esc_html__( 'Short', 'storeguide-ai' ); ?></option>
					<option value="medium" <?php selected( isset( $options['length'] ) ? $options['length'] : '', 'medium' ); ?>><?php echo esc_html__( 'Medium', 'storeguide-ai' ); ?></option>
					<option value="detailed" <?php selected( isset( $options['length'] ) ? $options['length'] : '', 'detailed' ); ?>><?php echo esc_html__( 'Detailed', 'storeguide-ai' ); ?></option>
				</select></label></p>
			<p><label><?php echo esc_html__( 'Forbidden Behaviors', 'storeguide-ai' ); ?><br><textarea class="large-text" rows="4" name="storeguide_ai_persona_options[forbidden]"><?php echo esc_textarea( isset( $options['forbidden'] ) ? $options['forbidden'] : 'Never invent stock, price, or compatibility.' ); ?></textarea></label></p>
		</div>
		<?php
	}

	private function render_custom_css_tab() {
		$options    = get_option( 'storeguide_ai_widget_options', array() );
		$custom_css = isset( $options['custom_css'] ) ? (string) $options['custom_css'] : '';
		?>
		<div class="storeguide-ai-card">
			<h3><?php echo esc_html__( 'Custom CSS for Chat Widget', 'storeguide-ai' ); ?></h3>
			<p class="description"><?php echo esc_html__( 'Paste your own CSS rules to further style the chat widget. This CSS is loaded only when the widget assets are loaded on frontend.', 'storeguide-ai' ); ?></p>
			<p class="description"><code>.storeguide-ai-widget .storeguide-ai-panel { ... }</code></p>
			<p>
				<label for="storeguide-ai-custom-css" class="screen-reader-text"><?php echo esc_html__( 'Custom CSS', 'storeguide-ai' ); ?></label>
				<textarea id="storeguide-ai-custom-css" class="large-text code" rows="16" name="storeguide_ai_custom_css"><?php echo esc_textarea( $custom_css ); ?></textarea>
			</p>
		</div>
		<?php
	}

	private function render_knowledge_tab() {
		$options = get_option( 'storeguide_ai_index_options', array() );
		$sources = $this->parse_index_sources( isset( $options['sources'] ) ? $options['sources'] : array() );
		$acf_keys = isset( $options['acf_keys'] ) ? (string) $options['acf_keys'] : '';
		$content_meta_keys = isset( $options['content_meta_keys'] ) ? (string) $options['content_meta_keys'] : '';
		$acf_auto_detect = ! empty( $options['acf_auto_detect'] );
		$content_meta_auto_detect = ! empty( $options['content_meta_auto_detect'] );
		$detected_acf_keys = $this->detect_product_meta_keys( 40 );
		$detected_content_keys = $this->detect_content_meta_keys( 40 );
		$progress = get_option( self::INDEX_PROGRESS_OPTION, array() );
		$status   = get_option( self::INDEX_STATUS_OPTION, array() );
		$stats    = $this->get_index_progress_stats( $sources, $progress, $status );
		?>
		<div class="storeguide-ai-card">
			<h3><?php echo esc_html__( 'Knowledge & Index', 'storeguide-ai' ); ?></h3>
			<p><label><input type="checkbox" name="storeguide_ai_index_options[autosync]" value="1" <?php checked( ! empty( $options['autosync'] ) ); ?> /> <?php echo esc_html__( 'Enable incremental autosync on product updates', 'storeguide-ai' ); ?></label></p>
			<p><label><input type="checkbox" name="storeguide_ai_index_options[background_worker]" value="1" <?php checked( ! empty( $options['background_worker'] ) ); ?> /> <?php echo esc_html__( 'Enable background index worker (every 5 minutes)', 'storeguide-ai' ); ?></label></p>
			<p><label><?php echo esc_html__( 'Batch Size', 'storeguide-ai' ); ?><br><input type="number" min="10" max="1000" name="storeguide_ai_index_options[batch_size]" value="<?php echo esc_attr( isset( $options['batch_size'] ) ? (string) $options['batch_size'] : '100' ); ?>"></label></p>
			<p><strong><?php echo esc_html__( 'Indexed Sources', 'storeguide-ai' ); ?></strong></p>
			<p><label><input type="checkbox" name="storeguide_ai_index_options[sources][]" value="products" <?php checked( in_array( 'products', $sources, true ) ); ?> /> <?php echo esc_html__( 'Products', 'storeguide-ai' ); ?></label></p>
			<p><label><input type="checkbox" name="storeguide_ai_index_options[sources][]" value="pages" <?php checked( in_array( 'pages', $sources, true ) ); ?> /> <?php echo esc_html__( 'Pages', 'storeguide-ai' ); ?></label></p>
			<p><label><input type="checkbox" name="storeguide_ai_index_options[sources][]" value="posts" <?php checked( in_array( 'posts', $sources, true ) ); ?> /> <?php echo esc_html__( 'Blog posts', 'storeguide-ai' ); ?></label></p>
			<hr />
			<p>
				<strong><?php echo esc_html__( 'Semantic Retrieval (Vector Store, optional)', 'storeguide-ai' ); ?></strong>
				<span class="storeguide-ai-tooltip" title="<?php echo esc_attr__( 'When enabled, plugin can optionally read semantically similar results from an external vector store integration (if connected). Standard SQL retrieval remains active as fallback.', 'storeguide-ai' ); ?>">?</span>
			</p>
			<p><label><input type="checkbox" name="storeguide_ai_index_options[semantic_retrieval_enabled]" value="1" <?php checked( ! empty( $options['semantic_retrieval_enabled'] ) ); ?> /> <?php echo esc_html__( 'Enable semantic retrieval integration (optional)', 'storeguide-ai' ); ?></label></p>
			<p><label><?php echo esc_html__( 'Semantic top-K results', 'storeguide-ai' ); ?> <span class="storeguide-ai-tooltip" title="<?php echo esc_attr__( 'Maximum number of semantic candidates requested from vector store integration before merging with standard results.', 'storeguide-ai' ); ?>">?</span><br><input type="number" min="1" max="20" name="storeguide_ai_index_options[semantic_top_k]" value="<?php echo esc_attr( isset( $options['semantic_top_k'] ) ? (string) $options['semantic_top_k'] : '5' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Pinecone index host', 'storeguide-ai' ); ?> <span class="storeguide-ai-tooltip" title="<?php echo esc_attr__( 'Host from Pinecone dashboard without protocol. Example: my-index-xxxx.svc.us-east1-gcp.pinecone.io', 'storeguide-ai' ); ?>">?</span><br><input class="regular-text" type="text" name="storeguide_ai_index_options[pinecone_host]" value="<?php echo esc_attr( isset( $options['pinecone_host'] ) ? (string) $options['pinecone_host'] : '' ); ?>" placeholder="my-index-xxxx.svc.us-east1-gcp.pinecone.io"></label></p>
			<p><label><?php echo esc_html__( 'Pinecone API Key', 'storeguide-ai' ); ?> <span class="storeguide-ai-tooltip" title="<?php echo esc_attr__( 'Used for vector upsert/query/delete in Pinecone.', 'storeguide-ai' ); ?>">?</span><br><input class="regular-text" type="password" name="storeguide_ai_index_options[pinecone_api_key]" value="<?php echo esc_attr( isset( $options['pinecone_api_key'] ) ? (string) $options['pinecone_api_key'] : '' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Pinecone namespace (optional)', 'storeguide-ai' ); ?> <span class="storeguide-ai-tooltip" title="<?php echo esc_attr__( 'Logical partition in a single Pinecone index. Leave empty to use default namespace.', 'storeguide-ai' ); ?>">?</span><br><input class="regular-text" type="text" name="storeguide_ai_index_options[pinecone_namespace]" value="<?php echo esc_attr( isset( $options['pinecone_namespace'] ) ? (string) $options['pinecone_namespace'] : '' ); ?>" placeholder="storeguide-main"></label></p>
			<p><label><?php echo esc_html__( 'Embedding model', 'storeguide-ai' ); ?> <span class="storeguide-ai-tooltip" title="<?php echo esc_attr__( 'OpenAI embedding model used to vectorize products/pages and user queries.', 'storeguide-ai' ); ?>">?</span><br><input class="regular-text" type="text" name="storeguide_ai_index_options[embedding_model]" value="<?php echo esc_attr( isset( $options['embedding_model'] ) ? (string) $options['embedding_model'] : 'text-embedding-3-small' ); ?>" placeholder="text-embedding-3-small"></label></p>
			<p><label><?php echo esc_html__( 'Embedding API Key (optional)', 'storeguide-ai' ); ?> <span class="storeguide-ai-tooltip" title="<?php echo esc_attr__( 'If empty and provider is OpenAI, plugin uses the main OpenAI API key. Fill this field to use a separate key.', 'storeguide-ai' ); ?>">?</span><br><input class="regular-text" type="password" name="storeguide_ai_index_options[embedding_api_key]" value="<?php echo esc_attr( isset( $options['embedding_api_key'] ) ? (string) $options['embedding_api_key'] : '' ); ?>"></label></p>
			<hr />
			<p><strong><?php echo esc_html__( 'ACF Product Fields', 'storeguide-ai' ); ?></strong></p>
			<p><label><input type="checkbox" name="storeguide_ai_index_options[acf_enabled]" value="1" <?php checked( ! empty( $options['acf_enabled'] ) ); ?> /> <?php echo esc_html__( 'Enable indexing of selected ACF/meta keys for products', 'storeguide-ai' ); ?></label></p>
			<p><label><input type="checkbox" name="storeguide_ai_index_options[acf_auto_detect]" value="1" <?php checked( ! empty( $options['acf_auto_detect'] ) ); ?> /> <?php echo esc_html__( 'Auto-detect keys (recommended, no manual typing)', 'storeguide-ai' ); ?></label></p>
			<p><label><?php echo esc_html__( 'Indexed ACF/meta keys (comma separated)', 'storeguide-ai' ); ?><br><input class="regular-text" type="text" name="storeguide_ai_index_options[acf_keys]" value="<?php echo esc_attr( $acf_keys ); ?>" placeholder="np. skladniki, zastosowanie, producent"></label></p>
			<?php if ( $acf_auto_detect ) : ?>
				<p class="description"><?php echo esc_html__( 'Auto-detect is enabled: detected keys are included, and keys from this field are added too.', 'storeguide-ai' ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $detected_acf_keys ) ) : ?>
				<p class="description"><?php echo esc_html__( 'Detected keys from recent products (click and copy):', 'storeguide-ai' ); ?></p>
				<p class="description"><code><?php echo esc_html( implode( ', ', $detected_acf_keys ) ); ?></code></p>
			<?php else : ?>
				<p class="description"><?php echo esc_html__( 'No candidate product meta keys detected yet.', 'storeguide-ai' ); ?></p>
			<?php endif; ?>
			<hr />
			<p><strong><?php echo esc_html__( 'Pages/Posts ACF/Meta Fields', 'storeguide-ai' ); ?></strong></p>
			<p><label><input type="checkbox" name="storeguide_ai_index_options[content_meta_enabled]" value="1" <?php checked( ! empty( $options['content_meta_enabled'] ) ); ?> /> <?php echo esc_html__( 'Enable indexing of selected ACF/meta keys for pages/posts', 'storeguide-ai' ); ?></label></p>
			<p><label><input type="checkbox" name="storeguide_ai_index_options[content_meta_auto_detect]" value="1" <?php checked( ! empty( $options['content_meta_auto_detect'] ) ); ?> /> <?php echo esc_html__( 'Auto-detect keys (recommended, no manual typing)', 'storeguide-ai' ); ?></label></p>
			<p><label><?php echo esc_html__( 'Indexed content meta keys (comma separated)', 'storeguide-ai' ); ?><br><input class="regular-text" type="text" name="storeguide_ai_index_options[content_meta_keys]" value="<?php echo esc_attr( $content_meta_keys ); ?>" placeholder="np. faq, short_intro, seo_description"></label></p>
			<?php if ( $content_meta_auto_detect ) : ?>
				<p class="description"><?php echo esc_html__( 'Auto-detect is enabled: detected keys are included, and keys from this field are added too.', 'storeguide-ai' ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $detected_content_keys ) ) : ?>
				<p class="description"><?php echo esc_html__( 'Detected keys from recent pages/posts:', 'storeguide-ai' ); ?></p>
				<p class="description"><code><?php echo esc_html( implode( ', ', $detected_content_keys ) ); ?></code></p>
			<?php else : ?>
				<p class="description"><?php echo esc_html__( 'No candidate page/post meta keys detected yet.', 'storeguide-ai' ); ?></p>
			<?php endif; ?>
			<hr />
			<p><strong><?php echo esc_html__( 'Index Progress', 'storeguide-ai' ); ?></strong></p>
			<div style="background:#f0f0f1;border-radius:8px;overflow:hidden;height:12px;max-width:680px;">
				<div style="height:12px;background:#2271b1;width:<?php echo esc_attr( (string) $stats['percent'] ); ?>%;"></div>
			</div>
			<p><?php echo esc_html( sprintf( __( 'Processed: %1$d / %2$d (%3$s%%)', 'storeguide-ai' ), $stats['processed'], $stats['total'], number_format_i18n( $stats['percent'], 1 ) ) ); ?></p>
			<p><?php echo esc_html( sprintf( __( 'Last batch: %1$d | ETA: %2$s', 'storeguide-ai' ), $stats['last_batch'], $stats['eta'] ) ); ?></p>
			<p><?php echo esc_html( sprintf( __( 'Products: %1$d/%2$d | Pages: %3$d/%4$d | Posts: %5$d/%6$d', 'storeguide-ai' ), $stats['products_processed'], $stats['products_total'], $stats['pages_processed'], $stats['pages_total'], $stats['posts_processed'], $stats['posts_total'] ) ); ?></p>
		</div>
		<?php
	}

	private function render_providers_tab() {
		$options = get_option( 'storeguide_ai_provider_options', array() );
		$provider = isset( $options['provider'] ) ? sanitize_key( $options['provider'] ) : 'openai';
		$model_options = $this->get_model_options( $provider );
		$current_model = isset( $options['model'] ) ? sanitize_text_field( $options['model'] ) : $this->get_default_model_for_provider( $provider );
		?>
		<div class="storeguide-ai-card">
			<h3><?php echo esc_html__( 'AI Providers', 'storeguide-ai' ); ?></h3>
			<p><label><?php echo esc_html__( 'Provider', 'storeguide-ai' ); ?><br>
				<select name="storeguide_ai_provider_options[provider]">
					<option value="openai" <?php selected( $provider, 'openai' ); ?>>OpenAI</option>
					<option value="openrouter" <?php selected( $provider, 'openrouter' ); ?>>OpenRouter</option>
					<option value="custom" <?php selected( $provider, 'custom' ); ?>><?php echo esc_html__( 'Custom endpoint', 'storeguide-ai' ); ?></option>
				</select></label></p>
			<p><label><?php echo esc_html__( 'API Key', 'storeguide-ai' ); ?><br><input class="regular-text" type="password" name="storeguide_ai_provider_options[api_key]" value="<?php echo esc_attr( isset( $options['api_key'] ) ? $options['api_key'] : '' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Base URL', 'storeguide-ai' ); ?><br><input class="regular-text" type="url" name="storeguide_ai_provider_options[base_url]" value="<?php echo esc_attr( isset( $options['base_url'] ) ? $options['base_url'] : '' ); ?>"></label></p>
			<p class="description">
				<?php echo esc_html__( 'Leave Base URL empty for standard OpenAI/OpenRouter endpoints. Use it only for OpenAI-compatible custom APIs (example: https://api.example.com/v1).', 'storeguide-ai' ); ?>
			</p>
			<p><label><?php echo esc_html__( 'Model', 'storeguide-ai' ); ?><br>
				<select name="storeguide_ai_provider_options[model]">
					<?php foreach ( $model_options as $model_value => $model_label ) : ?>
						<option value="<?php echo esc_attr( $model_value ); ?>" <?php selected( $current_model, $model_value ); ?>>
							<?php echo esc_html( $model_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label></p>
			<p><label><?php echo esc_html__( 'Temperature', 'storeguide-ai' ); ?><br><input type="number" step="0.1" min="0" max="2" name="storeguide_ai_provider_options[temperature]" value="<?php echo esc_attr( isset( $options['temperature'] ) ? (string) $options['temperature'] : '0.3' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Max Tokens', 'storeguide-ai' ); ?><br><input type="number" min="100" max="8192" name="storeguide_ai_provider_options[max_tokens]" value="<?php echo esc_attr( isset( $options['max_tokens'] ) ? (string) $options['max_tokens'] : '800' ); ?>"></label></p>
		</div>
		<?php
	}

	private function render_limits_tab() {
		$options = get_option( 'storeguide_ai_limits_options', array() );
		?>
		<div class="storeguide-ai-card">
			<h3><?php echo esc_html__( 'Limits & Budget', 'storeguide-ai' ); ?></h3>
			<p><label><?php echo esc_html__( 'Daily Request Limit', 'storeguide-ai' ); ?><br><input type="number" min="0" name="storeguide_ai_limits_options[daily_requests]" value="<?php echo esc_attr( isset( $options['daily_requests'] ) ? (string) $options['daily_requests'] : '500' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Monthly Request Limit', 'storeguide-ai' ); ?><br><input type="number" min="0" name="storeguide_ai_limits_options[monthly_requests]" value="<?php echo esc_attr( isset( $options['monthly_requests'] ) ? (string) $options['monthly_requests'] : '10000' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Daily Cost Limit', 'storeguide-ai' ); ?><br><input type="number" step="0.01" min="0" name="storeguide_ai_limits_options[daily_cost]" value="<?php echo esc_attr( isset( $options['daily_cost'] ) ? (string) $options['daily_cost'] : '10' ); ?>"></label></p>
		</div>
		<?php
	}

	private function render_business_tab() {
		$options = get_option( 'storeguide_ai_business_options', array() );
		?>
		<div class="storeguide-ai-card">
			<h3><?php echo esc_html__( 'Business Profile', 'storeguide-ai' ); ?></h3>
			<p><label><?php echo esc_html__( 'Store Name', 'storeguide-ai' ); ?><br><input class="regular-text" type="text" name="storeguide_ai_business_options[store_name]" value="<?php echo esc_attr( isset( $options['store_name'] ) ? $options['store_name'] : '' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Store URL', 'storeguide-ai' ); ?><br><input class="regular-text" type="url" name="storeguide_ai_business_options[store_url]" value="<?php echo esc_attr( isset( $options['store_url'] ) ? $options['store_url'] : home_url( '/' ) ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Store Address', 'storeguide-ai' ); ?><br><textarea class="large-text" rows="2" name="storeguide_ai_business_options[store_address]"><?php echo esc_textarea( isset( $options['store_address'] ) ? $options['store_address'] : '' ); ?></textarea></label></p>
			<p><label><?php echo esc_html__( 'Google Maps Link', 'storeguide-ai' ); ?><br><input class="regular-text" type="url" name="storeguide_ai_business_options[google_maps_url]" value="<?php echo esc_attr( isset( $options['google_maps_url'] ) ? $options['google_maps_url'] : '' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Support Email', 'storeguide-ai' ); ?><br><input class="regular-text" type="email" name="storeguide_ai_business_options[support_email]" value="<?php echo esc_attr( isset( $options['support_email'] ) ? $options['support_email'] : '' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Support Phone', 'storeguide-ai' ); ?><br><input class="regular-text" type="text" name="storeguide_ai_business_options[support_phone]" value="<?php echo esc_attr( isset( $options['support_phone'] ) ? $options['support_phone'] : '' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Shipping Countries', 'storeguide-ai' ); ?><br><input class="regular-text" type="text" name="storeguide_ai_business_options[shipping_countries]" value="<?php echo esc_attr( isset( $options['shipping_countries'] ) ? $options['shipping_countries'] : '' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Company Description', 'storeguide-ai' ); ?><br><textarea class="large-text" rows="4" name="storeguide_ai_business_options[company_description]"><?php echo esc_textarea( isset( $options['company_description'] ) ? $options['company_description'] : '' ); ?></textarea></label></p>
			<hr />
			<p><strong><?php echo esc_html__( 'Legal & Compliance', 'storeguide-ai' ); ?></strong></p>
			<p><label><?php echo esc_html__( 'Company Legal Name', 'storeguide-ai' ); ?><br><input class="regular-text" type="text" name="storeguide_ai_business_options[company_legal_name]" value="<?php echo esc_attr( isset( $options['company_legal_name'] ) ? $options['company_legal_name'] : '' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Tax ID (NIP/VAT)', 'storeguide-ai' ); ?><br><input class="regular-text" type="text" name="storeguide_ai_business_options[tax_id]" value="<?php echo esc_attr( isset( $options['tax_id'] ) ? $options['tax_id'] : '' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Business Registry Number', 'storeguide-ai' ); ?><br><input class="regular-text" type="text" name="storeguide_ai_business_options[registry_number]" value="<?php echo esc_attr( isset( $options['registry_number'] ) ? $options['registry_number'] : '' ); ?>"></label></p>
			<hr />
			<p><strong><?php echo esc_html__( 'Important Pages', 'storeguide-ai' ); ?></strong></p>
			<p><label><?php echo esc_html__( 'Contact Page URL', 'storeguide-ai' ); ?><br><input class="regular-text" type="url" name="storeguide_ai_business_options[contact_page_url]" value="<?php echo esc_attr( isset( $options['contact_page_url'] ) ? $options['contact_page_url'] : '' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Privacy Policy URL', 'storeguide-ai' ); ?><br><input class="regular-text" type="url" name="storeguide_ai_business_options[privacy_policy_url]" value="<?php echo esc_attr( isset( $options['privacy_policy_url'] ) ? $options['privacy_policy_url'] : '' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Terms & Conditions URL', 'storeguide-ai' ); ?><br><input class="regular-text" type="url" name="storeguide_ai_business_options[terms_page_url]" value="<?php echo esc_attr( isset( $options['terms_page_url'] ) ? $options['terms_page_url'] : '' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Returns & Refunds URL', 'storeguide-ai' ); ?><br><input class="regular-text" type="url" name="storeguide_ai_business_options[returns_page_url]" value="<?php echo esc_attr( isset( $options['returns_page_url'] ) ? $options['returns_page_url'] : '' ); ?>"></label></p>
		</div>
		<?php
	}

	private function render_rules_tab() {
		$options = get_option( 'storeguide_ai_rules_options', array() );
		$coupon_rules = isset( $options['coupon_recommendations'] ) && is_array( $options['coupon_recommendations'] ) ? $options['coupon_recommendations'] : array();
		?>
		<div class="storeguide-ai-card">
			<h3><?php echo esc_html__( 'Rules', 'storeguide-ai' ); ?></h3>
			<p><label><input type="checkbox" name="storeguide_ai_rules_options[in_stock_only]" value="1" <?php checked( ! empty( $options['in_stock_only'] ) ); ?> /> <?php echo esc_html__( 'Prefer in-stock products only', 'storeguide-ai' ); ?></label></p>
			<p><label><?php echo esc_html__( 'Excluded Categories (IDs, comma separated)', 'storeguide-ai' ); ?><br><input class="regular-text" type="text" name="storeguide_ai_rules_options[excluded_categories]" value="<?php echo esc_attr( isset( $options['excluded_categories'] ) ? $options['excluded_categories'] : '' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Promoted Product IDs (comma separated)', 'storeguide-ai' ); ?><br><input class="regular-text" type="text" name="storeguide_ai_rules_options[promoted_products]" value="<?php echo esc_attr( isset( $options['promoted_products'] ) ? $options['promoted_products'] : '' ); ?>"></label></p>
			<hr />
			<p><strong><?php echo esc_html__( 'Coupons Recommendations', 'storeguide-ai' ); ?></strong></p>
			<p><label><input type="checkbox" name="storeguide_ai_rules_options[coupons_enabled]" value="1" <?php checked( ! empty( $options['coupons_enabled'] ) ); ?> /> <?php echo esc_html__( 'Enable coupon recommendations in chat', 'storeguide-ai' ); ?></label></p>
			<table class="widefat striped storeguide-ai-coupon-table">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Coupon', 'storeguide-ai' ); ?></th>
						<th><?php echo esc_html__( 'Recommend from', 'storeguide-ai' ); ?></th>
						<th><?php echo esc_html__( 'Recommend to', 'storeguide-ai' ); ?></th>
						<th><?php echo esc_html__( 'Show value and conditions', 'storeguide-ai' ); ?></th>
						<th><?php echo esc_html__( 'Action', 'storeguide-ai' ); ?></th>
					</tr>
				</thead>
				<tbody id="storeguide-ai-coupon-rules-body">
					<?php foreach ( $coupon_rules as $index => $rule ) : ?>
						<?php
						$coupon_id = isset( $rule['coupon_id'] ) ? absint( $rule['coupon_id'] ) : 0;
						$coupon_label = '';
						if ( $coupon_id > 0 ) {
							$coupon_post = get_post( $coupon_id );
							if ( $coupon_post && 'shop_coupon' === $coupon_post->post_type ) {
								$coupon_label = '' !== (string) $coupon_post->post_title ? (string) $coupon_post->post_title : (string) $coupon_id;
							}
						}
						?>
						<tr>
							<td>
								<select class="storeguide-ai-coupon-select" name="storeguide_ai_rules_options[coupon_recommendations][<?php echo esc_attr( (string) $index ); ?>][coupon_id]" data-placeholder="<?php echo esc_attr__( 'Search by ID or coupon name', 'storeguide-ai' ); ?>">
									<?php if ( $coupon_id > 0 ) : ?>
										<option value="<?php echo esc_attr( (string) $coupon_id ); ?>" selected><?php echo esc_html( $coupon_label ); ?></option>
									<?php endif; ?>
								</select>
							</td>
							<td><input type="date" name="storeguide_ai_rules_options[coupon_recommendations][<?php echo esc_attr( (string) $index ); ?>][start_date]" value="<?php echo esc_attr( isset( $rule['start_date'] ) ? $rule['start_date'] : '' ); ?>"></td>
							<td><input type="date" name="storeguide_ai_rules_options[coupon_recommendations][<?php echo esc_attr( (string) $index ); ?>][end_date]" value="<?php echo esc_attr( isset( $rule['end_date'] ) ? $rule['end_date'] : '' ); ?>"></td>
							<td><label><input type="checkbox" name="storeguide_ai_rules_options[coupon_recommendations][<?php echo esc_attr( (string) $index ); ?>][include_conditions]" value="1" <?php checked( ! empty( $rule['include_conditions'] ) ); ?>> <?php echo esc_html__( 'Include requirements', 'storeguide-ai' ); ?></label></td>
							<td><button type="button" class="button-link-delete storeguide-ai-remove-coupon-rule"><?php echo esc_html__( 'Remove', 'storeguide-ai' ); ?></button></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p><button type="button" class="button button-secondary" id="storeguide-ai-add-coupon-rule"><?php echo esc_html__( 'Add coupon rule', 'storeguide-ai' ); ?></button></p>
			<script type="text/template" id="storeguide-ai-coupon-rule-row-template">
				<tr>
					<td>
						<select class="storeguide-ai-coupon-select" name="storeguide_ai_rules_options[coupon_recommendations][__INDEX__][coupon_id]" data-placeholder="<?php echo esc_attr__( 'Search by ID or coupon name', 'storeguide-ai' ); ?>"></select>
					</td>
					<td><input type="date" name="storeguide_ai_rules_options[coupon_recommendations][__INDEX__][start_date]" value=""></td>
					<td><input type="date" name="storeguide_ai_rules_options[coupon_recommendations][__INDEX__][end_date]" value=""></td>
					<td><label><input type="checkbox" name="storeguide_ai_rules_options[coupon_recommendations][__INDEX__][include_conditions]" value="1"> <?php echo esc_html__( 'Include requirements', 'storeguide-ai' ); ?></label></td>
					<td><button type="button" class="button-link-delete storeguide-ai-remove-coupon-rule"><?php echo esc_html__( 'Remove', 'storeguide-ai' ); ?></button></td>
				</tr>
			</script>
		</div>
		<?php
	}

	private function render_logs_tab() {
		global $wpdb;
		$table         = $wpdb->prefix . 'storeguide_ai_logs';
		$messages_table = $wpdb->prefix . 'storeguide_ai_messages';
		$error_cnt     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE level = %s", 'error' ) );
		$all_cnt       = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$zero_result   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE details_json LIKE '%\"results\":0%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$cached_hits   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE message = 'Served response from Q&A cache.'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$top_questions = $wpdb->get_results(
			"SELECT message_text, COUNT(*) AS cnt
			FROM {$messages_table}
			WHERE role = 'user' AND message_text <> ''
			GROUP BY message_text
			ORDER BY cnt DESC
			LIMIT 10",
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		?>
		<div class="storeguide-ai-card">
			<h3><?php echo esc_html__( 'Logs & Analytics', 'storeguide-ai' ); ?></h3>
			<p><?php echo esc_html( sprintf( __( 'Total logs: %d', 'storeguide-ai' ), $all_cnt ) ); ?></p>
			<p><?php echo esc_html( sprintf( __( 'Error logs: %d', 'storeguide-ai' ), $error_cnt ) ); ?></p>
			<p><?php echo esc_html( sprintf( __( 'Zero-result queries: %d', 'storeguide-ai' ), $zero_result ) ); ?></p>
			<p><?php echo esc_html( sprintf( __( 'Cache hits: %d', 'storeguide-ai' ), $cached_hits ) ); ?></p>
			<p><strong><?php echo esc_html__( 'Top user questions', 'storeguide-ai' ); ?></strong></p>
			<?php if ( empty( $top_questions ) ) : ?>
				<p class="description"><?php echo esc_html__( 'No data yet.', 'storeguide-ai' ); ?></p>
			<?php else : ?>
				<ol>
					<?php foreach ( $top_questions as $row ) : ?>
						<li><?php echo esc_html( isset( $row['message_text'] ) ? (string) $row['message_text'] : '' ); ?> (<?php echo esc_html( (string) absint( isset( $row['cnt'] ) ? $row['cnt'] : 0 ) ); ?>)</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_developer_tab() {
		$options = get_option( 'storeguide_ai_dev_options', array() );
		?>
		<div class="storeguide-ai-card">
			<h3><?php echo esc_html__( 'Developer', 'storeguide-ai' ); ?></h3>
			<p><label><input type="checkbox" name="storeguide_ai_dev_options[debug_enabled]" value="1" <?php checked( ! empty( $options['debug_enabled'] ) ); ?> /> <?php echo esc_html__( 'Enable debug logging', 'storeguide-ai' ); ?></label></p>
			<p><label><?php echo esc_html__( 'Asset Version', 'storeguide-ai' ); ?><br><input class="regular-text" type="text" name="storeguide_ai_dev_options[asset_version]" value="<?php echo esc_attr( isset( $options['asset_version'] ) ? $options['asset_version'] : STOREGUIDE_AI_VERSION ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Schema Version', 'storeguide-ai' ); ?><br><input class="regular-text" type="text" disabled value="<?php echo esc_attr( (string) get_option( 'storeguide_ai_schema_version', '0.0.0' ) ); ?>"></label></p>
		</div>
		<?php
	}

	private function render_optimization_tab() {
		$options = get_option( 'storeguide_ai_optimization_options', array() );
		$cache   = get_option( 'storeguide_ai_qa_cache', array() );
		global $wpdb;
		$messages_table = $wpdb->prefix . 'storeguide_ai_messages';
		$learning_window = isset( $options['learning_window'] ) ? max( 100, min( 50000, absint( $options['learning_window'] ) ) ) : 1000;
		$faq_items       = isset( $options['faq_items'] ) ? max( 1, min( 50, absint( $options['faq_items'] ) ) ) : 10;
		$top_questions   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT message_text, COUNT(*) AS cnt
				FROM (
					SELECT LOWER(TRIM(message_text)) AS message_text
					FROM {$messages_table}
					WHERE role = %s AND message_text <> ''
					ORDER BY id DESC
					LIMIT %d
				) recent
				GROUP BY message_text
				ORDER BY cnt DESC
				LIMIT %d",
				'user',
				$learning_window,
				$faq_items
			),
			ARRAY_A
		);
		?>
		<div class="storeguide-ai-card">
			<h3><?php echo esc_html__( 'Optimization & Learning', 'storeguide-ai' ); ?></h3>
			<p><label><input type="checkbox" name="storeguide_ai_optimization_options[cache_enabled]" value="1" <?php checked( ! empty( $options['cache_enabled'] ) ); ?> /> <?php echo esc_html__( 'Enable Q&A response cache', 'storeguide-ai' ); ?></label></p>
			<p><label><?php echo esc_html__( 'Cache TTL (minutes)', 'storeguide-ai' ); ?><br><input type="number" min="1" max="10080" name="storeguide_ai_optimization_options[cache_ttl_minutes]" value="<?php echo esc_attr( isset( $options['cache_ttl_minutes'] ) ? (string) $options['cache_ttl_minutes'] : '1440' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Cache max entries', 'storeguide-ai' ); ?><br><input type="number" min="50" max="20000" name="storeguide_ai_optimization_options[cache_max_entries]" value="<?php echo esc_attr( isset( $options['cache_max_entries'] ) ? (string) $options['cache_max_entries'] : '1000' ); ?>"></label></p>
			<hr />
			<p><label><input type="checkbox" name="storeguide_ai_optimization_options[faq_enabled]" value="1" <?php checked( ! empty( $options['faq_enabled'] ) ); ?> /> <?php echo esc_html__( 'Enable FAQ learning context', 'storeguide-ai' ); ?></label></p>
			<p><label><?php echo esc_html__( 'FAQ items in context', 'storeguide-ai' ); ?><br><input type="number" min="1" max="50" name="storeguide_ai_optimization_options[faq_items]" value="<?php echo esc_attr( isset( $options['faq_items'] ) ? (string) $options['faq_items'] : '10' ); ?>"></label></p>
			<p><label><?php echo esc_html__( 'Learning window (last user queries)', 'storeguide-ai' ); ?><br><input type="number" min="100" max="50000" name="storeguide_ai_optimization_options[learning_window]" value="<?php echo esc_attr( isset( $options['learning_window'] ) ? (string) $options['learning_window'] : '1000' ); ?>"></label></p>
			<hr />
			<p><strong><?php echo esc_html__( 'Cache status', 'storeguide-ai' ); ?></strong><br><?php echo esc_html( sprintf( __( 'Stored Q&A cache entries: %d', 'storeguide-ai' ), is_array( $cache ) ? count( $cache ) : 0 ) ); ?></p>
			<p><strong><?php echo esc_html__( 'Most frequent recent questions', 'storeguide-ai' ); ?></strong></p>
			<?php if ( empty( $top_questions ) ) : ?>
				<p><?php echo esc_html__( 'No user messages yet.', 'storeguide-ai' ); ?></p>
			<?php else : ?>
				<ol>
					<?php foreach ( $top_questions as $row ) : ?>
						<li><?php echo esc_html( isset( $row['message_text'] ) ? (string) $row['message_text'] : '' ); ?> (<?php echo esc_html( (string) ( isset( $row['cnt'] ) ? absint( $row['cnt'] ) : 0 ) ); ?>)</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_faq_tab() {
		$options            = get_option( 'storeguide_ai_faq_options', array() );
		$manual_qa          = isset( $options['manual_qa'] ) && is_array( $options['manual_qa'] ) ? $options['manual_qa'] : array();
		$suggested_fixes    = isset( $options['suggested_fixes'] ) && is_array( $options['suggested_fixes'] ) ? $options['suggested_fixes'] : array();
		$learning_limit     = isset( $options['learning_review_limit'] ) ? max( 100, min( 5000, absint( $options['learning_review_limit'] ) ) ) : 1000;
		$recent_pairs       = $this->get_recent_qa_pairs( $learning_limit );
		?>
		<div class="storeguide-ai-card">
			<h3><?php echo esc_html__( 'Manual FAQ (priority responses)', 'storeguide-ai' ); ?></h3>
			<p><label><input type="checkbox" name="storeguide_ai_faq_options[enabled]" value="1" <?php checked( ! empty( $options['enabled'] ) ); ?> /> <?php echo esc_html__( 'Enable manual Q&A answers in chat', 'storeguide-ai' ); ?></label></p>
			<p class="description"><?php echo esc_html__( 'If user question matches, chat returns this answer first (without extra token usage).', 'storeguide-ai' ); ?></p>
			<?php for ( $i = 0; $i < 15; $i++ ) : ?>
				<?php $row = isset( $manual_qa[ $i ] ) && is_array( $manual_qa[ $i ] ) ? $manual_qa[ $i ] : array(); ?>
				<p>
					<label><?php echo esc_html( sprintf( __( 'Question #%d', 'storeguide-ai' ), $i + 1 ) ); ?><br>
						<input class="regular-text" type="text" name="storeguide_ai_faq_options[manual_qa][<?php echo esc_attr( (string) $i ); ?>][question]" value="<?php echo esc_attr( isset( $row['question'] ) ? (string) $row['question'] : '' ); ?>">
					</label>
				</p>
				<p>
					<label><?php echo esc_html__( 'Answer', 'storeguide-ai' ); ?><br>
						<textarea class="large-text" rows="3" name="storeguide_ai_faq_options[manual_qa][<?php echo esc_attr( (string) $i ); ?>][answer]"><?php echo esc_textarea( isset( $row['answer'] ) ? (string) $row['answer'] : '' ); ?></textarea>
					</label>
				</p>
				<hr />
			<?php endfor; ?>
		</div>

		<div class="storeguide-ai-card">
			<h3><?php echo esc_html__( 'Review latest questions and answers', 'storeguide-ai' ); ?></h3>
			<p><label><?php echo esc_html__( 'Review limit (latest pairs)', 'storeguide-ai' ); ?><br><input type="number" min="100" max="5000" name="storeguide_ai_faq_options[learning_review_limit]" value="<?php echo esc_attr( (string) $learning_limit ); ?>"></label></p>
			<p class="description"><?php echo esc_html__( 'Below you can suggest a better answer. It will be used as a preferred answer for exact repeat of the same question.', 'storeguide-ai' ); ?></p>
			<?php if ( empty( $recent_pairs ) ) : ?>
				<p><?php echo esc_html__( 'No Q&A pairs found yet.', 'storeguide-ai' ); ?></p>
			<?php else : ?>
				<?php foreach ( $recent_pairs as $idx => $pair ) : ?>
					<?php
					$q = isset( $pair['question'] ) ? (string) $pair['question'] : '';
					$a = isset( $pair['answer'] ) ? (string) $pair['answer'] : '';
					$key = md5( strtolower( trim( preg_replace( '/\s+/u', ' ', function_exists( 'remove_accents' ) ? remove_accents( $q ) : $q ) ) ) );
					$fix = isset( $suggested_fixes[ $key ] ) && is_array( $suggested_fixes[ $key ] ) ? $suggested_fixes[ $key ] : array();
					?>
					<div style="padding:10px;border:1px solid #dcdcde;border-radius:6px;margin:10px 0;">
						<p><strong><?php echo esc_html( sprintf( __( 'Q #%d', 'storeguide-ai' ), $idx + 1 ) ); ?></strong><br><?php echo esc_html( $q ); ?></p>
						<p><strong><?php echo esc_html__( 'Current answer', 'storeguide-ai' ); ?></strong><br><?php echo esc_html( $a ); ?></p>
						<input type="hidden" name="storeguide_ai_faq_options[suggested_fixes][<?php echo esc_attr( $key ); ?>][question]" value="<?php echo esc_attr( $q ); ?>">
						<p><label><?php echo esc_html__( 'Suggested better answer', 'storeguide-ai' ); ?><br>
							<textarea class="large-text" rows="3" name="storeguide_ai_faq_options[suggested_fixes][<?php echo esc_attr( $key ); ?>][answer]"><?php echo esc_textarea( isset( $fix['answer'] ) ? (string) $fix['answer'] : '' ); ?></textarea>
						</label></p>
						<p><label><input type="checkbox" name="storeguide_ai_faq_options[suggested_fixes][<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( ! empty( $fix['enabled'] ) ); ?>> <?php echo esc_html__( 'Use as preferred answer', 'storeguide-ai' ); ?></label></p>
						<p><label><input type="checkbox" name="storeguide_ai_faq_options[approve_to_manual][<?php echo esc_attr( $key ); ?>]" value="1"> <?php echo esc_html__( 'Approve and add to Manual FAQ', 'storeguide-ai' ); ?></label></p>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	private function sanitize_general( $input ) {
		return array(
			'enabled'        => isset( $input['enabled'] ) ? 1 : 0,
			'assistant_name' => isset( $input['assistant_name'] ) ? sanitize_text_field( $input['assistant_name'] ) : 'StoreGuide Assistant',
		);
	}

	private function sanitize_widget( $input ) {
		$existing_widget = get_option( 'storeguide_ai_widget_options', array() );
		$allowed_fields = array( 'thumbnail', 'name', 'price', 'link', 'availability' );
		$result_fields  = isset( $input['result_fields'] ) && is_array( $input['result_fields'] ) ? array_values( array_intersect( array_map( 'sanitize_key', $input['result_fields'] ), $allowed_fields ) ) : array( 'thumbnail', 'name', 'price', 'link' );
		if ( empty( $result_fields ) ) {
			$result_fields = array( 'name', 'price', 'link' );
		}

		return array(
			'position'              => isset( $input['position'] ) ? sanitize_key( $input['position'] ) : 'bottom-right',
			'welcome'               => isset( $input['welcome'] ) ? sanitize_text_field( $input['welcome'] ) : 'Hi! I can help you choose a product.',
			'placeholder'           => isset( $input['placeholder'] ) ? sanitize_text_field( $input['placeholder'] ) : 'What are you looking for?',
			'results_limit'         => isset( $input['results_limit'] ) ? max( 1, min( 20, absint( $input['results_limit'] ) ) ) : 5,
			'button_text'           => isset( $input['button_text'] ) ? sanitize_text_field( $input['button_text'] ) : 'Ask StoreGuide AI',
			'button_icon'           => isset( $input['button_icon'] ) ? sanitize_text_field( $input['button_icon'] ) : '💬',
			'button_bg_color'       => isset( $input['button_bg_color'] ) ? sanitize_hex_color( $input['button_bg_color'] ) : '#2271b1',
			'button_text_color'     => isset( $input['button_text_color'] ) ? sanitize_hex_color( $input['button_text_color'] ) : '#ffffff',
			'button_radius'         => isset( $input['button_radius'] ) ? max( 0, min( 40, absint( $input['button_radius'] ) ) ) : 20,
			'button_font_size'      => isset( $input['button_font_size'] ) ? max( 10, min( 30, absint( $input['button_font_size'] ) ) ) : 14,
			'send_button_text'      => isset( $input['send_button_text'] ) ? sanitize_text_field( $input['send_button_text'] ) : 'Send',
			'send_button_bg_color'  => isset( $input['send_button_bg_color'] ) ? sanitize_hex_color( $input['send_button_bg_color'] ) : '#2271b1',
			'send_button_text_color'=> isset( $input['send_button_text_color'] ) ? sanitize_hex_color( $input['send_button_text_color'] ) : '#ffffff',
			'send_button_radius'    => isset( $input['send_button_radius'] ) ? max( 0, min( 30, absint( $input['send_button_radius'] ) ) ) : 8,
			'send_button_font_size' => isset( $input['send_button_font_size'] ) ? max( 10, min( 24, absint( $input['send_button_font_size'] ) ) ) : 13,
			'custom_css'            => isset( $existing_widget['custom_css'] ) ? $this->sanitize_custom_css( (string) $existing_widget['custom_css'] ) : '',
			'chat_theme'            => isset( $input['chat_theme'] ) && in_array( $input['chat_theme'], array( 'light', 'dark' ), true ) ? $input['chat_theme'] : 'light',
			'result_fields'         => $result_fields,
			'show_related_products' => isset( $input['show_related_products'] ) ? 1 : 0,
		);
	}

	/**
	 * Sanitize custom CSS payload.
	 *
	 * @param string $css Raw CSS.
	 * @return string
	 */
	private function sanitize_custom_css( $css ) {
		$css = str_replace( array( "\r\n", "\r" ), "\n", (string) $css );
		$css = trim( $css );
		if ( '' === $css ) {
			return '';
		}
		if ( strlen( $css ) > 20000 ) {
			$css = substr( $css, 0, 20000 );
		}
		return wp_strip_all_tags( $css );
	}

	private function sanitize_persona( $input ) {
		return array(
			'role'      => isset( $input['role'] ) ? sanitize_text_field( $input['role'] ) : '',
			'tone'      => isset( $input['tone'] ) ? sanitize_text_field( $input['tone'] ) : '',
			'length'    => isset( $input['length'] ) ? sanitize_key( $input['length'] ) : 'short',
			'forbidden' => isset( $input['forbidden'] ) ? sanitize_textarea_field( $input['forbidden'] ) : '',
		);
	}

	private function sanitize_provider( $input ) {
		$provider      = isset( $input['provider'] ) ? sanitize_key( $input['provider'] ) : 'openai';
		$allowed_models = array_keys( $this->get_model_options( $provider ) );
		$selected_model = isset( $input['model'] ) ? sanitize_text_field( $input['model'] ) : $this->get_default_model_for_provider( $provider );
		if ( ! in_array( $selected_model, $allowed_models, true ) ) {
			$selected_model = $this->get_default_model_for_provider( $provider );
		}

		return array(
			'provider'    => $provider,
			'api_key'     => isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '',
			'base_url'    => isset( $input['base_url'] ) ? esc_url_raw( $input['base_url'] ) : '',
			'model'       => $selected_model,
			'temperature' => isset( $input['temperature'] ) ? (float) $input['temperature'] : 0.3,
			'max_tokens'  => isset( $input['max_tokens'] ) ? absint( $input['max_tokens'] ) : 800,
		);
	}

	/**
	 * Supported models by provider.
	 *
	 * @param string $provider Provider key.
	 * @return array<string, string>
	 */
	private function get_model_options( $provider ) {
		$options = array(
			'openai' => array(
				'gpt-4.1-mini' => 'GPT-4.1 Mini',
				'gpt-4.1'      => 'GPT-4.1',
				'gpt-4o-mini'  => 'GPT-4o Mini',
			),
			'openrouter' => array(
				'openai/gpt-4.1-mini'                => 'OpenAI GPT-4.1 Mini',
				'anthropic/claude-3.5-sonnet'        => 'Claude 3.5 Sonnet',
				'google/gemini-2.0-flash-001'        => 'Gemini 2.0 Flash',
			),
			'custom' => array(
				'gpt-4.1-mini' => 'gpt-4.1-mini',
				'gpt-4o-mini'  => 'gpt-4o-mini',
				'llama-3.1-70b-instruct' => 'llama-3.1-70b-instruct',
			),
		);

		return isset( $options[ $provider ] ) ? $options[ $provider ] : $options['openai'];
	}

	/**
	 * Default model for selected provider.
	 *
	 * @param string $provider Provider key.
	 * @return string
	 */
	private function get_default_model_for_provider( $provider ) {
		$defaults = array(
			'openai'     => 'gpt-4.1-mini',
			'openrouter' => 'openai/gpt-4.1-mini',
			'custom'     => 'gpt-4.1-mini',
		);

		return isset( $defaults[ $provider ] ) ? $defaults[ $provider ] : $defaults['openai'];
	}

	private function sanitize_index( $input ) {
		$sources = $this->parse_index_sources( isset( $input['sources'] ) ? $input['sources'] : array() );
		$acf_keys = isset( $input['acf_keys'] ) ? $this->sanitize_meta_keys_csv( (string) $input['acf_keys'] ) : '';
		$acf_auto_detect = isset( $input['acf_auto_detect'] ) ? 1 : 0;
		$content_meta_keys = isset( $input['content_meta_keys'] ) ? $this->sanitize_meta_keys_csv( (string) $input['content_meta_keys'] ) : '';
		return array(
			'autosync'          => isset( $input['autosync'] ) ? 1 : 0,
			'background_worker' => isset( $input['background_worker'] ) ? 1 : 0,
			'batch_size'        => isset( $input['batch_size'] ) ? max( 10, min( 1000, absint( $input['batch_size'] ) ) ) : 100,
			'sources'           => implode( ',', $sources ),
			'semantic_retrieval_enabled' => isset( $input['semantic_retrieval_enabled'] ) ? 1 : 0,
			'semantic_top_k'    => isset( $input['semantic_top_k'] ) ? max( 1, min( 20, absint( $input['semantic_top_k'] ) ) ) : 5,
			'pinecone_host'     => isset( $input['pinecone_host'] ) ? sanitize_text_field( (string) $input['pinecone_host'] ) : '',
			'pinecone_api_key'  => isset( $input['pinecone_api_key'] ) ? sanitize_text_field( (string) $input['pinecone_api_key'] ) : '',
			'pinecone_namespace'=> isset( $input['pinecone_namespace'] ) ? sanitize_key( (string) $input['pinecone_namespace'] ) : '',
			'embedding_model'   => isset( $input['embedding_model'] ) ? sanitize_text_field( (string) $input['embedding_model'] ) : 'text-embedding-3-small',
			'embedding_api_key' => isset( $input['embedding_api_key'] ) ? sanitize_text_field( (string) $input['embedding_api_key'] ) : '',
			'acf_enabled'       => isset( $input['acf_enabled'] ) ? 1 : 0,
			'acf_keys'          => $acf_keys,
			'acf_auto_detect'  => isset( $input['acf_auto_detect'] ) ? 1 : 0,
			'content_meta_enabled' => isset( $input['content_meta_enabled'] ) ? 1 : 0,
			'content_meta_keys'    => $content_meta_keys,
			'content_meta_auto_detect' => isset( $input['content_meta_auto_detect'] ) ? 1 : 0,
		);
	}

	/**
	 * Parse and validate index sources from input/options.
	 *
	 * @param mixed $sources Raw sources input.
	 * @return array<int, string>
	 */
	private function parse_index_sources( $sources ) {
		$allowed = array( 'products', 'pages', 'posts' );
		if ( is_string( $sources ) ) {
			$sources = explode( ',', $sources );
		}
		if ( ! is_array( $sources ) ) {
			return array( 'products' );
		}

		$parsed = array_values( array_intersect( array_map( 'sanitize_key', $sources ), $allowed ) );
		if ( empty( $parsed ) ) {
			return array( 'products' );
		}

		return $parsed;
	}

	/**
	 * Build indexing progress stats for admin UI.
	 *
	 * @param array<int, string>        $sources Enabled sources.
	 * @param array<string, mixed>      $progress Progress payload.
	 * @param array<string, mixed>      $status Status payload.
	 * @return array<string, mixed>
	 */
	private function get_index_progress_stats( $sources, $progress, $status ) {
		global $wpdb;
		$posts_table = $wpdb->posts;
		$products_total = in_array( 'products', $sources, true ) ? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$posts_table} WHERE post_type = %s AND post_status IN ('publish','private')", 'product' ) ) : 0;
		$pages_total    = in_array( 'pages', $sources, true ) ? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$posts_table} WHERE post_type = %s AND post_status IN ('publish','private')", 'page' ) ) : 0;
		$posts_total    = in_array( 'posts', $sources, true ) ? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$posts_table} WHERE post_type = %s AND post_status IN ('publish','private')", 'post' ) ) : 0;

		$products_processed = isset( $progress['products_last_id'] ) ? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$posts_table} WHERE post_type = %s AND post_status IN ('publish','private') AND ID <= %d", 'product', absint( $progress['products_last_id'] ) ) ) : 0;
		$pages_processed    = isset( $progress['pages_last_id'] ) ? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$posts_table} WHERE post_type = %s AND post_status IN ('publish','private') AND ID <= %d", 'page', absint( $progress['pages_last_id'] ) ) ) : 0;
		$posts_processed    = isset( $progress['posts_last_id'] ) ? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$posts_table} WHERE post_type = %s AND post_status IN ('publish','private') AND ID <= %d", 'post', absint( $progress['posts_last_id'] ) ) ) : 0;

		if ( empty( $progress ) ) {
			$products_processed = $products_total;
			$pages_processed    = $pages_total;
			$posts_processed    = $posts_total;
		}

		$total     = $products_total + $pages_total + $posts_total;
		$processed = min( $total, $products_processed + $pages_processed + $posts_processed );
		$percent   = $total > 0 ? ( ( $processed / $total ) * 100 ) : 0;
		$last_batch = isset( $status['last_batch'] ) ? absint( $status['last_batch'] ) : 0;
		$eta       = __( 'n/a', 'storeguide-ai' );

		$started = isset( $status['started_at'] ) ? absint( $status['started_at'] ) : 0;
		if ( $started > 0 && $processed > 0 && $total > $processed ) {
			$elapsed = max( 1, current_time( 'timestamp' ) - $started );
			$rate    = $processed / $elapsed;
			if ( $rate > 0 ) {
				$seconds_left = (int) round( ( $total - $processed ) / $rate );
				$eta          = human_time_diff( current_time( 'timestamp' ), current_time( 'timestamp' ) + $seconds_left );
			}
		}

		return array(
			'products_total'     => $products_total,
			'pages_total'        => $pages_total,
			'posts_total'        => $posts_total,
			'products_processed' => $products_processed,
			'pages_processed'    => $pages_processed,
			'posts_processed'    => $posts_processed,
			'total'              => $total,
			'processed'          => $processed,
			'percent'            => max( 0, min( 100, $percent ) ),
			'last_batch'         => $last_batch,
			'eta'                => $eta,
		);
	}

	/**
	 * Sanitize comma-separated meta keys.
	 *
	 * @param string $raw Raw input.
	 * @return string
	 */
	private function sanitize_meta_keys_csv( $raw ) {
		$parts = array_map( 'trim', explode( ',', $raw ) );
		$keys  = array();
		foreach ( $parts as $part ) {
			if ( '' === $part ) {
				continue;
			}
			$key = sanitize_key( $part );
			if ( '' === $key ) {
				continue;
			}
			$keys[] = $key;
		}
		$keys = array_values( array_unique( $keys ) );
		return implode( ',', $keys );
	}

	/**
	 * Detect candidate custom/meta keys from recent products.
	 *
	 * @param int $limit Max keys to return.
	 * @return array<int, string>
	 */
	private function detect_product_meta_keys( $limit = 40 ) {
		global $wpdb;
		$postmeta = $wpdb->postmeta;
		$posts    = $wpdb->posts;
		$rows     = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_key
				FROM {$postmeta} pm
				INNER JOIN {$posts} p ON p.ID = pm.post_id
				WHERE p.post_type = %s
				  AND p.post_status IN ('publish','private')
				  AND pm.meta_key NOT LIKE %s
				  AND pm.meta_key NOT LIKE %s
				  AND pm.meta_key NOT IN ('_price','_regular_price','_sale_price','_sku','_stock_status','_stock','_manage_stock','_visibility','_tax_status','_tax_class')
				ORDER BY pm.meta_key ASC
				LIMIT %d",
				'product',
				'\\_%',
				'attribute\\_%',
				max( 5, min( 200, absint( $limit ) ) )
			)
		);
		if ( ! is_array( $rows ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'strval', $rows ) ) );
	}

	/**
	 * Detect candidate meta keys from pages/posts.
	 *
	 * @param int $limit Max keys.
	 * @return array<int, string>
	 */
	private function detect_content_meta_keys( $limit = 40 ) {
		global $wpdb;
		$postmeta = $wpdb->postmeta;
		$posts    = $wpdb->posts;
		$rows     = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_key
				FROM {$postmeta} pm
				INNER JOIN {$posts} p ON p.ID = pm.post_id
				WHERE p.post_type IN ('post','page')
				  AND p.post_status IN ('publish','private')
				  AND pm.meta_key NOT LIKE %s
				ORDER BY pm.meta_key ASC
				LIMIT %d",
				'\\_%',
				max( 5, min( 200, absint( $limit ) ) )
			)
		);
		if ( ! is_array( $rows ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'strval', $rows ) ) );
	}

	private function sanitize_limits( $input ) {
		return array(
			'daily_requests'   => isset( $input['daily_requests'] ) ? absint( $input['daily_requests'] ) : 500,
			'monthly_requests' => isset( $input['monthly_requests'] ) ? absint( $input['monthly_requests'] ) : 10000,
			'daily_cost'       => isset( $input['daily_cost'] ) ? (float) $input['daily_cost'] : 10,
		);
	}

	private function sanitize_business( $input ) {
		return array(
			'store_name'         => isset( $input['store_name'] ) ? sanitize_text_field( $input['store_name'] ) : '',
			'store_url'          => isset( $input['store_url'] ) ? esc_url_raw( $input['store_url'] ) : '',
			'store_address'      => isset( $input['store_address'] ) ? sanitize_textarea_field( $input['store_address'] ) : '',
			'google_maps_url'    => isset( $input['google_maps_url'] ) ? esc_url_raw( $input['google_maps_url'] ) : '',
			'support_email'      => isset( $input['support_email'] ) ? sanitize_email( $input['support_email'] ) : '',
			'support_phone'      => isset( $input['support_phone'] ) ? sanitize_text_field( $input['support_phone'] ) : '',
			'shipping_countries' => isset( $input['shipping_countries'] ) ? sanitize_text_field( $input['shipping_countries'] ) : '',
			'company_description'=> isset( $input['company_description'] ) ? sanitize_textarea_field( $input['company_description'] ) : '',
			'company_legal_name' => isset( $input['company_legal_name'] ) ? sanitize_text_field( $input['company_legal_name'] ) : '',
			'tax_id'             => isset( $input['tax_id'] ) ? sanitize_text_field( $input['tax_id'] ) : '',
			'registry_number'    => isset( $input['registry_number'] ) ? sanitize_text_field( $input['registry_number'] ) : '',
			'contact_page_url'   => isset( $input['contact_page_url'] ) ? esc_url_raw( $input['contact_page_url'] ) : '',
			'privacy_policy_url' => isset( $input['privacy_policy_url'] ) ? esc_url_raw( $input['privacy_policy_url'] ) : '',
			'terms_page_url'     => isset( $input['terms_page_url'] ) ? esc_url_raw( $input['terms_page_url'] ) : '',
			'returns_page_url'   => isset( $input['returns_page_url'] ) ? esc_url_raw( $input['returns_page_url'] ) : '',
		);
	}

	private function sanitize_rules( $input ) {
		$coupon_recommendations = array();
		if ( isset( $input['coupon_recommendations'] ) && is_array( $input['coupon_recommendations'] ) ) {
			foreach ( $input['coupon_recommendations'] as $rule ) {
				if ( ! is_array( $rule ) ) {
					continue;
				}
				$coupon_id = isset( $rule['coupon_id'] ) ? absint( $rule['coupon_id'] ) : 0;
				if ( $coupon_id <= 0 ) {
					continue;
				}
				$start_date = isset( $rule['start_date'] ) ? sanitize_text_field( $rule['start_date'] ) : '';
				$end_date   = isset( $rule['end_date'] ) ? sanitize_text_field( $rule['end_date'] ) : '';
				$coupon_recommendations[] = array(
					'coupon_id'           => $coupon_id,
					'start_date'          => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ? $start_date : '',
					'end_date'            => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ? $end_date : '',
					'include_conditions'  => isset( $rule['include_conditions'] ) ? 1 : 0,
				);
			}
		}
		return array(
			'in_stock_only'      => isset( $input['in_stock_only'] ) ? 1 : 0,
			'excluded_categories'=> isset( $input['excluded_categories'] ) ? sanitize_text_field( $input['excluded_categories'] ) : '',
			'promoted_products'  => isset( $input['promoted_products'] ) ? sanitize_text_field( $input['promoted_products'] ) : '',
			'coupons_enabled'    => isset( $input['coupons_enabled'] ) ? 1 : 0,
			'coupon_ids'         => isset( $input['coupon_ids'] ) ? sanitize_text_field( $input['coupon_ids'] ) : '',
			'coupon_window_days' => isset( $input['coupon_window_days'] ) ? max( 0, min( 365, absint( $input['coupon_window_days'] ) ) ) : 30,
			'coupon_recommendations' => $coupon_recommendations,
		);
	}

	/**
	 * Coupon search results for select.
	 *
	 * @param string $term Query.
	 * @return array<int, array<string, string|int>>
	 */
	private function search_coupons_for_select( $term ) {
		global $wpdb;
		$posts_table = $wpdb->posts;
		$term        = trim( sanitize_text_field( (string) $term ) );
		$limit       = 20;
		$items       = array();

		if ( '' === $term ) {
			return $items;
		}

		if ( ctype_digit( $term ) ) {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT ID, post_title FROM {$posts_table} WHERE ID = %d AND post_type = %s LIMIT 1",
					(int) $term,
					'shop_coupon'
				),
				ARRAY_A
			);
			if ( is_array( $row ) ) {
				$items[] = array(
					'id'   => (int) $row['ID'],
					'text' => '' !== (string) $row['post_title'] ? (string) $row['post_title'] : (string) (int) $row['ID'],
				);
			}
		}

		$like = '%' . $wpdb->esc_like( $term ) . '%';
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title
				FROM {$posts_table}
				WHERE post_type = %s
				  AND post_status IN ('publish','private')
				  AND (post_title LIKE %s OR post_name LIKE %s)
				ORDER BY ID DESC
				LIMIT %d",
				'shop_coupon',
				$like,
				$like,
				$limit
			),
			ARRAY_A
		);

		foreach ( $rows as $row ) {
			$id = (int) $row['ID'];
			$exists = false;
			foreach ( $items as $item ) {
				if ( (int) $item['id'] === $id ) {
					$exists = true;
					break;
				}
			}
			if ( $exists ) {
				continue;
			}
			$items[] = array(
				'id'   => $id,
				'text' => '' !== (string) $row['post_title'] ? (string) $row['post_title'] : (string) $id,
			);
		}

		return $items;
	}

	private function sanitize_developer( $input ) {
		return array(
			'debug_enabled' => isset( $input['debug_enabled'] ) ? 1 : 0,
			'asset_version' => isset( $input['asset_version'] ) ? sanitize_text_field( $input['asset_version'] ) : STOREGUIDE_AI_VERSION,
		);
	}

	private function sanitize_optimization( $input ) {
		return array(
			'cache_enabled'     => isset( $input['cache_enabled'] ) ? 1 : 0,
			'cache_ttl_minutes' => isset( $input['cache_ttl_minutes'] ) ? max( 1, min( 10080, absint( $input['cache_ttl_minutes'] ) ) ) : 1440,
			'cache_max_entries' => isset( $input['cache_max_entries'] ) ? max( 50, min( 20000, absint( $input['cache_max_entries'] ) ) ) : 1000,
			'faq_enabled'       => isset( $input['faq_enabled'] ) ? 1 : 0,
			'faq_items'         => isset( $input['faq_items'] ) ? max( 1, min( 50, absint( $input['faq_items'] ) ) ) : 10,
			'learning_window'   => isset( $input['learning_window'] ) ? max( 100, min( 50000, absint( $input['learning_window'] ) ) ) : 1000,
		);
	}

	private function sanitize_faq( $input ) {
		$manual_qa = array();
		if ( isset( $input['manual_qa'] ) && is_array( $input['manual_qa'] ) ) {
			foreach ( $input['manual_qa'] as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$question = isset( $row['question'] ) ? sanitize_text_field( $row['question'] ) : '';
				$answer   = isset( $row['answer'] ) ? sanitize_textarea_field( $row['answer'] ) : '';
				if ( '' === $question || '' === $answer ) {
					continue;
				}
				$manual_qa[] = array(
					'question' => $question,
					'answer'   => $answer,
				);
			}
		}

		$suggested_fixes = array();
		if ( isset( $input['suggested_fixes'] ) && is_array( $input['suggested_fixes'] ) ) {
			foreach ( $input['suggested_fixes'] as $key => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$normalized_key = preg_replace( '/[^a-f0-9]/', '', strtolower( (string) $key ) );
				if ( 32 !== strlen( $normalized_key ) ) {
					continue;
				}
				$question = isset( $row['question'] ) ? sanitize_text_field( $row['question'] ) : '';
				$answer   = isset( $row['answer'] ) ? sanitize_textarea_field( $row['answer'] ) : '';
				if ( '' === $question || '' === $answer ) {
					continue;
				}
				$suggested_fixes[ $normalized_key ] = array(
					'question' => $question,
					'answer'   => $answer,
					'enabled'  => isset( $row['enabled'] ) ? 1 : 0,
				);
			}
		}

		if ( isset( $input['approve_to_manual'] ) && is_array( $input['approve_to_manual'] ) ) {
			foreach ( $input['approve_to_manual'] as $key => $enabled ) {
				$normalized_key = preg_replace( '/[^a-f0-9]/', '', strtolower( (string) $key ) );
				if ( empty( $enabled ) || ! isset( $suggested_fixes[ $normalized_key ] ) ) {
					continue;
				}
				$question = isset( $suggested_fixes[ $normalized_key ]['question'] ) ? (string) $suggested_fixes[ $normalized_key ]['question'] : '';
				$answer   = isset( $suggested_fixes[ $normalized_key ]['answer'] ) ? (string) $suggested_fixes[ $normalized_key ]['answer'] : '';
				if ( '' === $question || '' === $answer ) {
					continue;
				}
				$exists = false;
				foreach ( $manual_qa as $entry ) {
					if ( isset( $entry['question'] ) && strtolower( (string) $entry['question'] ) === strtolower( $question ) ) {
						$exists = true;
						break;
					}
				}
				if ( ! $exists ) {
					$manual_qa[] = array(
						'question' => $question,
						'answer'   => $answer,
					);
				}
			}
		}

		return array(
			'enabled'               => isset( $input['enabled'] ) ? 1 : 0,
			'manual_qa'             => array_slice( $manual_qa, 0, 100 ),
			'suggested_fixes'       => $suggested_fixes,
			'learning_review_limit' => isset( $input['learning_review_limit'] ) ? max( 100, min( 5000, absint( $input['learning_review_limit'] ) ) ) : 1000,
		);
	}

	/**
	 * Build recent Q&A pairs from chat history.
	 *
	 * @param int $limit Pair limit.
	 * @return array<int, array<string, string>>
	 */
	private function get_recent_qa_pairs( $limit = 1000 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'storeguide_ai_messages';
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT conversation_id, role, message_text
				FROM {$table}
				WHERE role IN ('user','assistant')
				ORDER BY id DESC
				LIMIT %d",
				max( 200, min( 20000, absint( $limit ) * 2 ) )
			),
			ARRAY_A
		);
		if ( empty( $rows ) ) {
			return array();
		}

		$rows    = array_reverse( $rows );
		$pending = array();
		$pairs   = array();
		foreach ( $rows as $row ) {
			$cid  = isset( $row['conversation_id'] ) ? (int) $row['conversation_id'] : 0;
			$role = isset( $row['role'] ) ? (string) $row['role'] : '';
			$text = isset( $row['message_text'] ) ? (string) $row['message_text'] : '';
			if ( $cid <= 0 || '' === $text ) {
				continue;
			}
			if ( 'user' === $role ) {
				$pending[ $cid ] = $text;
				continue;
			}
			if ( 'assistant' === $role && isset( $pending[ $cid ] ) ) {
				$pairs[] = array(
					'question' => $pending[ $cid ],
					'answer'   => $text,
				);
				unset( $pending[ $cid ] );
			}
		}
		if ( empty( $pairs ) ) {
			return array();
		}
		$pairs = array_reverse( $pairs );
		return array_slice( $pairs, 0, max( 100, min( 5000, absint( $limit ) ) ) );
	}

	/**
	 * Perform provider connection test.
	 *
	 * @return void
	 */
	private function test_provider_connection() {
		$options  = get_option( 'storeguide_ai_provider_options', array() );
		$provider = isset( $options['provider'] ) ? sanitize_key( $options['provider'] ) : 'openai';
		$api_key  = isset( $options['api_key'] ) ? sanitize_text_field( $options['api_key'] ) : '';
		$base_url = isset( $options['base_url'] ) ? esc_url_raw( $options['base_url'] ) : '';

		if ( '' === $api_key ) {
			$this->redirect_with_notice(
				array(
					'tab'                => 'providers',
					'connection_test'    => 'error',
					'connection_message' => __( 'API key is empty. Please save your API key first.', 'storeguide-ai' ),
				)
			);
		}

		$url = $this->get_connection_test_url( $provider, $base_url );
		if ( '' === $url ) {
			$this->redirect_with_notice(
				array(
					'tab'                => 'providers',
					'connection_test'    => 'error',
					'connection_message' => __( 'Invalid provider or Base URL configuration.', 'storeguide-ai' ),
				)
			);
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => $this->get_connection_test_headers( $provider, $api_key ),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->redirect_with_notice(
				array(
					'tab'                => 'providers',
					'connection_test'    => 'error',
					'connection_message' => sprintf(
						/* translators: %s is WP error message */
						__( 'Connection failed: %s', 'storeguide-ai' ),
						$response->get_error_message()
					),
				)
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code >= 200 && $code < 300 ) {
			$this->redirect_with_notice(
				array(
					'tab'                => 'providers',
					'connection_test'    => 'success',
					'connection_message' => __( 'Connection successful. Provider API is reachable.', 'storeguide-ai' ),
				)
			);
		}

		$this->redirect_with_notice(
			array(
				'tab'                => 'providers',
				'connection_test'    => 'error',
				'connection_message' => sprintf(
					/* translators: %d is HTTP status code */
					__( 'Connection failed. HTTP status: %d', 'storeguide-ai' ),
					$code
				),
			)
		);
	}

	/**
	 * Build test URL based on provider.
	 *
	 * @param string $provider Provider.
	 * @param string $base_url Base URL.
	 * @return string
	 */
	private function get_connection_test_url( $provider, $base_url ) {
		if ( 'openrouter' === $provider ) {
			return 'https://openrouter.ai/api/v1/models';
		}

		if ( 'custom' === $provider ) {
			$trimmed = rtrim( $base_url, '/' );
			return '' !== $trimmed ? $trimmed . '/models' : '';
		}

		return 'https://api.openai.com/v1/models';
	}

	/**
	 * Build request headers for connection test.
	 *
	 * @param string $provider Provider.
	 * @param string $api_key API key.
	 * @return array<string, string>
	 */
	private function get_connection_test_headers( $provider, $api_key ) {
		$headers = array(
			'Authorization' => 'Bearer ' . $api_key,
			'Content-Type'  => 'application/json',
		);

		if ( 'openrouter' === $provider ) {
			$headers['HTTP-Referer'] = home_url();
			$headers['X-Title']      = 'StoreGuide AI';
		}

		return $headers;
	}
}
