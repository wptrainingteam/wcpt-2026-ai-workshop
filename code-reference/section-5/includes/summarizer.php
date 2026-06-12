<?php

/**
 * Register the workshop's ability category.
 *
 * Categories group abilities in the Abilities Explorer and let JS clients
 * filter with `getAbilities( { category: 'wp-ai-workshop' } )`.
 */
function wp_ai_workshop_register_ability_category() {
	wp_register_ability_category(
		'wp-ai-workshop',
		array(
			'label'       => __( 'WordPress AI Workshop', 'wp-ai-workshop' ),
			'description' => __( 'Abilities built during the WordPress AI Building Blocks workshop.', 'wp-ai-workshop' ),
		)
	);
}
// `wp_abilities_api_categories_init` fires before `wp_abilities_api_init`,
// so the category exists by the time abilities try to reference it.
add_action( 'wp_abilities_api_categories_init', 'wp_ai_workshop_register_ability_category' );

/**
 * Register the wp-ai-workshop/summarization ability.
 */
function wp_ai_workshop_register_summarization_ability() {
	wp_register_ability(
		'wp-ai-workshop/summarization',
		array(
			'label'               => __( 'Summarize Content', 'wp-ai-workshop' ),
			'description'         => __( 'Generates a plain-text summary of the provided content.', 'wp-ai-workshop' ),
			'category'            => 'wp-ai-workshop',
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
			'execute_callback'    => 'wp_ai_workshop_execute_summarization',
			// `show_in_rest => true` is what auto-creates the REST endpoint
			// at /wp-json/wp-abilities/v1/abilities/wp-ai-workshop/summarization/run.
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
add_action( 'wp_abilities_api_init', 'wp_ai_workshop_register_summarization_ability' );

/**
 * Execute callback for the wp-ai-workshop/summarization ability.
 *
 * @param array $input Validated input matching `input_schema`.
 *                     Keys: 'content' (string), 'length' (short|medium|long).
 * @return string|WP_Error Generated summary, or WP_Error on provider failure.
 */
function wp_ai_workshop_execute_summarization( $input ) {
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

/**
 * Register and enqueue the editor script module.
 *
 * Why this looks the way it does:
 *
 * - `@wordpress/abilities` is published only as a runtime ES module — it
 *   can only be loaded through WordPress's script module loader.
 * - Our bundle is built by `@wordpress/scripts` as a *classic* script, not
 *   an ES module. But to declare `@wordpress/abilities` as a dependency we
 *   have to enqueue our file with `wp_enqueue_script_module()` so the
 *   loader can wire the dependency graph. So: classic-script body,
 *   script-module enqueue. The runtime `await import()` in `src/index.js`
 *   is what actually pulls in `@wordpress/abilities`.
 * - The two `wp_enqueue_script_module()` calls for
 *   `@wordpress/core-abilities` and `@wordpress/abilities` are a temporary
 *   shim — once 7.0 ships these will be auto-registered and can be removed.
 */
function wp_ai_workshop_enqueue_script_modules() {
	// `index.asset.php` is generated by `@wordpress/scripts` and contains
	// the build hash plus the WP package dependencies webpack found.
	$asset_file = plugin_dir_path( __DIR__ ) . 'build/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}
	$assets = require $asset_file;

	// Required as of WordPress 7.0 RC4 — the script-module loader does not
	// auto-register these yet. Re-test after each RC; remove these two lines
	// once they're registered automatically.
	wp_enqueue_script_module( '@wordpress/core-abilities' );
	wp_enqueue_script_module( '@wordpress/abilities' );

	wp_enqueue_script_module(
		'wp-ai-workshop-summarization',
		plugins_url( 'build/index.js', __DIR__ ),
		array( '@wordpress/abilities' ),
		$assets['version']
	);
}
add_action( 'enqueue_block_editor_assets', 'wp_ai_workshop_enqueue_script_modules' );
