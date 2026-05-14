# WooCommerce AI Advisor Plugin Specification

## Working Plugin Name

**StoreGuide AI for WooCommerce**

Alternative names worth considering:
- ProductGuide AI for WooCommerce
- ShopAdvisor AI for WooCommerce
- Smart Product Advisor for WooCommerce

Recommended final name: **StoreGuide AI for WooCommerce**.

Reasoning:
- sounds product-focused rather than generic chatbot,
- fits WooCommerce and broader ecommerce use cases,
- is understandable for non-technical store owners,
- leaves room for future features beyond chat.

## Product Goal

Build an object-oriented WordPress plugin for WooCommerce that provides an AI shopping assistant embedded as a chat widget on the frontend. The assistant should help customers choose products based on indexed store data such as product titles, descriptions, short descriptions, prices, sale prices, stock, categories, tags, attributes, variations, custom fields, FAQ snippets, curated brand content, and manually defined business rules. [cite:41][cite:54]

The plugin must not behave like a generic chatbot. It should act as a controlled product advisor using retrieval-first logic, business constraints, configurable assistant persona, and optional fallback between AI providers. Retrieval-augmented generation is the preferred architecture because it reduces hallucinations and keeps answers grounded in store data. [cite:41][cite:74]

## Core Principles

- WooCommerce required dependency.
- HPOS compatible.
- Object-oriented architecture with small, focused classes.
- Very short main plugin file, only bootstrap and loader references.
- All UI strings in English and wrapped in gettext functions for future translations.
- Modern, clear, non-technical admin UI with tooltips for settings.
- Debug logging stored inside the plugin data area when enabled.
- Developer settings with manual versioning and asset version controls for cache-busting.
- AI provider abstraction with switchable models and daily/monthly limits.
- Retrieval-first response flow.
- Strong guardrails and refusal logic.
- Plugin list “Settings” link for quick access.

## Functional Scope

### Frontend customer experience

The plugin should render a floating chat button in the storefront, typically in the bottom-right corner, with an expandable assistant panel. The widget should support configurable greeting, quick suggestions, typing state, streaming responses if the provider supports it, and action buttons such as “show products”, “compare”, or “add to cart” in later phases. [cite:41][cite:45]

The assistant should answer only within configured scope. Example domains include product selection by car model, skin type, budget, use case, compatibility, size, color, finish, or other product-specific properties. If store data is missing or confidence is too low, the assistant should say so and ask clarifying questions instead of guessing. This is important for safe RAG behavior. [cite:41][cite:74]

### Assistant persona and style

The plugin must include an “Assistant Style” module where the admin can configure how the assistant speaks and behaves. The final prompt should be composed from structured settings rather than a single free-text box because separated role, tone, and safety instructions are more stable and easier to maintain. [cite:63][cite:69]

Required persona settings:
- Persona name.
- Role, for example “technical advisor”, “premium beauty consultant”, “B2B sales assistant”.
- Tone, for example polite, professional, warm, concise, friendly, luxury.
- Formality, for example informal or Pan/Pani style.
- Response length preference.
- Clarification behavior, for example ask one question first or answer immediately if enough data exists.
- Sales behavior, for example suggest up to 3 products, include one cheaper alternative, mention accessories.
- Forbidden behaviors, for example never invent compatibility, never claim stock certainty unless indexed.
- Refusal policy.
- Custom brand voice instructions.
- Language selection and optional multilingual rules.

Recommended persona presets:
- Technical Expert
- Friendly Product Advisor
- Premium Brand Consultant
- Minimal and Concise Assistant
- B2B Sales Consultant

### Business rules engine

The assistant must support store-defined rules that influence recommendations independently of the AI provider. This avoids over-reliance on the model and allows commercial control.

Examples:
- Prefer in-stock products only.
- Exclude products from chosen categories or brands.
- Prefer products with higher margin.
- Prefer products on sale.
- Promote selected flagship products.
- Avoid discontinued or hidden items.
- Restrict recommendations to allowed categories per assistant profile.
- Limit number of returned products.

### Contact and brand knowledge

The assistant should have access to store-level business information manually entered in plugin settings. This enables better customer guidance and useful fallback responses.

Recommended business profile fields:
- Store name.
- Legal company name.
- Support email.
- Support phone number.
- WhatsApp number.
- Physical address.
- Service area / shipping countries.
- Opening hours.
- Google Maps or location URL.
- Website URL.
- Facebook URL.
- Instagram URL.
- TikTok URL.
- YouTube URL.
- LinkedIn URL.
- Marketplace profile links if relevant.
- Return policy URL.
- Shipping policy URL.
- FAQ page URL.
- Contact page URL.
- Warranty / complaint information.
- Preferred support escalation path.

These fields should be available to the assistant as a separate trusted context block, not merged blindly with product retrieval.

### Data retrieval and indexing

Fast response requires a dedicated retrieval layer. A custom index is recommended rather than scanning WooCommerce product objects on every request. Similar RAG-oriented WooCommerce chatbot implementations and store AI guidance emphasize structured, searchable data as the foundation for useful responses. [cite:41][cite:54]

The index should support:
- products,
- variations,
- categories,
- tags,
- attributes,
- selected custom fields,
- FAQ entries,
- manual knowledge base entries,
- store business profile,
- optional synonyms and aliases.

The retrieval strategy should support hybrid search in later phases:
- keyword / fulltext search,
- structured filters,
- semantic search with embeddings,
- re-ranking.

### AI providers and models

The plugin must be provider-agnostic. It should support multiple AI backends through a unified interface so the admin can switch providers or models without changing the rest of the plugin architecture. OpenRouter is useful as a multi-model gateway, while OpenAI remains a strong premium option; prompt caching guidance also favors a stable prompt layout for cost and latency improvements. [cite:47][cite:50][cite:67][cite:76]

Initial provider targets:
- OpenAI
- OpenRouter
- Custom OpenAI-compatible endpoint

Future provider candidates:
- Anthropic through gateway
- Google Gemini through gateway
- local/self-hosted endpoint

Provider settings should support:
- API key,
- base URL where applicable,
- model selection,
- timeout,
- max tokens,
- temperature,
- streaming on/off,
- provider priority order,
- fallback model,
- cost estimation settings.

### Limits, quotas, and cost control

The plugin must include strong usage control to avoid unexpected billing. This is especially important for usage-based providers and shared multi-model gateways. [cite:46][cite:47][cite:50][cite:52]

Required controls:
- daily requests limit,
- monthly requests limit,
- daily token limit,
- monthly token limit,
- daily cost limit,
- monthly cost limit,
- per-IP rate limit,
- per-session rate limit,
- separate limits for guests and logged-in users,
- auto-disable or fallback on threshold,
- warning thresholds such as 80% and 95%,
- safe mode with cheaper model fallback.

### Logging and analytics

The plugin should store operational and conversation logs. Admins need visibility into questions, failures, usage, and quality.

Required log categories:
- system log,
- provider/API log,
- retrieval log,
- conversation log,
- rule decision log,
- rate limit log,
- indexing log,
- debug log.

Required analytics:
- top questions,
- no-answer questions,
- most recommended products,
- provider usage,
- model usage,
- average response time,
- estimated spend,
- conversions influenced by chat in later phases.

## Recommended Plugin Architecture

## Folder Structure

```text
storeguide-ai/
├── storeguide-ai.php
├── uninstall.php
├── readme.txt
├── languages/
│   └── storeguide-ai.pot
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   ├── frontend.css
│   │   └── chat-widget.css
│   ├── js/
│   │   ├── admin.js
│   │   ├── frontend.js
│   │   └── chat-widget.js
│   └── img/
├── includes/
│   ├── class-plugin.php
│   ├── class-loader.php
│   ├── class-activator.php
│   ├── class-deactivator.php
│   ├── class-requirements.php
│   ├── class-hpos.php
│   ├── class-i18n.php
│   ├── class-admin.php
│   ├── class-admin-menu.php
│   ├── class-admin-assets.php
│   ├── class-plugin-links.php
│   ├── class-settings.php
│   ├── class-settings-sections.php
│   ├── class-tooltips.php
│   ├── class-developer-settings.php
│   ├── class-version-manager.php
│   ├── class-debug-logger.php
│   ├── class-log-manager.php
│   ├── class-rest.php
│   ├── class-permissions.php
│   ├── class-frontend.php
│   ├── class-widget-renderer.php
│   ├── class-chat-controller.php
│   ├── class-conversation-manager.php
│   ├── class-context-builder.php
│   ├── class-prompt-composer.php
│   ├── class-persona-manager.php
│   ├── class-business-profile.php
│   ├── class-rule-engine.php
│   ├── class-response-validator.php
│   ├── class-cache.php
│   ├── class-index-manager.php
│   ├── class-index-builder.php
│   ├── class-index-sync.php
│   ├── class-retriever.php
│   ├── class-filter-parser.php
│   ├── class-embeddings.php
│   ├── class-analytics.php
│   ├── class-quota-manager.php
│   ├── class-provider-manager.php
│   ├── providers/
│   │   ├── interface-provider.php
│   │   ├── class-openai-provider.php
│   │   ├── class-openrouter-provider.php
│   │   └── class-custom-provider.php
│   ├── database/
│   │   ├── class-schema.php
│   │   ├── class-migrations.php
│   │   └── class-repositories.php
│   └── views/
│       ├── admin/
│       └── frontend/
└── templates/
    ├── chat-widget.php
    └── admin/
```

## Main plugin file rules

The main plugin file must remain intentionally short.

Responsibilities of `storeguide-ai.php`:
- plugin header,
- defined constants,
- minimum environment checks include,
- bootstrap loader include,
- plugin run call.

Example responsibility flow:
1. define constants such as plugin version, paths, URLs,
2. require `includes/class-loader.php`,
3. initialize the main plugin class,
4. register activation/deactivation hooks,
5. exit.

Do not place business logic in the main file.

## Suggested class responsibilities

### Bootstrap and environment
- `Plugin`: central coordinator.
- `Loader`: registers hooks.
- `Requirements`: checks WordPress, PHP, WooCommerce, extensions.
- `HPOS`: declares compatibility.
- `I18n`: loads text domain.
- `PluginLinks`: adds Settings link in plugins list.

### Admin
- `Admin`: orchestrates admin area.
- `AdminMenu`: menu pages and tabs.
- `Settings`: register settings.
- `SettingsSections`: organized settings UI.
- `Tooltips`: tooltip registry and rendering.
- `DeveloperSettings`: debug, versions, migrations, test tools.
- `VersionManager`: plugin version and asset versions.

### Frontend and chat
- `Frontend`: enqueues and storefront hooks.
- `WidgetRenderer`: chat button and panel UI.
- `ChatController`: frontend request handling.
- `ConversationManager`: sessions and message persistence.

### AI and prompting
- `ContextBuilder`: collects persona, business profile, rules, retrieved docs.
- `PromptComposer`: final prompt assembly.
- `PersonaManager`: persona presets and settings.
- `BusinessProfile`: store contact and brand data.
- `RuleEngine`: business constraints and recommendation logic.
- `ResponseValidator`: post-generation checks and safe fallback.

### Retrieval and indexing
- `IndexManager`: indexing orchestration.
- `IndexBuilder`: builds documents.
- `IndexSync`: incremental updates.
- `Retriever`: search across indexed documents.
- `FilterParser`: converts user questions into structured filters.
- `Embeddings`: semantic search layer in later phase.

### Providers and quotas
- `ProviderManager`: provider selection and fallback.
- `QuotaManager`: token/request/cost limits.
- `OpenAIProvider`, `OpenRouterProvider`, `CustomProvider`: provider implementations.

### Logging and data
- `DebugLogger`: plugin-owned debug logging.
- `LogManager`: log storage and retention.
- `Analytics`: usage insights.
- `Schema`, `Migrations`, `Repositories`: DB schema and persistence.

## Database Design

Use custom tables for performance-critical features rather than post meta.

### Required tables

#### 1. `wp_storeguide_ai_documents`
Purpose: normalized retrieval documents.

Suggested columns:
- `id`
- `document_type` (`product`, `variation`, `category`, `faq`, `kb`, `business_profile`)
- `object_id`
- `title`
- `summary`
- `content_text`
- `language_code`
- `status`
- `checksum`
- `source_updated_at`
- `indexed_at`
- `created_at`
- `updated_at`

#### 2. `wp_storeguide_ai_document_meta`
Purpose: structured filters and retrieval metadata.

Suggested columns:
- `id`
- `document_id`
- `product_id`
- `parent_product_id`
- `sku`
- `price`
- `regular_price`
- `sale_price`
- `currency`
- `stock_status`
- `stock_quantity`
- `category_ids_json`
- `tag_ids_json`
- `attribute_map_json`
- `custom_fields_json`
- `visibility`
- `brand`
- `rating`
- `popularity`
- `margin_score`
- `is_featured`

#### 3. `wp_storeguide_ai_embeddings`
Purpose: optional semantic vectors.

Suggested columns:
- `id`
- `document_id`
- `provider`
- `model`
- `embedding_vector`
- `dimensions`
- `created_at`

#### 4. `wp_storeguide_ai_conversations`
Purpose: session-level conversations.

Suggested columns:
- `id`
- `session_key`
- `user_id`
- `customer_email`
- `customer_ip_hash`
- `source_page`
- `started_at`
- `updated_at`
- `status`

#### 5. `wp_storeguide_ai_messages`
Purpose: individual messages.

Suggested columns:
- `id`
- `conversation_id`
- `role`
- `message_text`
- `provider`
- `model`
- `prompt_tokens`
- `completion_tokens`
- `estimated_cost`
- `latency_ms`
- `created_at`

#### 6. `wp_storeguide_ai_logs`
Purpose: system, debug, API, and retrieval logs.

Suggested columns:
- `id`
- `log_type`
- `level`
- `context_key`
- `message`
- `details_json`
- `created_at`

#### 7. `wp_storeguide_ai_quotas`
Purpose: daily and monthly usage aggregation.

Suggested columns:
- `id`
- `scope_type` (`global`, `ip`, `session`, `user`)
- `scope_key`
- `period_type` (`daily`, `monthly`)
- `period_key`
- `requests_count`
- `input_tokens`
- `output_tokens`
- `estimated_cost`
- `updated_at`

#### 8. `wp_storeguide_ai_personas`
Purpose: persona presets and active assistant style profiles.

Suggested columns:
- `id`
- `name`
- `role_label`
- `tone`
- `formality`
- `response_style`
- `clarification_mode`
- `sales_behavior`
- `forbidden_behaviors_json`
- `refusal_policy`
- `custom_instructions`
- `is_default`
- `created_at`
- `updated_at`

#### 9. `wp_storeguide_ai_rules`
Purpose: recommendation and business rules.

Suggested columns:
- `id`
- `rule_name`
- `rule_type`
- `priority`
- `conditions_json`
- `actions_json`
- `is_active`
- `created_at`
- `updated_at`

### Optional file-based logs

If enabled, store debug logs under a plugin-owned writable path such as:

```text
wp-content/uploads/storeguide-ai/logs/
```

Reasoning:
- safer than plugin directory writes on many hosts,
- survives plugin updates,
- easier permissions model,
- easier rotation and cleanup.

If file logging is enabled, also store an `.htaccess` and `index.php` guard in the logs directory.

## Admin UI Specification

The admin panel must be modern, clear, and accessible for non-technical users. Use a tabbed or sidebar-sections layout with grouped cards, concise descriptions, and per-field help tooltips.

Recommended top-level sections:
- Dashboard
- General
- Chat Widget
- Assistant Style
- Knowledge & Index
- AI Providers
- Limits & Budget
- Business Profile
- Rules
- Logs & Analytics
- Developer
- Tools

### Dashboard
Show:
- plugin status,
- WooCommerce status,
- HPOS compatibility status,
- active provider and model,
- quota usage,
- index health,
- latest errors,
- quick actions.

### General
Fields:
- enable plugin,
- assistant name,
- default language,
- admin notice settings,
- retention periods.

### Chat Widget
Fields:
- enable widget,
- widget position,
- icon style,
- welcome message,
- placeholder,
- suggested prompts,
- color scheme,
- device visibility,
- page targeting.

### Assistant Style
Fields from the persona section plus presets and live preview.

### Knowledge & Index
Fields:
- choose indexed post types and products,
- choose allowed custom fields,
- choose taxonomies,
- choose FAQ sources,
- indexing mode,
- manual rebuild button,
- scheduled sync,
- embedding mode,
- synonym manager.

### AI Providers
Fields:
- provider enable toggles,
- API credentials,
- model select,
- timeout,
- temperature,
- max tokens,
- fallback provider/model,
- streaming,
- test connection button.

### Limits & Budget
Fields:
- request limits,
- token limits,
- estimated cost limits,
- guest vs user quotas,
- rate limiting,
- alerts,
- safe mode thresholds.

### Business Profile
Fields described in the business profile section.

### Rules
Fields:
- recommendation rules list,
- rule priorities,
- include/exclude logic,
- in-stock preference,
- promoted products,
- manual boosts,
- forbidden categories.

### Logs & Analytics
Views:
- filters by type and date,
- conversation history,
- provider calls,
- retrieval traces,
- export CSV,
- clear logs.

### Developer
Fields:
- debug mode,
- debug log enable,
- debug verbosity,
- DB schema version,
- plugin internal version,
- asset version,
- force asset bump button,
- migration runner,
- test endpoints,
- reset caches.

## Versioning and Cache Busting

The plugin must support a controlled versioning system from the developer panel.

Required version layers:
- plugin code version,
- database schema version,
- asset version,
- index schema version,
- prompt schema version.

Requirements:
- store current values in options,
- allow manual asset version bump for cache busting,
- optionally auto-sync asset version to plugin version,
- show version history log,
- allow safe migration checks,
- display current installed version in dashboard.

Use cases:
- stale JS/CSS due to server or CDN cache,
- DB migration rollouts,
- prompt structure changes,
- reindex requirements after schema updates.

## Debug Logging

Debug logging should be optional and disabled by default.

When enabled, support:
- log levels: error, warning, info, debug,
- category filtering,
- max file size and rotation,
- retention cleanup,
- safe redaction of API keys and personal data,
- admin viewer with filters and copy/export,
- contextual logging from providers, retriever, indexer, and UI events.

Never log raw secrets.

## Prompt Composition Design

Build prompts in layers, not as one ad-hoc blob. OpenAI documentation on prompt caching favors stable prompt prefixes and dynamic content later in the request. [cite:67][cite:76]

Recommended prompt order:
1. safety core,
2. plugin scope and refusal rules,
3. assistant persona,
4. business profile context,
5. business rules,
6. retrieved product/knowledge context,
7. conversation summary or recent turns,
8. current user question.

Example core rules:
- answer only using available store context and configured business info,
- do not invent compatibility, stock, or pricing,
- ask concise clarifying questions when needed,
- recommend only products allowed by business rules,
- be honest when data is missing,
- keep tone aligned with selected persona.

## Security Requirements

- Use nonces for all admin and frontend write actions.
- Validate and sanitize all settings.
- Escape all output.
- Restrict logs and developer tools by capability.
- Secure REST endpoints with proper permission callbacks.
- Hash IP addresses if stored.
- Respect privacy and local data regulations.
- Redact secrets from logs and exports.
- Use least-privilege access for all admin actions.

## WooCommerce and HPOS Requirements

- Plugin must require WooCommerce to activate fully.
- Show clear admin notice if WooCommerce is missing.
- Declare HPOS compatibility using WooCommerce recommended mechanism.
- Test plugin behavior with HPOS enabled.
- Avoid direct assumptions based on legacy order storage.
- Keep future order-related analytics HPOS safe.

## Internationalization Requirements

- All strings must be English source strings.
- Use one text domain consistently.
- Wrap every UI string in translation functions.
- Prepare `.pot` generation.
- Do not hardcode mixed-language labels.
- Tooltips must also be translatable.

Suggested text domain:

```text
storeguide-ai
```

## Plugin List Settings Link

Add a “Settings” action link on the Plugins screen that points directly to the main admin page of the plugin. This improves usability for store owners and is part of the required UX.

## Recommended Development Phases

### Phase 1: Foundation
- bootstrap,
- requirements,
- admin menu,
- settings API,
- plugin links,
- i18n,
- HPOS declaration,
- developer panel,
- logging base.

### Phase 2: Frontend widget
- floating widget,
- chat panel,
- frontend assets,
- REST endpoint skeleton,
- session handling.

### Phase 3: Indexing and retrieval
- schema,
- indexing builder,
- incremental sync,
- keyword search,
- structured filters.

### Phase 4: Providers and prompting
- provider interface,
- OpenAI provider,
- OpenRouter provider,
- prompt composer,
- persona manager,
- business profile context.

### Phase 5: Limits and analytics
- quotas,
- alerts,
- logs viewer,
- analytics dashboard.

### Phase 6: Business rules and quality
- rule engine,
- response validation,
- no-answer handling,
- confidence improvements.

### Phase 7: Advanced retrieval
- embeddings,
- hybrid retrieval,
- reranking,
- caching improvements.

## Cursor Implementation Notes

Use iterative generation. Do not generate the whole plugin in one step.

Recommended coding rules for Cursor:
- Keep each class focused on one responsibility.
- Follow WordPress coding standards.
- Prefer dependency injection or explicit composition over hidden global dependencies.
- Use repositories/services for DB operations.
- Put all admin markup into view files where practical.
- Keep methods short and testable.
- Add inline documentation only where helpful.
- Use English UI strings only.
- Build feature by feature and verify after each stage.

## Definition of Done for MVP

MVP is complete when all conditions are true:
- WooCommerce dependency works.
- HPOS compatibility is declared and tested.
- Admin settings panel exists with tooltips.
- Plugin settings link appears in plugins list.
- Frontend widget works.
- Persona settings work.
- Business profile settings work.
- Product index builds and updates.
- Retrieval-based answers use indexed data.
- Provider switching works.
- Limits and quotas work.
- Debug log works when enabled.
- Asset version bump works from developer panel.
- All strings are English and translatable.
- Main plugin file remains short.

## Recommended Nice-to-Have Features

- conversation export,
- failed answer review queue,
- prompt preview debugger,
- provider latency comparison,
- A/B test persona presets,
- answer source trace for admins,
- confidence score display in debug mode,
- manual knowledge base editor,
- seasonal recommendation rules,
- multilingual retrieval.
