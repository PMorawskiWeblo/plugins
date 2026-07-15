<?php
/**
 * Form builder documentation tab.
 *
 * @package FastForms
 *
 * @var \WP_Post $post Edytowany formularz.
 */

use Weblo\FastForms\FormBuilder\BuilderI18n;
use Weblo\FastForms\FormBuilder\FormSchemaStorage;
use Weblo\FastForms\Frontend\ShortcodeAttributes;
use Weblo\FastForms\FormBuilder\RestApi;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$form_id           = (int) $post->ID;
$form_schema       = FormSchemaStorage::get( $form_id );
$form_opts         = $form_settings['form'] ?? array();
$shortcode_inline  = ShortcodeAttributes::build( $form_id, 'inline', $form_opts );
$shortcode_button  = ShortcodeAttributes::build( $form_id, 'button', $form_opts );
$shortcode_trigger = ShortcodeAttributes::build( $form_id, 'trigger', $form_opts );
$submit_rest_url   = rest_url( RestApi::NAMESPACE . '/forms/' . $form_id . '/submit' );
$merge_tags_html   = BuilderI18n::merge_tags_list_html( $form_id, $form_schema );
?>
<div id="ff-tab-documentation" class="ff-builder-tab-panel ff-settings-panel ff-docs-panel">
    <div class="ff-docs">
        <section class="ff-docs__section">
            <h2><?php esc_html_e( 'Displaying the form', 'fast-forms' ); ?></h2>
            <p><?php esc_html_e( 'Paste a shortcode into a page, post, widget, or template. This form’s ready-to-copy shortcodes are also available under Settings → Shortcodes.', 'fast-forms' ); ?>
            </p>

            <h3><?php esc_html_e( 'Inline form', 'fast-forms' ); ?></h3>
            <p><?php esc_html_e( 'Renders the full form directly on the page.', 'fast-forms' ); ?></p>
            <pre class="ff-docs__code"><code><?php echo esc_html( $shortcode_inline ); ?></code></pre>

            <h3><?php esc_html_e( 'Button with modal', 'fast-forms' ); ?></h3>
            <p><?php esc_html_e( 'Shows a button that opens the form in a modal window.', 'fast-forms' ); ?></p>
            <pre class="ff-docs__code"><code><?php echo esc_html( $shortcode_button ); ?></code></pre>

            <h3><?php esc_html_e( 'CSS trigger (modal)', 'fast-forms' ); ?></h3>
            <p><?php esc_html_e( 'Opens the form in a modal when a matching element on the page is clicked.', 'fast-forms' ); ?>
            </p>
            <pre class="ff-docs__code"><code><?php echo esc_html( $shortcode_trigger ); ?></code></pre>
            <p class="description"><?php esc_html_e( 'Example HTML trigger:', 'fast-forms' ); ?>
                <code>&lt;a href="#" class="open-form-<?php echo esc_html( (string) $form_id ); ?>"&gt;<?php esc_html_e( 'Contact us', 'fast-forms' ); ?>&lt;/a&gt;</code>
            </p>
        </section>

        <section class="ff-docs__section">
            <h2><?php esc_html_e( 'Shortcode attributes', 'fast-forms' ); ?></h2>
            <table class="widefat striped ff-docs__table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Attribute', 'fast-forms' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'fast-forms' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>id</code></td>
                        <td><?php esc_html_e( 'Form ID (required).', 'fast-forms' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>display</code></td>
                        <td><?php esc_html_e( 'Display mode: inline, button, or trigger.', 'fast-forms' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>button_text</code></td>
                        <td><?php esc_html_e( 'Modal button label when display="button".', 'fast-forms' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>button_class</code></td>
                        <td><?php esc_html_e( 'CSS classes for the modal button.', 'fast-forms' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>trigger</code></td>
                        <td><?php esc_html_e( 'CSS selector of the element that opens the modal when display="trigger".', 'fast-forms' ); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="ff-docs__section">
            <h2><?php esc_html_e( 'Email merge tags', 'fast-forms' ); ?></h2>
            <p><?php esc_html_e( 'Use these placeholders in admin/user email subject and body (Email tab).', 'fast-forms' ); ?>
            </p>
            <?php echo $merge_tags_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </section>

        <section class="ff-docs__section">
            <h2><?php esc_html_e( 'Frontend JavaScript event', 'fast-forms' ); ?></h2>
            <p><?php esc_html_e( 'After a successful AJAX submit, the form element triggers a jQuery event you can listen to:', 'fast-forms' ); ?>
            </p>
            <pre class="ff-docs__code"><code>jQuery( document ).on( 'ff:submitted', '.ff-form', function ( event, data ) {
	// data.entryId, data.message, data.redirect, …
} );</code></pre>
        </section>

        <section class="ff-docs__section">
            <h2><?php esc_html_e( 'Developer hooks', 'fast-forms' ); ?></h2>
            <p><?php esc_html_e( 'Use WordPress actions and filters to extend Fast Forms — for example CRM integrations (Salesmanago, Mailchimp), webhooks, or custom processing after submit.', 'fast-forms' ); ?>
            </p>

            <h3><?php esc_html_e( 'After successful submit', 'fast-forms' ); ?></h3>
            <pre class="ff-docs__code"><code>add_action( 'ff_form_submitted', function ( $form_id, $entry_id, $payload, $schema, $response ) {
	// Send $payload to an external API when $form_id matches.
}, 10, 5 );</code></pre>
            <p class="description">
                <?php esc_html_e( 'When “Do not save submission to database” is enabled, $entry_id is 0 but emails and this hook still run.', 'fast-forms' ); ?>
            </p>

            <h3><?php esc_html_e( 'Other filters', 'fast-forms' ); ?></h3>
            <ul class="ff-docs__list">
                <li><code>ff_enable_honeypot</code> —
                    <?php esc_html_e( 'Enable/disable the honeypot field.', 'fast-forms' ); ?></li>
                <li><code>ff_max_submissions_per_hour</code> —
                    <?php esc_html_e( 'Rate limit per form and IP.', 'fast-forms' ); ?></li>
                <li><code>ff_max_upload_kb</code> —
                    <?php esc_html_e( 'Maximum upload file size in KB.', 'fast-forms' ); ?></li>
                <li><code>ff_allowed_upload_mimes</code> —
                    <?php esc_html_e( 'Allowed MIME types for file fields.', 'fast-forms' ); ?></li>
            </ul>
        </section>

        <section class="ff-docs__section">
            <h2><?php esc_html_e( 'REST API submit endpoint', 'fast-forms' ); ?></h2>
            <p><?php esc_html_e( 'The frontend submits forms via REST (same endpoint for custom integrations):', 'fast-forms' ); ?>
            </p>
            <pre class="ff-docs__code"><code>POST <?php echo esc_html( $submit_rest_url ); ?>

Content-Type: multipart/form-data
X-WP-Nonce: &lt;wp_rest nonce&gt;
ff_form_nonce: &lt;token from the form&gt;</code></pre>
        </section>

        <section class="ff-docs__section">
            <h2><?php esc_html_e( 'Submissions and SEO', 'fast-forms' ); ?></h2>
            <p><?php esc_html_e( 'Form submissions are stored as private entries. They are not public, not indexed by search engines, and return 404 if accessed directly on the front end. Only logged-in users with permission can view them in the admin.', 'fast-forms' ); ?>
            </p>
        </section>


    </div>
</div>