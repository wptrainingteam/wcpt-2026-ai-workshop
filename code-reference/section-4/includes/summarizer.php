<?php
/**
 * Content Summarization ability and editor integration.
 *
 * @package WCPT
 */

/**
 * Register the workshop's ability category.
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
add_action( 'wp_abilities_api_categories_init', 'wcpt_register_ability_category' );

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

/**
 * Build the prompt and call the AI client.
 *
 * @param array $input Validated input matching the registered input schema.
 * @return string|WP_Error Generated summary, or WP_Error on failure.
 */
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

	return wp_ai_client_prompt( $prompt )->generate_text();
}

/**
 * Register and enqueue the editor script module.
 *
 * Why this looks the way it does:
 *
 * - `@wordpress/abilities` is published only as a runtime ES module — it can
 *   only be loaded through WordPress's script module loader.
 * - Our bundle is built by `@wordpress/scripts` as a *classic* script, not an
 *   ES module. But to declare `@wordpress/abilities` as a dependency we have
 *   to enqueue our file with `wp_enqueue_script_module()` so the loader can
 *   wire the dependency graph. So: classic-script body, script-module enqueue.
 *   The runtime `await import()` in `src/index.js` is what actually pulls in
 *   `@wordpress/abilities` — see the comment there.
 * - Script modules currently only register correctly on the
 *   `admin_enqueue_scripts` hook in this scenario, not
 *   `enqueue_block_editor_assets`. We narrow the screen check to post/page
 *   edit screens so we don't load on every admin page.
 * - The two `wp_enqueue_script_module()` calls for `@wordpress/core-abilities`
 *   and `@wordpress/abilities` are a temporary shim — once 7.0 ships these
 *   will be auto-registered and can be removed.
 */
function wcpt_enqueue_script_modules() {
	$screen          = get_current_screen();
	$allowed_screens = array( 'post', 'page' );
	if ( ! in_array( $screen->id, $allowed_screens, true ) ) {
		return;
	}

	$asset_file = plugin_dir_path( __DIR__ ) . 'build/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}
	$assets = require $asset_file;

	// Should be removed once 7.0 is released.
	wp_enqueue_script_module( '@wordpress/core-abilities' );
	wp_enqueue_script_module( '@wordpress/abilities' );

	wp_enqueue_script_module(
		'wcpt-summarization',
		plugins_url( 'build/index.js', __DIR__ ),
		array( '@wordpress/abilities' ),
		$assets['version']
	);
}
add_action( 'admin_enqueue_scripts', 'wcpt_enqueue_script_modules' );
