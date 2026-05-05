# Section 3 — Registering the Summarization Ability

The Abilities API gives our AI feature a standard, discoverable interface. Instead of a bespoke REST route, we register an Ability with a defined input/output schema — and WordPress automatically exposes it as a REST endpoint that any client (including AI agents) can call.

Reference: [Abilities API](https://developer.wordpress.org/apis/abilities-api/)

## Register a Category

Abilities are organized into categories. Let's register one for our plugin.

1. In `includes/summarizer.php`, add:

```php
function wcpt_register_ability_category() {
	wp_register_ability_category(
		'wcpt',
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
		'ai/summarization',
		array(
			'label'               => __( 'Summarize Content', 'wcpt' ),
			'description'         => __( 'Generates a plain-text summary of the provided content.', 'wcpt' ),
			'category'            => 'wcpt',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'content' => array(
						'type'        => 'string',
						'description' => 'The content to summarize.',
					),
					'length'  => array(
						'type'    => 'string',
						'enum'    => array( 'short', 'medium', 'long' ),
						'default' => 'medium',
						'description' => 'The desired length of the summary.',
					),
				),
				'required' => array( 'content' ),
			),
			'output_schema'       => array(
				'type'        => 'string',
				'description' => 'The generated summary.',
			),
			'permission_callback' => function() {
				return current_user_can( 'edit_posts' );
			},
			'execute_callback'    => 'wcpt_execute_summarization',
		)
	);
}
add_action( 'wp_abilities_api_init', 'wcpt_register_summarization_ability' );
```

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

	$client   = wp_ai_get_client();
	$response = $client->text()->generate( $prompt );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	return $response;
}
```

## Test It via the REST API

No JavaScript needed yet — let's confirm the ability is callable via the REST endpoint WordPress created for us automatically.

4. Use curl from your terminal (replace `yoursite.local` with your Studio site URL, and use an application password from **Users → Profile → Application Passwords**):

```bash
curl -X POST "http://yoursite.local/wp-json/wp-abilities/v1/abilities/ai/summarization/run" \
  -u "admin:your-application-password" \
  -H "Content-Type: application/json" \
  -d '{
    "input": {
      "content": "WordPress is open source software you can use to create a beautiful website, blog, or app. Beautiful designs, powerful features, and the freedom to build anything you want.",
      "length": "short"
    }
  }'
```

You should get back a plain-text string — a one-sentence summary of the content. 🔥

5. Try it with `"length": "long"` and see the difference.

---

# Ready to move on?
[Section 4: Block Editor Integration](./section-4.md)

# Missing something from the last section?
[Section 2: Scaffold the Plugin & Connect to the AI API](./section-2.md)
