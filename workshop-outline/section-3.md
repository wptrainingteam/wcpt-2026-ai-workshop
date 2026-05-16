# Section 3 — Registering the Summarization Ability

The Abilities API gives our AI feature a standard, discoverable interface. Instead of a bespoke REST route, we register an Ability with a defined input/output schema — and WordPress automatically exposes it as a REST endpoint that any client (including AI agents) can call.

> **Stuck? Completed code for this section lives at `code-reference/section-3/`** — open it to compare against your own work, not to copy from.

Reference: [Abilities API](https://developer.wordpress.org/apis/abilities-api/)

## Register a Category

Abilities are organized into categories. Let's register one for our plugin.

1. First, **delete** the `wcpt_test_ai_connection` function and its `add_action` call from `includes/summarizer.php` — it served its purpose. Then add the category registration:

```php
/**
 * Register the workshop's ability category.
 *
 * Categories group abilities in the Abilities Explorer and let JS clients
 * filter with `getAbilities( { category: 'wcpt-workshop' } )`.
 */
function wcpt_register_ability_category() {
	wp_register_ability_category(
		'wcpt-workshop',
		array(
			'label'       => __( 'WC Portugal 2026', 'wcpt' ),
			'description' => __( 'Abilities built during the WordCamp Portugal 2026 workshop.', 'wcpt' ),
		)
	);
}
// `wp_abilities_api_categories_init` fires before `wp_abilities_api_init`,
// so the category exists by the time abilities try to reference it.
add_action( 'wp_abilities_api_categories_init', 'wcpt_register_ability_category' );
```

## Register the Ability

2. Now register the ability itself. Add this below the category registration:

```php
/**
 * Register the wcpt/summarization ability.
 */
function wcpt_register_summarization_ability() {
	wp_register_ability(
		'wcpt/summarization',
		array(
			'label'               => __( 'Summarize Content', 'wcpt' ),
			'description'         => __( 'Generates a plain-text summary of the provided content.', 'wcpt' ),
			'category'            => 'wcpt-workshop',
			// JSON Schema describing what callers must send. WordPress
			// validates incoming requests against this *before*
			// `execute_callback` runs — the callback never sees invalid data.
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'content' => array(
						'type'        => 'string',
						'description' => 'The content to summarize.',
					),
					'length'  => array(
						'type'        => 'string',
						'enum'        => array( 'short', 'medium', 'long' ),
						'default'     => 'medium',
						'description' => 'The desired length of the summary.',
					),
				),
				'required'   => array( 'content' ),
			),
			// JSON Schema for the return value. A plain string here.
			'output_schema'       => array(
				'type'        => 'string',
				'description' => 'The generated summary.',
			),
			// Same shape as REST permission callbacks. Runs before execute.
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'execute_callback'    => 'wcpt_execute_summarization',
			// `show_in_rest => true` is what auto-creates the REST endpoint
			// at /wp-json/wp-abilities/v1/abilities/wcpt/summarization/run.
			// `mcp.public => true` opts the ability into the MCP Adapter's
			// default server so AI agents can discover and execute it. The
			// value must be the boolean `true` — `1` or `'true'` do not opt in.
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
				),
			),
		)
	);
}
add_action( 'wp_abilities_api_init', 'wcpt_register_summarization_ability' );
```

Setting `'show_in_rest' => true` in `meta` is what tells WordPress to auto-expose this ability over the REST API. As soon as the ability is registered, the following endpoint exists with no extra `register_rest_route()` call required:

```
POST /wp-json/wp-abilities/v1/abilities/wcpt/summarization/run
```

The route follows the pattern `/<namespace>/<ability-slug>/run` — so the `wcpt/summarization` ability becomes `/wp-abilities/v1/abilities/wcpt/summarization/run`. (For comparison, the reference summarization ability in the WordPress/ai plugin is registered as `ai/summarization` and lives at `/wp-abilities/v1/abilities/ai/summarization/run`.) We'll hit this endpoint from the browser console in a moment to confirm the ability works before we touch any plugin JavaScript.

### Why `meta.mcp.public`?

By default, registered abilities are *not* visible to MCP clients — they're only reachable over REST. The `'mcp' => array( 'public' => true )` line opts this ability into the MCP Adapter's default server, which is what lets AI agents like Claude Desktop and Cursor discover and call it. We'll see this in action in Section 6; for now just know that the flag is the one-line opt-in that makes it possible.

Two gotchas worth flagging:

- The value has to be the boolean `true`. `1`, `'1'`, and `'true'` do *not* opt in — the MCP Adapter checks for the boolean specifically.
- The MCP Adapter is a separate package. Attendee sites in this workshop don't have it installed (the presenter's demo site does), so the opt-in is a no-op locally until something is listening for it. That's fine — register correctly now, and the ability is ready the moment an MCP server is present.

### Confirm It Registered

Before testing the endpoint, sanity-check that WordPress sees the ability. Go to **Settings → AI → Abilities Explorer** — you should see your **WC Portugal 2026** category listed, with **Summarize Content** (`wcpt/summarization`) underneath it. If it's there, registration worked. If it's not, recheck the hooks (`wp_abilities_api_categories_init` and `wp_abilities_api_init`) before moving on.

## The Execute Callback

3. Add the callback that does the actual work. It receives the validated input and must return a string:

```php
/**
 * Execute callback for the wcpt/summarization ability.
 *
 * @param array $input Validated input matching `input_schema`.
 *                     Keys: 'content' (string), 'length' (short|medium|long).
 * @return string|WP_Error Generated summary, or WP_Error on provider failure.
 */
function wcpt_execute_summarization( $input ) {
	$content = $input['content'];
	$length  = $input['length'] ?? 'medium';

	// Translate the schema's length enum into concrete instructions for
	// the model. Keeping the enum on the schema and the wording in PHP
	// means we can tweak prompts without touching the public contract.
	$length_instruction = array(
		'short'  => 'Write a single sentence summary of no more than 25 words.',
		'medium' => 'Write a 2-3 sentence summary of 25-80 words.',
		'long'   => 'Write a 4-6 sentence summary of 80-160 words.',
	);

	$prompt = sprintf(
		"Summarize the following content. %s Use plain text only — no markdown, no bullet points. Do not introduce information not present in the source.\n\nContent:\n%s",
		$length_instruction[ $length ],
		$content
	);

	// Returning the raw result is fine: a string flows through to the
	// caller; a WP_Error is surfaced by the Abilities API as a typed REST
	// error (e.g. `ability_invalid_output`).
	return wp_ai_client_prompt( $prompt )->generate_text();
}
```

## Test It from the wp-admin Console

No JavaScript file, no curl, no application password — let's confirm the ability is callable directly from the browser DevTools console using `wp.apiFetch`, which is already loaded on every wp-admin page and authenticates with your existing admin session cookie + nonce.

4. In the WordPress admin, navigate to any non-editor admin screen — **Posts → All Posts** works well. (Avoid the post editor itself: the editor canvas runs inside an iframe and `wp.apiFetch` isn't always available on the top-level `window` there.)

5. Open your browser's DevTools (Cmd+Opt+I on macOS, Ctrl+Shift+I on Windows/Linux) and switch to the **Console** tab.

6. Paste this in and hit Enter. It calls the REST endpoint WordPress generated for our ability, captures the response, and logs the summary:

```js
const response = await wp.apiFetch( {
    path: '/wp-abilities/v1/abilities/wcpt/summarization/run',
    method: 'POST',
    data: {
        input: {
            content: 'WordPress is open source software you can use to create a beautiful website, blog, or app. Beautiful designs, powerful features, and the freedom to build anything you want.',
            length: 'long',
        },
    },
} );

console.log( 'Summary:', response.output );
```

You should see `Summary:` followed by a plain-text string — a 4–6 sentence summary of the content (because we asked for `"length": "long"`). 🔥

A couple of contract details worth noticing — these are the same ones our JavaScript will rely on in Section 4:

-   Input goes inside an `input` wrapper. That's the REST contract the Abilities API generates from your `input_schema`.
-   The response is an object; the ability's return value is on `response.output`. Our `output_schema` declared a string, so `response.output` is a string.

7. Run it again with `length: 'short'` and watch the console — you should get a single-sentence summary instead. Same ability, same content, different input field.

---

# Ready to move on?

[Section 4: Block Editor Integration](./section-4.md)

# Missing something from the last section?

[Section 2: Scaffold the Plugin & Connect to the AI API](./section-2.md)
