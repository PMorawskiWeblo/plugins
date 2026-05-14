<?php
/**
 * Background index worker (WP-Cron).
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Index_Worker {
	const EVENT_HOOK      = 'storeguide_ai_run_index_batch';
	const PROGRESS_OPTION = 'storeguide_ai_index_progress';
	const STATUS_OPTION   = 'storeguide_ai_index_status';
	const LOCK_TRANSIENT  = 'storeguide_ai_index_worker_lock';

	/**
	 * Index builder.
	 *
	 * @var StoreGuide_AI_Index_Builder
	 */
	private $builder;

	/**
	 * Constructor.
	 *
	 * @param StoreGuide_AI_Index_Builder $builder Builder.
	 */
	public function __construct( $builder ) {
		$this->builder = $builder;
	}

	/**
	 * Register cron hooks.
	 *
	 * @param StoreGuide_AI_Loader $loader Hook loader.
	 * @return void
	 */
	public function register( $loader ) {
		$loader->add_filter( 'cron_schedules', $this, 'add_cron_schedules' );
		$loader->add_action( 'init', $this, 'ensure_schedule' );
		$loader->add_action( self::EVENT_HOOK, $this, 'run_scheduled_batch' );
	}

	/**
	 * Add custom schedule for index worker.
	 *
	 * @param array<string, array<string, mixed>> $schedules Schedules.
	 * @return array<string, array<string, mixed>>
	 */
	public function add_cron_schedules( $schedules ) {
		if ( ! isset( $schedules['storeguide_ai_5min'] ) ) {
			$schedules['storeguide_ai_5min'] = array(
				'interval' => 5 * MINUTE_IN_SECONDS,
				'display'  => __( 'StoreGuide AI every 5 minutes', 'storeguide-ai' ),
			);
		}
		return $schedules;
	}

	/**
	 * Ensure recurring worker event exists when enabled.
	 *
	 * @return void
	 */
	public function ensure_schedule() {
		$index_options = get_option( 'storeguide_ai_index_options', array() );
		$enabled       = ! empty( $index_options['background_worker'] );
		$next          = wp_next_scheduled( self::EVENT_HOOK );

		if ( $enabled && ! $next ) {
			wp_schedule_event( time() + 60, 'storeguide_ai_5min', self::EVENT_HOOK );
		}

		if ( ! $enabled && $next ) {
			wp_unschedule_event( $next, self::EVENT_HOOK );
		}
	}

	/**
	 * Process one background index batch.
	 *
	 * @return void
	 */
	public function run_scheduled_batch() {
		if ( get_transient( self::LOCK_TRANSIENT ) ) {
			return;
		}
		set_transient( self::LOCK_TRANSIENT, 1, 240 );

		$index_options = get_option( 'storeguide_ai_index_options', array() );
		if ( empty( $index_options['background_worker'] ) ) {
			delete_transient( self::LOCK_TRANSIENT );
			return;
		}

		$sources    = $this->parse_index_sources( isset( $index_options['sources'] ) ? $index_options['sources'] : array() );
		$batch_size = isset( $index_options['batch_size'] ) ? max( 10, min( 2000, absint( $index_options['batch_size'] ) ) ) : 300;
		$progress   = get_option(
			self::PROGRESS_OPTION,
			array(
				'products_last_id' => 0,
				'pages_last_id'    => 0,
				'posts_last_id'    => 0,
				'total_processed'  => 0,
			)
		);
		$status = get_option(
			self::STATUS_OPTION,
			array(
				'is_running'      => 0,
				'started_at'      => current_time( 'timestamp' ),
				'last_run_at'     => 0,
				'last_batch'      => 0,
				'last_error'      => '',
				'completed_at'    => 0,
			)
		);

		$processed_now = 0;
		$has_more      = false;

		if ( in_array( 'products', $sources, true ) ) {
			$product_chunk                 = $this->builder->index_products_chunk( $batch_size, isset( $progress['products_last_id'] ) ? absint( $progress['products_last_id'] ) : 0 );
			$progress['products_last_id'] = (int) $product_chunk['last_id'];
			$processed_now               += (int) $product_chunk['processed'];
			$has_more                     = $has_more || ! empty( $product_chunk['has_more'] );
		}
		if ( in_array( 'pages', $sources, true ) ) {
			$page_chunk                 = $this->builder->index_content_chunk( 'page', $batch_size, isset( $progress['pages_last_id'] ) ? absint( $progress['pages_last_id'] ) : 0 );
			$progress['pages_last_id'] = (int) $page_chunk['last_id'];
			$processed_now            += (int) $page_chunk['processed'];
			$has_more                  = $has_more || ! empty( $page_chunk['has_more'] );
		}
		if ( in_array( 'posts', $sources, true ) ) {
			$post_chunk                 = $this->builder->index_content_chunk( 'post', $batch_size, isset( $progress['posts_last_id'] ) ? absint( $progress['posts_last_id'] ) : 0 );
			$progress['posts_last_id'] = (int) $post_chunk['last_id'];
			$processed_now            += (int) $post_chunk['processed'];
			$has_more                  = $has_more || ! empty( $post_chunk['has_more'] );
		}

		$progress['total_processed'] = isset( $progress['total_processed'] ) ? absint( $progress['total_processed'] ) + $processed_now : $processed_now;
		$status['is_running']        = $has_more ? 1 : 0;
		$status['last_run_at']       = current_time( 'timestamp' );
		$status['last_batch']        = $processed_now;
		$status['last_error']        = '';
		if ( empty( $status['started_at'] ) ) {
			$status['started_at'] = current_time( 'timestamp' );
		}

		try {
			if ( $has_more ) {
				update_option( self::PROGRESS_OPTION, $progress );
			} else {
				delete_option( self::PROGRESS_OPTION );
				delete_option( 'storeguide_ai_qa_cache' );
				$status['completed_at'] = current_time( 'timestamp' );
			}
			update_option( self::STATUS_OPTION, $status );
		} finally {
			delete_transient( self::LOCK_TRANSIENT );
		}
	}

	/**
	 * Parse index sources.
	 *
	 * @param mixed $raw Raw sources.
	 * @return array<int, string>
	 */
	private function parse_index_sources( $raw ) {
		$allowed = array( 'products', 'pages', 'posts' );
		if ( is_array( $raw ) ) {
			$sources = array_values( array_intersect( array_map( 'sanitize_key', $raw ), $allowed ) );
		} else {
			$sources = array_values( array_intersect( array_map( 'sanitize_key', array_map( 'trim', explode( ',', (string) $raw ) ) ), $allowed ) );
		}
		return empty( $sources ) ? array( 'products' ) : $sources;
	}
}

