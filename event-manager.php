<?php
/**
 * Plugin Name: Event Manager
 * Plugin URI: https://example.com/event-manager
 * Description: Manage conferences, seminars and events with speakers, organizers, and hierarchical relationships
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: event-manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EVENT_MANAGER_VERSION', '1.0.0');
define('EVENT_MANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EVENT_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EVENT_MANAGER_EVENT_TEMPLATE', 'event-manager-event');

/**
 * Check if a page uses the Event block template
 */
function event_manager_is_event_page($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    $template = get_page_template_slug($post_id);
    // Block themes store template as "theme//slug", classic themes store just the slug
    return $template === EVENT_MANAGER_EVENT_TEMPLATE
        || $template === get_stylesheet() . '//' . EVENT_MANAGER_EVENT_TEMPLATE;
}

/**
 * Meta query to find pages using the Event template (handles both block and classic theme formats)
 */
function event_manager_event_page_meta_query() {
    return array(
        'relation' => 'OR',
        array(
            'key' => '_wp_page_template',
            'value' => EVENT_MANAGER_EVENT_TEMPLATE,
        ),
        array(
            'key' => '_wp_page_template',
            'value' => get_stylesheet() . '//' . EVENT_MANAGER_EVENT_TEMPLATE,
        ),
    );
}

/**
 * Register the Event template slug with WordPress so:
 * - The "Page Attributes" template dropdown shows "Event" as an option.
 * - The REST API accepts the slug when saving via the block editor.
 */
add_filter('theme_page_templates', function ($templates) {
    $templates[EVENT_MANAGER_EVENT_TEMPLATE] = __('Event', 'event-manager');
    return $templates;
});

/**
 * Serve the plugin's PHP event template when a page uses the Event template.
 */
add_filter('template_include', function ($template) {
    if (!is_page() || !event_manager_is_event_page(get_the_ID())) {
        return $template;
    }
    // Allow the active theme to override the template.
    $theme_override = locate_template(EVENT_MANAGER_EVENT_TEMPLATE . '.php');
    if ($theme_override) {
        return $theme_override;
    }
    return EVENT_MANAGER_PLUGIN_DIR . 'templates/event-manager-event.php';
});

/**
 * Enable post categories on pages so events can be categorised
 */
add_action('init', function () {
    register_taxonomy_for_object_type('category', 'page');
});

// Include required files
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/speakers/post-type.php';
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/speakers/shortcode.php';
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/venues/post-type.php';
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/venues/shortcode.php';
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/events/metabox.php';
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/events/shortcode.php';
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/events/admin-menu.php';

/**
 * Enqueue admin scripts and styles
 */
function event_manager_enqueue_admin_scripts($hook) {
    if ('post.php' !== $hook && 'post-new.php' !== $hook) {
        return;
    }

    wp_enqueue_style('event-manager-admin', EVENT_MANAGER_PLUGIN_URL . 'assets/css/admin.css', array(), EVENT_MANAGER_VERSION);
}
add_action('admin_enqueue_scripts', 'event_manager_enqueue_admin_scripts');

/**
 * Theme compatibility wrappers
 * Default wrappers that can be overridden by themes
 */
if (!function_exists('event_manager_output_content_wrapper')) {
    function event_manager_output_content_wrapper() {
        echo '<div class="event-manager-content">';
    }
}

if (!function_exists('event_manager_output_content_wrapper_end')) {
    function event_manager_output_content_wrapper_end() {
        echo '</div>';
    }
}

add_action('event_manager_before_main_content', 'event_manager_output_content_wrapper', 10);
add_action('event_manager_after_main_content', 'event_manager_output_content_wrapper_end', 10);

/**
 * Activation hook
 */
function event_manager_activate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'event_manager_activate');

/**
 * Deactivation hook
 */
function event_manager_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'event_manager_deactivate');
