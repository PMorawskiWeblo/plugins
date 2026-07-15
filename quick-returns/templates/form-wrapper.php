<?php
/**
 * Form wrapper template.
 *
 * @var string $context
 * @var int    $initial_step
 * @var int    $order_id
 * @var string $selection_mode
 * @var string $email
 */

defined( 'ABSPATH' ) || exit;

$settings = \Weblo\QuickReturns\Infrastructure\Repository\SettingsRepository::get_all();
$email    = $email ?? '';
?>
<div
	class="qr-form"
	data-qr-form="1"
	data-qr-context="<?php echo esc_attr( $context ); ?>"
	data-qr-initial-step="<?php echo esc_attr( (string) $initial_step ); ?>"
	data-qr-order-id="<?php echo esc_attr( (string) $order_id ); ?>"
	data-qr-email="<?php echo esc_attr( $email ); ?>"
	data-qr-mode="<?php echo esc_attr( $selection_mode ); ?>"
>
	<div class="qr-card">
		<div class="qr-card__header">
			<h2 class="qr-card__title"><?php esc_html_e( 'Return request', 'quick-returns' ); ?></h2>
			<p class="qr-card__subtitle"><?php esc_html_e( 'Fill out the form so we can process your return.', 'quick-returns' ); ?></p>
		</div>
		<div class="qr-card__body">
			<div class="qr-app" aria-live="polite"></div>
		</div>
	</div>
</div>
