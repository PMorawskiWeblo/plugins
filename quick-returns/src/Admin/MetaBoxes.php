<?php

namespace Weblo\QuickReturns\Admin;

use Weblo\QuickReturns\Infrastructure\Email\StatusChangeNotifier;
use Weblo\QuickReturns\Infrastructure\PostType\ReturnRequestPostType;

class MetaBoxes {

	public function register_hooks(): void {
		add_action( 'add_meta_boxes', [ $this, 'add_boxes' ] );
		add_action( 'save_post_' . ReturnRequestPostType::POST_TYPE, [ $this, 'save' ], 10, 2 );
	}

	public function add_boxes(): void {
		add_meta_box(
			'qr_return_details',
			__( 'Return Request Details', 'quick-returns' ),
			[ $this, 'render_details' ],
			ReturnRequestPostType::POST_TYPE,
			'normal',
			'high'
		);
	}

	public function render_details( \WP_Post $post ): void {
		wp_nonce_field( 'qr_save_meta', 'qr_meta_nonce' );

		$order_id    = get_post_meta( $post->ID, '_qr_order_id', true );
		$status      = get_post_meta( $post->ID, '_qr_status', true ) ?: 'new';
		$email       = get_post_meta( $post->ID, '_qr_customer_email', true );
		$items       = get_post_meta( $post->ID, '_qr_items', true );
		$totals      = get_post_meta( $post->ID, '_qr_totals', true );
		$admin_note  = get_post_meta( $post->ID, '_qr_admin_note', true );
		$submitted   = get_post_meta( $post->ID, '_qr_submitted_at', true );
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Order', 'quick-returns' ); ?></th>
				<td>
					<?php
					$order = wc_get_order( $order_id );
					if ( $order ) {
						printf(
							'<a href="%s">#%s</a>',
							esc_url( $order->get_edit_order_url() ),
							esc_html( $order->get_order_number() )
						);
					} else {
						echo esc_html( $order_id );
					}
					?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Customer email', 'quick-returns' ); ?></th>
				<td><?php echo esc_html( $email ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Submitted at', 'quick-returns' ); ?></th>
				<td><?php echo esc_html( $submitted ); ?></td>
			</tr>
			<tr>
				<th><label for="qr_status"><?php esc_html_e( 'Status', 'quick-returns' ); ?></label></th>
				<td>
					<select name="qr_status" id="qr_status">
						<?php foreach ( ReturnRequestPostType::statuses() as $s ) : ?>
							<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $status, $s ); ?>>
								<?php echo esc_html( ReturnRequestPostType::status_label( $s ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="qr_admin_note"><?php esc_html_e( 'Admin note', 'quick-returns' ); ?></label></th>
				<td>
					<textarea name="qr_admin_note" id="qr_admin_note" rows="4" class="large-text"><?php echo esc_textarea( $admin_note ); ?></textarea>
				</td>
			</tr>
		</table>

		<?php if ( is_array( $items ) && ! empty( $items ) ) : ?>
			<h4><?php esc_html_e( 'Items', 'quick-returns' ); ?></h4>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Product', 'quick-returns' ); ?></th>
						<th><?php esc_html_e( 'Qty', 'quick-returns' ); ?></th>
						<th><?php esc_html_e( 'Reason', 'quick-returns' ); ?></th>
						<th><?php esc_html_e( 'Comment', 'quick-returns' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $items as $item ) : ?>
						<tr>
							<td><?php echo esc_html( $item['name'] ?? '' ); ?></td>
							<td><?php echo esc_html( $item['quantity'] ?? '' ); ?></td>
							<td><?php echo esc_html( $item['reason'] ?? '' ); ?></td>
							<td><?php echo esc_html( $item['comment'] ?? '' ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if ( is_array( $totals ) ) : ?>
			<p>
				<strong><?php esc_html_e( 'Estimated total:', 'quick-returns' ); ?></strong>
				<?php echo esc_html( \Weblo\QuickReturns\Support\Helpers::format_price( (float) ( $totals['estimated'] ?? 0 ) ) ); ?>
			</p>
		<?php endif; ?>
		<?php
	}

	public function save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['qr_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qr_meta_nonce'] ) ), 'qr_save_meta' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$old_status = get_post_meta( $post_id, '_qr_status', true ) ?: 'new';

		if ( isset( $_POST['qr_admin_note'] ) ) {
			update_post_meta( $post_id, '_qr_admin_note', sanitize_textarea_field( wp_unslash( $_POST['qr_admin_note'] ) ) );
		}

		if ( ! isset( $_POST['qr_status'] ) ) {
			return;
		}

		$new_status = sanitize_text_field( wp_unslash( $_POST['qr_status'] ) );

		if ( ! in_array( $new_status, ReturnRequestPostType::statuses(), true ) ) {
			return;
		}

		update_post_meta( $post_id, '_qr_status', $new_status );

		StatusChangeNotifier::maybe_send( $post_id, $old_status, $new_status );
	}
}
