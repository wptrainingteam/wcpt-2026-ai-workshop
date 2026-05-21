# Section 2 — Scaffold the Plugin & Connect to the AI API

Time to write code. We'll create the plugin scaffold and make our first live AI request using `wp_ai_client_prompt()` — WordPress 7.0's provider-agnostic PHP AI Client.

> **Stuck? Completed code for this section lives at `code-reference/section-2/`** — open it to compare against your own work, not to copy from. Each numbered `code-reference/section-N/` folder is a snapshot of what the plugin should look like after finishing that section.

Reference: [PHP AI Client (WordPress/php-ai-client)](https://github.com/WordPress/php-ai-client)

## The Plugin File

1. Open `plugin.php` at the root of the repo. The plugin header is already in place:

```php
<?php
/**
 * Plugin Name: WordPress AI Workshop — Content Summarizer
 * Description: A hands-on workshop plugin teaching the WordPress 7.0 AI Building Blocks.
 * Version:     0.1.0
 * Requires at least: 7.0
 * Requires PHP:      8.1
 * Text Domain: wp-ai-workshop
 */
```

2. Below the header, add a guard so the plugin exits gracefully on older WordPress versions:

```php
// The PHP AI Client (`wp_ai_client_prompt()`) ships in WordPress 7.0 core.
// On older versions the function isn't defined yet — bail out silently so
// the plugin doesn't fatal during an upgrade.
if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
	return;
}
```

3. Require our main includes file:

```php
// All plugin logic lives in includes/summarizer.php.
require_once __DIR__ . '/includes/summarizer.php';
```

4. Create the file `includes/summarizer.php`. This is where all our PHP logic will live for the next two sections.

## Make Your First AI Request

Before wiring up any WordPress APIs, let's confirm the AI client is working with your configured provider.

5. In `includes/summarizer.php`, add a test function:

```php
<?php

/**
 * Temporary smoke test — confirms the configured AI provider is reachable.
 *
 * Hooks into `admin_notices` so the result is visible on every admin screen.
 * Removed at the start of Section 3 once the real ability replaces it.
 */
function wp_ai_workshop_test_ai_connection() {
	// Restrict to admins; we don't want every editor seeing the smoke test.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// `wp_ai_client_prompt()` returns a builder object; calling
	// `->generate_text()` is what makes the actual provider request. The
	// result is a string on success or a WP_Error on failure (bad key, rate
	// limit, network error, etc.).
	$response = wp_ai_client_prompt(
		'Say hello to the WordPress AI Building Blocks workshop attendees in exactly one sentence.'
	)->generate_text();

	if ( is_wp_error( $response ) ) {
		printf(
			'<div class="notice notice-error"><p>AI Error: %s</p></div>',
			esc_html( $response->get_error_message() )
		);
		return;
	}

	printf(
		'<div class="notice notice-success"><p>%s</p></div>',
		esc_html( $response )
	);
}
add_action( 'admin_notices', 'wp_ai_workshop_test_ai_connection' );
```

6. Load any admin page. You should see a green notice with a one-sentence greeting from your AI model. 🎉

If you see an error, check **Settings → Connectors** and confirm your API key is saved correctly.

## Peek Under the Hood (Optional, Presenter-Led)

Before we move on, let's see what `wp_ai_client_prompt()` actually hands back. If you're running this in **WP Studio**, set a breakpoint on the line:

```php
$response = wp_ai_client_prompt(
    'Say hello to the WordPress AI Building Blocks workshop attendees in exactly one sentence.'
)->generate_text();
```

Place the breakpoint **on the `wp_ai_client_prompt(...)` call itself**, then step _over_ that call (don't step into `generate_text()`) and inspect the return value. You'll see a `WP_AI_Client_Prompt_Builder` object — the fluent builder we talked about in Section 1. Every `using_*()`, `with_*()`, and `as_*()` method returns `$this`; only the terminal `generate_*()` call actually fires the HTTP request.

Seeing the builder object live makes the rest of the workshop click — every chain you write later is just configuring that object before you hand it to a `generate_*()` method.

7. Leave the test function in place for now — it's our proof that the AI client is wired up. We'll delete it at the start of Section 3 when we replace it with the real ability code.

---

# Ready to move on?
[Section 3: Registering the Summarization Ability](./section-3.md)

# Missing something from the last section?
[Section 1: Tour the AI Experiments Plugin](./section-1.md)
