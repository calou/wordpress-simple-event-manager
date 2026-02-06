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

// Include required files
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/class-speaker.php';
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/class-venue.php';
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/class-event-metabox.php';

/**
 * Main Event Manager Class
 */
class Event_Manager {

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize components
        new Event_Manager_Speaker();
        new Event_Manager_Venue();
        new Event_Manager_Metabox();

        // Add custom page templates
        add_filter('theme_page_templates', array($this, 'add_event_template'));
        add_filter('template_include', array($this, 'load_event_template'));

        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Add custom page template to the dropdown
     */
    public function add_event_template($templates) {
        $templates['event-template.php'] = __('Event Template', 'event-manager');
        return $templates;
    }

    /**
     * Load custom page template
     */
    public function load_event_template($template) {
        global $post;

        if (!$post) {
            return $template;
        }

        $page_template = get_post_meta($post->ID, '_wp_page_template', true);

        if ($page_template == 'event-template.php') {
            $plugin_template = EVENT_MANAGER_PLUGIN_DIR . 'templates/event-template.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }

        wp_enqueue_style('event-manager-admin', EVENT_MANAGER_PLUGIN_URL . 'assets/css/admin.css', array(), EVENT_MANAGER_VERSION);
    }
}

// Initialize the plugin
function event_manager_init() {
    new Event_Manager();
}
add_action('plugins_loaded', 'event_manager_init');

/**
 * Helper function to get event data from JSON metadata
 */
function event_manager_get_event_data($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $json_data = get_post_meta($post_id, '_event_data', true);
    
    // Default structure
    $default_data = array(
        'start_date' => '',
        'end_date' => '',
        'registration_deadline' => '',
        'registration_url' => '',
        'venue_id' => '',
        'parent_event_id' => '',
        'speaker_ids' => array(),
        'organizer_ids' => array(),
    );
    
    if (!empty($json_data)) {
        $decoded = json_decode($json_data, true);
        if (is_array($decoded)) {
            return array_merge($default_data, $decoded);
        }
    }
    
    return $default_data;
}

/**
 * Helper function to get venue data
 */
function event_manager_get_venue_data($venue_id) {
    if (empty($venue_id)) {
        return null;
    }

    $venue = get_post($venue_id);
    if (!$venue || $venue->post_type !== 'venue') {
        return null;
    }

    return array(
        'id' => $venue->ID,
        'name' => $venue->post_title,
        'description' => $venue->post_content,
        'address' => get_post_meta($venue_id, '_venue_address', true),
        'city' => get_post_meta($venue_id, '_venue_city', true),
        'state' => get_post_meta($venue_id, '_venue_state', true),
        'zip' => get_post_meta($venue_id, '_venue_zip', true),
        'country' => get_post_meta($venue_id, '_venue_country', true),
        'capacity' => get_post_meta($venue_id, '_venue_capacity', true),
        'phone' => get_post_meta($venue_id, '_venue_phone', true),
        'website' => get_post_meta($venue_id, '_venue_website', true),
        'thumbnail' => get_the_post_thumbnail_url($venue_id, 'medium'),
    );
}

/**
 * Helper function to get speaker data
 */
function event_manager_get_speaker_data($speaker_id) {
    if (empty($speaker_id)) {
        return null;
    }

    $speaker = get_post($speaker_id);
    if (!$speaker || $speaker->post_type !== 'speaker') {
        return null;
    }

    $json_data = get_post_meta($speaker_id, '_speaker_data', true);
    $speaker_meta = array(
        'position' => '',
        'organization' => '',
        'organization_url' => '',
        'orcid' => '',
        'email' => '',
        'phone' => '',
        'website' => '',
        'linkedin' => '',
    );

    if (!empty($json_data)) {
        $decoded = json_decode($json_data, true);
        if (is_array($decoded)) {
            $speaker_meta = array_merge($speaker_meta, $decoded);
        }
    }

    return array(
        'id' => $speaker->ID,
        'name' => $speaker->post_title,
        'bio' => $speaker->post_content,
        'excerpt' => $speaker->post_excerpt,
        'position' => $speaker_meta['position'],
        'organization' => $speaker_meta['organization'],
        'organization_url' => $speaker_meta['organization_url'],
        'orcid' => $speaker_meta['orcid'],
        'email' => $speaker_meta['email'],
        'phone' => $speaker_meta['phone'],
        'website' => $speaker_meta['website'],
        'linkedin' => $speaker_meta['linkedin'],
        'picture' => get_the_post_thumbnail_url($speaker_id, 'medium'),
        'picture_large' => get_the_post_thumbnail_url($speaker_id, 'large'),
    );
}

/**
 * Helper function to format event dates
 */
function event_manager_format_date($datetime_string, $format = 'F j, Y g:i A') {
    if (empty($datetime_string)) {
        return '';
    }
    
    $datetime = DateTime::createFromFormat('Y-m-d\TH:i', $datetime_string);
    if ($datetime) {
        return $datetime->format($format);
    }
    
    return $datetime_string;
}

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
