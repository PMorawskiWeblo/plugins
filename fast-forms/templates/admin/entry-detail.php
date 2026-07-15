<?php
/**
 * Entry detail admin template.
 *
 * @package FastForms
 *
 * @var \WP_Post $post
 * @var array<int, array{label: string, value: string, type: string}> $rows
 * @var array<string, string> $meta
 * @var string               $current_status
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status_options = \Weblo\FastForms\Admin\EntryAdmin::get_status_options();
if ( '' === $current_status ) {
	$current_status = 'new';
}
?>
<div class="ff-entry-detail">
    <div class="ff-entry-detail__status">
        <h2><?php esc_html_e( 'Submission status', 'fast-forms' ); ?></h2>
        <p>
            <label for="ff_entry_status"
                class="screen-reader-text"><?php esc_html_e( 'Submission status', 'fast-forms' ); ?></label>
            <select name="ff_entry_status" id="ff_entry_status">
                <?php foreach ( $status_options as $value => $label ) : ?>
                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_status, $value ); ?>>
                    <?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php wp_nonce_field( 'ff_save_entry_status_' . $post->ID, 'ff_entry_status_nonce' ); ?>
        <p class="description">
            <?php esc_html_e( 'Save the entry to update the status (click “Update” above).', 'fast-forms' ); ?></p>
    </div>

    <div class="ff-entry-detail__meta">
        <h2><?php esc_html_e( 'Summary', 'fast-forms' ); ?></h2>
        <table class="widefat striped ff-entry-detail__table">
            <tbody>
                <?php foreach ( $meta as $label => $value ) : ?>
                <tr>
                    <th scope="row"><?php echo esc_html( $label ); ?></th>
                    <td><?php echo esc_html( $value ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="ff-entry-detail__answers">
        <h2><?php esc_html_e( 'Answers', 'fast-forms' ); ?></h2>

        <?php if ( empty( $rows ) ) : ?>
        <p class="description"><?php esc_html_e( 'No saved answers.', 'fast-forms' ); ?></p>
        <?php else : ?>
        <table class="widefat striped ff-entry-detail__table">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e( 'Field', 'fast-forms' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Answer', 'fast-forms' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $rows as $row ) : ?>
                <tr>
                    <th scope="row"
                        class="ff-entry-detail__field-label ff-entry-detail__field-label_<?php echo esc_attr( $row['type'] ); ?>">
                        <?php echo esc_html( $row['label'] ); ?></th>
                    <td><?php echo wp_kses_post( $row['value'] ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>