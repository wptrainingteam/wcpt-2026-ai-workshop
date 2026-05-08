<?php

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
