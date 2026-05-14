# WooCommerce AI Advisor Test Plan

## Purpose

This document defines the functional, technical, and regression tests for the WooCommerce AI advisor plugin implementation. It is intended for use during iterative development in Cursor so each module can be validated after implementation.

Recommended plugin name used in this plan: **StoreGuide AI for WooCommerce**.

## Test Strategy

Test in stages, not only at the end. Each completed module should pass its own checklist before the next module is implemented.

Test environments recommended:
- local WordPress + WooCommerce sandbox,
- test site with HPOS enabled,
- site with caching/minification enabled,
- site with many products and variations,
- site without WooCommerce for dependency checks,
- optional staging site with CDN/cache layer.

## Base Test Matrix

| Area | Must Test | Notes |
|---|---|---|
| Activation | Yes | dependency, schema, hooks |
| Admin UI | Yes | settings, tooltips, permissions |
| Frontend widget | Yes | rendering, mobile, session flow |
| Retrieval | Yes | indexing, search, filters |
| Providers | Yes | credentials, fallback, limits |
| Persona | Yes | tone, role, refusal behavior |
| Logs | Yes | enable/disable, redaction, viewer |
| Versioning | Yes | asset bumps, cache busting |
| HPOS | Yes | compatibility and no regressions |
| Security | Yes | nonces, caps, sanitization |

## Test Data Preparation

Prepare at least:
- 30 simple products,
- 20 variable products,
- products with categories and tags,
- products with 6 to 10 attributes,
- products with custom fields,
- products with short and long descriptions,
- some out-of-stock products,
- some sale products,
- FAQ content or a small knowledge base,
- one assistant persona preset,
- one business profile with contact data,
- one provider configured,
- one fallback provider configured if available.

Prepare realistic examples such as:
- car model compatibility,
- skin type recommendation,
- budget-based filtering,
- category-specific suggestions,
- product exclusions.

## Activation and Dependency Tests

### A1. Plugin activation with WooCommerce active
Expected result:
- plugin activates successfully,
- tables are created,
- admin menu appears,
- no fatal errors.

### A2. Plugin activation without WooCommerce
Expected result:
- plugin does not fully initialize business features,
- admin notice explains WooCommerce requirement,
- no fatal errors,
- plugin dependency behavior is clear.

### A3. HPOS declaration
Expected result:
- plugin declares HPOS compatibility correctly,
- no warnings in WooCommerce status tools.

### A4. Re-activation after update
Expected result:
- migrations run safely,
- no duplicate table creation,
- version values update correctly.

## Main File and Bootstrap Tests

### B1. Main plugin file remains minimal
Check:
- only header, constants, includes, hooks, bootstrap.
- no business logic.

### B2. Loader registration
Expected result:
- hooks are added from organized classes,
- admin and frontend assets load only where needed.

## Admin UI Tests

### C1. Settings link in Plugins list
Expected result:
- “Settings” action link is visible,
- click opens plugin settings directly.

### C2. Admin navigation
Expected result:
- sections/tabs load correctly,
- active tab persists,
- layout remains clean.

### C3. Tooltips
Expected result:
- each important option has a tooltip,
- tooltip language is clear for non-technical users,
- tooltip text is translatable.

### C4. Permissions
Expected result:
- unauthorized users cannot access settings,
- direct URL access is blocked by capability checks.

### C5. Settings save
Expected result:
- values save correctly,
- invalid values are sanitized,
- success/error messages display properly.

## Internationalization Tests

### D1. English source strings only
Expected result:
- all interface strings are English in code,
- no hardcoded Polish or mixed-language labels.

### D2. Text domain consistency
Expected result:
- all strings use the same text domain,
- .pot generation works.

### D3. Tooltip translations
Expected result:
- tooltip strings are wrapped for translation too.

## Frontend Widget Tests

### E1. Widget display
Expected result:
- widget appears in configured position,
- respects enable/disable setting,
- can be hidden on selected devices/pages.

### E2. Widget interaction
Expected result:
- open/close works,
- welcome message displays,
- placeholder and quick prompts display correctly.

### E3. Mobile behavior
Expected result:
- widget is usable on mobile,
- no layout overflow,
- controls remain tappable.

### E4. Accessibility
Expected result:
- keyboard navigation works,
- focus states visible,
- aria labels present where needed.

### E5. Cache-busting after asset bump
Expected result:
- after asset version bump, new CSS/JS loads,
- stale frontend asset issue is resolved.

## Persona and Prompt Tests

### F1. Persona preset application
Expected result:
- selected persona changes assistant output style,
- role and tone are reflected in answers.

### F2. Formality mode
Test examples:
- informal mode,
- formal Pan/Pani mode.

Expected result:
- assistant phrasing matches the selected formality.

### F3. Response length preference
Expected result:
- short mode is concise,
- detailed mode gives longer structured guidance.

### F4. Clarification behavior
Expected result:
- assistant asks follow-up when question lacks enough parameters,
- assistant does not ask unnecessary questions if context is sufficient.

### F5. Forbidden behaviors
Expected result:
- assistant does not invent compatibility, pricing, or stock details,
- refusal policy triggers when data is insufficient.

### F6. Prompt composition order
Check:
- safety core first,
- persona before retrieval context,
- current user message last.

## Business Profile Tests

### G1. Contact data usage
Expected result:
- assistant can answer questions about phone, email, address, opening hours, socials, return policy, and support paths using configured store data.

### G2. Business profile isolation
Expected result:
- business profile data is inserted as separate trusted context,
- does not overwrite product retrieval logic.

### G3. Missing business fields
Expected result:
- assistant gracefully handles missing contact data,
- does not invent unavailable details.

## Indexing and Retrieval Tests

### H1. Initial index build
Expected result:
- selected products are indexed,
- documents and meta tables populate,
- indexing status visible in admin.

### H2. Incremental sync
Expected result:
- editing product title/price/attribute updates only affected records,
- no full rebuild unless requested.

### H3. Custom fields indexing
Expected result:
- selected custom fields are included,
- excluded fields are not indexed.

### H4. FAQ/KB indexing
Expected result:
- knowledge entries appear in retrieval when relevant.

### H5. Retrieval relevance by filters
Test examples:
- “show products under 100”
- “for dry skin”
- “for BMW E46”
- “in stock only”

Expected result:
- results match indexed metadata and rules.

### H6. Out-of-stock handling
Expected result:
- out-of-stock recommendations follow configured business rule.

### H7. Empty retrieval result
Expected result:
- assistant states that matching products were not found,
- asks for different criteria when appropriate.

## Provider Tests

### I1. OpenAI provider connection
Expected result:
- API credentials validate,
- connection test works,
- provider can answer requests.

### I2. OpenRouter provider connection
Expected result:
- API credentials validate,
- model list or configured model works,
- provider can answer requests. [cite:47][cite:50]

### I3. Custom provider compatibility
Expected result:
- OpenAI-compatible custom endpoint works if valid.

### I4. Provider switching
Expected result:
- admin can switch active provider,
- response flow keeps working without code changes elsewhere.

### I5. Provider fallback
Expected result:
- on failure of primary provider, fallback provider/model is used if configured.

### I6. Streaming support
Expected result:
- streaming works when supported,
- graceful fallback to non-stream mode otherwise.

## Limits and Budget Tests

### J1. Daily request limit
Expected result:
- after reaching limit, further requests are blocked or downgraded based on configuration.

### J2. Monthly request limit
Expected result:
- monthly counters aggregate correctly and reset with next period.

### J3. Token tracking
Expected result:
- prompt and completion tokens are stored accurately enough for reporting.

### J4. Cost threshold handling
Expected result:
- warning threshold is triggered,
- safe mode or fallback activates when configured.

### J5. Guest vs logged-in quotas
Expected result:
- separate quota logic works.

### J6. Rate limiting
Expected result:
- repeated rapid requests from same IP/session are throttled.

## Logging and Debug Tests

### K1. Debug log disabled
Expected result:
- no debug log is written when disabled.

### K2. Debug log enabled
Expected result:
- events are written to DB and/or file depending on configuration,
- log entries include timestamps and context.

### K3. Redaction
Expected result:
- API keys, secrets, and sensitive personal data are not stored in plain text logs.

### K4. Log viewer
Expected result:
- admin can filter logs by date, level, and type,
- pagination works,
- no fatal errors with large log volume.

### K5. Log rotation and cleanup
Expected result:
- old logs are cleaned according to retention settings,
- max file size rotation works if file logs are enabled.

## Developer and Versioning Tests

### L1. Internal version display
Expected result:
- plugin version, DB schema version, asset version, and prompt schema version are visible.

### L2. Manual asset bump
Expected result:
- clicking asset bump updates asset version,
- frontend/admin URLs change accordingly.

### L3. DB migration runner
Expected result:
- migration tool runs once safely,
- duplicate execution does not corrupt schema.

### L4. Cache scenario
Expected result:
- after CSS/JS changes on cached site, manual asset bump resolves stale assets.

### L5. Reset tools
Expected result:
- cache reset and test tools work only for authorized users,
- dangerous actions require confirmation.

## Rules Engine Tests

### M1. In-stock preference rule
Expected result:
- assistant prefers in-stock products when enabled.

### M2. Category exclusion rule
Expected result:
- excluded categories are never recommended.

### M3. Promoted product rule
Expected result:
- featured products receive boost where relevant without breaking basic relevance.

### M4. Priority order
Expected result:
- higher-priority rules override lower-priority rules where conflicts exist.

## Conversation and Message Tests

### N1. Conversation creation
Expected result:
- first message creates conversation/session record.

### N2. Message storage
Expected result:
- user and assistant messages are stored correctly,
- provider metadata is linked.

### N3. Session continuation
Expected result:
- consecutive messages keep context within allowed session logic.

### N4. Session reset
Expected result:
- reset starts a fresh conversation,
- old conversation remains stored if retention permits.

## Security Tests

### O1. Nonce validation
Expected result:
- admin and write actions reject invalid nonce requests.

### O2. Capability checks
Expected result:
- only authorized roles access settings, logs, and developer tools.

### O3. Sanitization and escaping
Expected result:
- stored settings are sanitized,
- rendered outputs are escaped,
- no XSS through settings or logs.

### O4. REST permissions
Expected result:
- endpoints expose only intended actions,
- protected actions use permission callbacks.

### O5. Data privacy
Expected result:
- IPs are hashed if stored,
- exports do not reveal secrets.

## HPOS and WooCommerce Compatibility Tests

### P1. HPOS enabled environment
Expected result:
- plugin works normally,
- no compatibility warnings.

### P2. WooCommerce updates
Expected result:
- plugin still loads and requirement checks remain valid.

### P3. Product variation handling
Expected result:
- variable products and variations index correctly,
- assistant can reason on variation data where supported.

## Performance Tests

### Q1. First response latency
Target:
- reasonable response time for indexed retrieval flow.

### Q2. Large catalog indexing
Expected result:
- index build completes without timeout in chunked or scheduled mode,
- progress reporting works.

### Q3. Repeated common query
Expected result:
- caching reduces repeat overhead where implemented.

### Q4. Prompt stability
Expected result:
- stable prompt prefix remains consistent for cache-friendly provider behavior. [cite:67][cite:76]

## Regression Checklist

Run this checklist after every meaningful feature merge:
- plugin activates,
- no fatal errors,
- admin menu loads,
- settings save,
- widget renders,
- one provider works,
- one indexed query works,
- persona still applies,
- logs still write correctly,
- asset version bump still works,
- settings link still exists,
- no untranslated accidental strings introduced.

## Suggested Iterative Milestones

### Milestone 1
- bootstrap,
- settings page,
- plugin links,
- developer settings,
- logs base.

### Milestone 2
- widget UI,
- REST skeleton,
- conversation creation.

### Milestone 3
- indexing schema,
- build index,
- simple retriever.

### Milestone 4
- provider interface,
- OpenAI provider,
- OpenRouter provider,
- prompt composer.

### Milestone 5
- persona,
- business profile,
- limits,
- rule engine basics.

### Milestone 6
- analytics,
- debug tools,
- cache/version tools,
- refinement.

## Final Release Acceptance

Release candidate is accepted only if:
- dependency checks are stable,
- HPOS compatibility is verified,
- no fatal errors in admin or frontend,
- provider switching works,
- limits protect spend,
- debug tools do not expose secrets,
- widget is usable on desktop and mobile,
- settings are understandable for non-technical users,
- all strings are English and translatable,
- plugin architecture remains modular and maintainable.
