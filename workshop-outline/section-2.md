# Section 2 — Scaffold the Plugin & Connect to the AI API

Time to write code. We'll create the plugin scaffold and make our first live AI request using `wp_ai_get_client()` — WordPress 7.0's provider-agnostic PHP AI Client.

Reference: [PHP AI Client](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-ai-client/)

## The Plugin File

1. Open `plugin.php` at the root of the repo. The plugin header is already in place:

```php
<?php
/**
 * Plugin Name: WC Portugal 2026 — Content Summarizer
 * Description: A hands-on workshop plugin for WordCamp Portugal 2026.
 * Version:     0.1.0
 * Requires at least: 7.0
 * Requires PHP:      8.1
 * Text Domain: wcpt
 */
```

2. Below the header, add a guard so the plugin exits gracefully on older WordPress versions:

```php
if ( ! function_exists( 'wp_ai_get_client' ) ) {
	return;
}
```

3. Require our main includes file:

```php
require_once __DIR__ . '/includes/summarizer.php';
```

4. Create the file `includes/summarizer.php`. This is where all our PHP logic will live for the next two sections.

## Make Your First AI Request

Before wiring up any WordPress APIs, let's confirm the AI client is working with your configured provider.

5. In `includes/summarizer.php`, add a test function:

```php
<?php

function wcpt_test_ai_connection() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$client   = wp_ai_get_client();
	$response = $client->text()->generate(
		'Say hello to the WordCamp Portugal 2026 attendees in exactly one sentence.'
	);

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
add_action( 'admin_notices', 'wcpt_test_ai_connection' );
```

6. Load any admin page. You should see a green notice with a one-sentence greeting from your AI model. 🎉

If you see an error, check **Settings → Connectors** and confirm your API key is saved correctly.

7. Once it's working, **remove** the `wcpt_test_ai_connection` function and its `add_action` call — we won't need it anymore.

---

# Ready to move on?
[Section 3: Registering the Summarization Ability](./section-3.md)

# Missing something from the last section?
[Section 1: Tour the AI Experiments Plugin](./section-1.md)
