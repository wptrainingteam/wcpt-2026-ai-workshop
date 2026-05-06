<?php
/**
 * Plugin Name: WC Portugal 2026 — Content Summarizer
 * Description: A hands-on workshop plugin for WordCamp Portugal 2026.
 * Version:     0.1.0
 * Requires at least: 7.0
 * Requires PHP:      8.1
 * Text Domain: wcpt
 *
 * @package WCPT
 */

if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
	return;
}

require_once __DIR__ . '/includes/summarizer.php';
