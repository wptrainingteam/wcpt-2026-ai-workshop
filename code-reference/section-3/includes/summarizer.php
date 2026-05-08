<?php

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
			'meta'                => array(
				'show_in_rest' => true,
			),
		)
	);
}
add_action( 'wp_abilities_api_init', 'wcpt_register_summarization_ability' );

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
