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
 * Register the Event template slug so the block editor and REST API accept it.
 * - theme_page_templates: keeps classic-theme and REST API validation happy.
 * - register_block_template (WP 6.7+) / get_block_templates filter (older WP):
 *   makes the block template available so block-theme pages actually render with
 *   page-event.html instead of falling back to the default page template.
 */
add_filter('theme_page_templates', function ($templates) {
    $templates[EVENT_MANAGER_EVENT_TEMPLATE] = __('Event', 'event-manager');
    return $templates;
});

add_action('init', 'event_manager_register_block_template');

function event_manager_register_block_template() {
    $template_file = EVENT_MANAGER_PLUGIN_DIR . 'templates/page-event.html';
    if (!file_exists($template_file)) {
        return;
    }
    $content = file_get_contents($template_file);

    if (function_exists('register_block_template')) {
        // WP 6.7+: official plugin template registration.
        // The plugin directory name is used as the namespace.
        register_block_template(
            basename(EVENT_MANAGER_PLUGIN_DIR) . '//' . EVENT_MANAGER_EVENT_TEMPLATE,
            array(
                'title'      => __('Event', 'event-manager'),
                'content'    => $content,
                'post_types' => array('page'),
            )
        );
        return;
    }

    // WP < 6.7 fallback: inject the template object via filters.
    add_filter('get_block_templates',   'event_manager_inject_block_template', 10, 3);
    add_filter('get_block_file_template', 'event_manager_get_block_file_template', 10, 3);
}

/** Build a WP_Block_Template object for the Event template. */
function event_manager_build_block_template_object() {
    $template_file = EVENT_MANAGER_PLUGIN_DIR . 'templates/page-event.html';

    $t = new WP_Block_Template();
    $t->type           = 'wp_template';
    $t->theme          = get_stylesheet();
    $t->slug           = EVENT_MANAGER_EVENT_TEMPLATE;
    $t->id             = get_stylesheet() . '//' . EVENT_MANAGER_EVENT_TEMPLATE;
    $t->title          = __('Event', 'event-manager');
    $t->description    = '';
    $t->status         = 'publish';
    $t->has_theme_file = false;
    $t->is_custom      = false;
    $t->source         = 'plugin';
    $t->origin         = 'plugin';
    $t->content        = file_get_contents($template_file);
    $t->post_types     = array('page');
    return $t;
}

function event_manager_inject_block_template($query_result, $query, $template_type) {
    if ('wp_template' !== $template_type) {
        return $query_result;
    }
    // Skip if the query is for specific slugs and ours isn't requested.
    if (!empty($query['slug__in']) && !in_array(EVENT_MANAGER_EVENT_TEMPLATE, $query['slug__in'], true)) {
        return $query_result;
    }
    // Skip if already present (e.g. saved as a customisation in the DB).
    foreach ($query_result as $t) {
        if ($t->slug === EVENT_MANAGER_EVENT_TEMPLATE) {
            return $query_result;
        }
    }
    $query_result[] = event_manager_build_block_template_object();
    return $query_result;
}

function event_manager_get_block_file_template($template, $id, $template_type) {
    if ('wp_template' !== $template_type || !is_null($template)) {
        return $template;
    }
    $expected_id = get_stylesheet() . '//' . EVENT_MANAGER_EVENT_TEMPLATE;
    if ($id !== $expected_id) {
        return $template;
    }
    return event_manager_build_block_template_object();
}

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
