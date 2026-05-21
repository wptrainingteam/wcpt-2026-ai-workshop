<?php
/**
 * Plugin Name: WordPress AI Workshop — Content Summarizer
 * Description: A hands-on workshop plugin teaching the WordPress 7.0 AI Building Blocks.
 * Version:     0.1.0
 * Requires at least: 7.0
 * Requires PHP:      8.1
 * Text Domain: wp-ai-workshop
 */

// The PHP AI Client (`wp_ai_client_prompt()`) ships in WordPress 7.0 core.
// On older versions the function isn't defined yet — bail out silently so
// the plugin doesn't fatal during an upgrade.
if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
	return;
}

// All plugin logic lives in includes/summarizer.php.
require_once __DIR__ . '/includes/summarizer.php';
