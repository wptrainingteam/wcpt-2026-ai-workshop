# Section 3 — Registering the Summarization Ability

The Abilities API gives our AI feature a standard, discoverable interface. Instead of a bespoke REST route, we register an Ability with a defined input/output schema — and WordPress automatically exposes it as a REST endpoint that any client (including AI agents) can call.

> **End-of-section reference:** `code-reference/section-3/`

Reference: [Abilities API](https://developer.wordpress.org/apis/abilities-api/)

## Register a Category

Abilities are organized into categories. Let's register one for our plugin.

1. In `includes/summarizer.php`, add:

```php
function wcpt_register_ability_category() {
	wp_register_ability_category(
		'wcpt-workshop',
		array(
			'label'       => __( 'WC Portugal 2026', 'wcpt' ),
			'description' => __( 'Abilities built during the WordCamp Portugal 2026 workshop.', 'wcpt' ),
		)
	);
}
add_action( 'wp_abilities_api_categories_init', 'wcpt_register_ability_category' );
```

## Register the Ability

2. Now register the ability itself. Add this below the category registration:

```php
function wcpt_register_summarization_ability() {
	wp_register_ability(
		'wcpt/summarization',
		array(
			'label'               => __( 'Summarize Content', 'wcpt' ),
			'description'         => __( 'Generates a plain-text summary of the provided content.', 'wcpt' ),
			'category'            => 'wcpt-workshop',
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
			'output_schema'       => array(
				'type'        => 'string',
				'description' => 'The generated summary.',
			),
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'execute_callback'    => 'wcpt_execute_summarization',
			'meta'                => array(
				'show_in_rest' => true,
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

The route follows the pattern `/<namespace>/<ability-slug>/run` — so the `wcpt/summarization` ability becomes `/wp-abilities/v1/abilities/wcpt/summarization/run`. (For comparison, the reference summarization ability in the WordPress/ai plugin is registered as `ai/summarization` and lives at `/wp-abilities/v1/abilities/ai/summarization/run`.) We'll hit this endpoint with `curl` in a moment to confirm the ability works before we touch any JavaScript.

### Confirm It Registered

Before testing the endpoint, sanity-check that WordPress sees the ability. Go to **Settings → AI → Abilities Explorer** — you should see your **WC Portugal 2026** category listed, with **Summarize Content** (`wcpt/summarization`) underneath it. If it's there, registration worked. If it's not, recheck the hooks (`wp_abilities_api_categories_init` and `wp_abilities_api_init`) before moving on.

## The Execute Callback

3. Add the callback that does the actual work. It receives the validated input and must return a string:

```php
function wcpt_execute_summarization( $input ) {
	$content = $input['content'];
	$length  = $input['length'] ?? 'medium';

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

	$response = wp_ai_client_prompt( $prompt )->generate_text();

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	return $response;
}
```

## Test It via the REST API

No JavaScript needed yet — let's confirm the ability is callable via the REST endpoint WordPress created for us automatically.

### Create an Application Password

The REST API requires authentication. We'll use an application password — a per-app credential separate from your login password.

4. In the WordPress admin, go to **Users → Profile** and scroll to the **Application Passwords** section.

5. In the **New Application Password Name** field, enter `workshop` (or any label you like) and click **Add New Application Password**.

6. WordPress will display the generated password as a string with spaces (e.g. `abcd 1234 efgh 5678`). **Copy it now** — it won't be shown again. You can keep or strip the spaces; both work.

### Call the Endpoint

7. Use curl from your terminal (replace `yoursite.local` with your Studio site URL, and paste the application password you just generated):

```bash
curl -X POST "{YOUR_SITE_URL}/wp-json/wp-abilities/v1/abilities/wcpt/summarization/run" \
  -u "admin:{YOU_APPLICATION_PASSWORD}" \
  -H "Content-Type: application/json" \
  -d '{
    "input": {
      "content": "WordPress is open source software you can use to create a beautiful website, blog, or app. Beautiful designs, powerful features, and the freedom to build anything you want.",
      "length": "long"
    }
  }'
```

You should get back a plain-text string — a one-sentence summary of the content. 🔥

8. Try it with `"length": "long"` and see the difference.

---

# Ready to move on?

[Section 4: Block Editor Integration](./section-4.md)

# Missing something from the last section?

[Section 2: Scaffold the Plugin & Connect to the AI API](./section-2.md)
