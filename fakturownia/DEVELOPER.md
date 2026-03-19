# Fakturownia by Weblo - Developer Documentation

## 1. Purpose and Scope

This plugin integrates WooCommerce with Fakturownia:

- creates invoices from WooCommerce orders,
- creates correction invoices from WooCommerce refunds,
- supports manual actions in order metabox,
- supports bulk operations in WooCommerce integration settings,
- provides logs, debug tools, and translation-ready UI.

Main file: `fakturownia.php`

---

## 2. High-Level Architecture

### Main bootstrap

File: `fakturownia.php`

Key responsibilities:

- plugin initialization and class loading,
- singleton `Weblo_Fakturownia_Plugin`,
- central settings access (`woocommerce_weblo_fakturownia_settings`),
- API client factory (`get_api_client()`),
- auto invoice/correction hooks,
- order list column + filters,
- public signed PDF download endpoint,
- shortcode `[weblo_fakturownia_invoice_pdf]`,
- custom DB error logging (`wp_weblo_fakturownia_logs`).

### Integration settings class

File: `includes/class-wc-integration-weblo-fakturownia.php`

Key responsibilities:

- WooCommerce Integration tab UI (`WC_Integration`),
- tabs: Connection, Invoices, Corrections, Bulk operations, Logs,
- settings fields definitions and rendering,
- AJAX endpoints:
  - connection test,
  - bulk invoices,
  - bulk corrections,
  - fetch DB logs,
  - clear debug file,
  - clear DB logs.

### API client

File: `includes/class-weblo-fakturownia-api-client.php`

Key responsibilities:

- low-level request wrapper (`request()`),
- `test_connection()`,
- `get_invoice()`,
- `create_invoice()`,
- `create_correction()`,
- `send_invoice_by_email()`,
- `download_invoice_pdf()`.

### Order metabox

File: `includes/class-weblo-fakturownia-order-metabox.php`

Key responsibilities:

- displays invoice/correction status on order edit screen,
- manual actions (issue invoice/correction, send mail, download PDF),
- AJAX handlers for metabox actions,
- user-friendly error rendering above raw API error JSON.

---

## 3. Data Model

### WooCommerce option

All settings stored in:

- `woocommerce_weblo_fakturownia_settings`

Important setting keys:

- `weblo_fakturownia_domain`
- `weblo_fakturownia_api_token`
- `weblo_fakturownia_department_id`
- `weblo_fakturownia_connection_ok`
- `weblo_fakturownia_auto_issue_invoices`
- `weblo_fakturownia_invoice_statuses`
- `weblo_fakturownia_invoice_date_source`
- `weblo_fakturownia_auto_issue_corrections`
- `weblo_fakturownia_correction_mode` (`difference` or `full`)
- `weblo_fakturownia_send_from_woocommerce`
- `weblo_fakturownia_send_corrections_from_woocommerce`
- `weblo_fakturownia_debug_logging_enabled`

### Order meta

Invoice:

- `_weblo_fakturownia_invoice_id`
- `_weblo_fakturownia_invoice_number`
- `_weblo_fakturownia_last_error`

Correction:

- `_weblo_fakturownia_correction_id`
- `_weblo_fakturownia_correction_number`
- `_weblo_fakturownia_correction_last_error`

Bulk helper:

- `_weblo_fakturownia_bulk_correction_skip`

### Custom DB table

Error logs table:

- `{$wpdb->prefix}weblo_fakturownia_logs`

Columns:

- `id`
- `order_id`
- `type`
- `error`
- `created_at`

---

## 4. Main Runtime Flows

### A) Manual invoice from metabox

1. User clicks "Issue invoice now".
2. AJAX `weblo_issue_invoice_now`.
3. `create_invoice()` in API client.
4. Save invoice meta to order.
5. Return refreshed metabox HTML.

### B) Auto invoice on status change

Hook:

- `woocommerce_order_status_changed`

Method:

- `Weblo_Fakturownia_Plugin::maybe_auto_issue_invoice()`

Checks:

- connection OK,
- auto issue enabled,
- status matches configured statuses,
- no existing invoice.

### C) Manual correction from metabox

1. User clicks "Issue correction now".
2. AJAX `weblo_issue_correction_now`.
3. Finds latest refund for order.
4. Calls `create_correction()`.
5. Saves correction meta and refreshes metabox.

### D) Auto correction on refund

Hook:

- `woocommerce_order_refunded`

Method:

- `Weblo_Fakturownia_Plugin::maybe_auto_issue_correction()`

Checks:

- base invoice exists,
- no correction yet,
- auto correction enabled.

---

## 5. Correction Modes

Setting:

- `weblo_fakturownia_correction_mode`

Modes:

- `difference` - include only changed positions,
- `full` - include all base invoice positions with updated after-state.

Shipping behavior controlled separately via:

- `weblo_fakturownia_correction_shipping_mode`
- `weblo_fakturownia_correction_shipping_amount`

---

## 6. Logs and Debug

### DB logs (integration errors)

- Stored in `wp_weblo_fakturownia_logs`.
- Visible in Logs tab.
- Can be cleared from Logs tab (new "Clear database logs" button).

### File debug log

Path:

- `logs/debug.log`

Rules:

- enabled only when setting `weblo_fakturownia_debug_logging_enabled = yes`,
- capped at 2 MB (rotation by delete/recreate),
- intended only for troubleshooting.

---

## 7. JavaScript Assets

### Settings UI script

File:

- `assets/js/weblo-fakturownia-admin.js`

Responsibilities:

- tab switching,
- test connection AJAX,
- bulk jobs orchestration,
- logs fetch/render/clear,
- remember active tab in `localStorage`,
- keep Save button enabled.

### Metabox script

File:

- `assets/js/weblo-fakturownia-order-metabox.js`

Responsibilities:

- invoice/correction AJAX actions in order screen,
- inline success/error result rendering.

### Admin CSS

File:

- `assets/css/weblo-fakturownia-admin.css`

Responsibilities:

- modern tab/panel style,
- logs and progress visual polish,
- email editor min height (250px).

---

## 8. Translations

Text domain:

- `weblo-fakturownia`

Translation folder:

- `languages/`

Main files:

- `languages/weblo-fakturownia-pl_PL.po`
- `languages/weblo-fakturownia-pl_PL.mo`
- `languages/weblo-fakturownia.pot`
- `languages/weblo-fakturownia-pl_PL.l10n.php`

Plugin header includes:

- `Text Domain: weblo-fakturownia`
- `Domain Path: /languages`

Textdomain is loaded in:

- `Weblo_Fakturownia_Plugin::init()`

---

## 9. Important Hooks and AJAX Actions

### WordPress / WooCommerce hooks

- `plugins_loaded`
- `woocommerce_order_status_changed`
- `woocommerce_order_refunded`
- `admin_enqueue_scripts`
- `template_redirect`
- `restrict_manage_posts`
- `pre_get_posts`

### AJAX actions (settings)

- `weblo_fakturownia_test_connection`
- `weblo_fakturownia_bulk_issue_invoices`
- `weblo_fakturownia_bulk_issue_corrections`
- `weblo_fakturownia_fetch_logs`
- `weblo_fakturownia_clear_debug_log`
- `weblo_fakturownia_clear_db_logs`

### AJAX actions (metabox)

- `weblo_issue_invoice_now`
- `weblo_issue_correction_now`
- `weblo_send_invoice_email`
- `weblo_send_correction_email`
- `weblo_download_invoice_pdf`

---

## 10. Dev Notes / Gotchas

- If connection is not confirmed, only Connection tab is visible.
- Settings save when connection is not confirmed is intentionally limited to Connection fields (prevents clearing hidden settings).
- Errors should be human-friendly in metabox, but raw details still available below.
- Keep user-facing strings translatable (`__()`, `esc_html__()`, etc.).
- Avoid noisy debug logs; keep file log focused on errors only.

---

## 11. Quick Manual Test Checklist

1. Connection
   - save domain/token, test connection, verify tabs unlock.
2. Manual invoice
   - issue invoice from metabox, verify ID/number and PDF download.
3. Manual correction
   - create refund, issue correction, verify correction meta.
4. Auto invoice
   - change order status to configured trigger and verify invoice creation.
5. Auto correction
   - refund order and verify correction creation.
6. Bulk invoices/corrections
   - run and stop jobs, verify progress and counters.
7. Logs tab
   - refresh logs, clear DB logs, clear debug.log.
8. Translations
   - switch site language, verify translated labels/messages.

