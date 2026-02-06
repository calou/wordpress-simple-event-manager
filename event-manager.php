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

/**
 * Main Event Manager Class
 */
class Event_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register custom post types
        add_action('init', array($this, 'register_speaker_post_type'));
        add_action('init', array($this, 'register_venue_post_type'));
        
        // Add meta boxes for pages
        add_action('add_meta_boxes', array($this, 'add_event_meta_boxes'));
        
        // Save meta box data
        add_action('save_post_page', array($this, 'save_event_meta'));
        
        // Add custom page templates
        add_filter('theme_page_templates', array($this, 'add_event_template'));
        add_filter('template_include', array($this, 'load_event_template'));
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Register Speaker Custom Post Type
     */
    public function register_speaker_post_type() {
        $labels = array(
            'name'                  => _x('Speakers', 'Post Type General Name', 'event-manager'),
            'singular_name'         => _x('Speaker', 'Post Type Singular Name', 'event-manager'),
            'menu_name'             => __('Speakers', 'event-manager'),
            'name_admin_bar'        => __('Speaker', 'event-manager'),
            'archives'              => __('Speaker Archives', 'event-manager'),
            'attributes'            => __('Speaker Attributes', 'event-manager'),
            'parent_item_colon'     => __('Parent Speaker:', 'event-manager'),
            'all_items'             => __('All Speakers', 'event-manager'),
            'add_new_item'          => __('Add New Speaker', 'event-manager'),
            'add_new'               => __('Add New', 'event-manager'),
            'new_item'              => __('New Speaker', 'event-manager'),
            'edit_item'             => __('Edit Speaker', 'event-manager'),
            'update_item'           => __('Update Speaker', 'event-manager'),
            'view_item'             => __('View Speaker', 'event-manager'),
            'view_items'            => __('View Speakers', 'event-manager'),
            'search_items'          => __('Search Speaker', 'event-manager'),
            'not_found'             => __('Not found', 'event-manager'),
            'not_found_in_trash'    => __('Not found in Trash', 'event-manager'),
        );
        
        $args = array(
            'label'                 => __('Speaker', 'event-manager'),
            'description'           => __('Event speakers', 'event-manager'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 20,
            'menu_icon'             => 'dashicons-businessperson',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );
        
        register_post_type('speaker', $args);
    }
    
    /**
     * Register Venue Custom Post Type
     */
    public function register_venue_post_type() {
        $labels = array(
            'name'                  => _x('Venues', 'Post Type General Name', 'event-manager'),
            'singular_name'         => _x('Venue', 'Post Type Singular Name', 'event-manager'),
            'menu_name'             => __('Venues', 'event-manager'),
            'name_admin_bar'        => __('Venue', 'event-manager'),
            'archives'              => __('Venue Archives', 'event-manager'),
            'attributes'            => __('Venue Attributes', 'event-manager'),
            'parent_item_colon'     => __('Parent Venue:', 'event-manager'),
            'all_items'             => __('All Venues', 'event-manager'),
            'add_new_item'          => __('Add New Venue', 'event-manager'),
            'add_new'               => __('Add New', 'event-manager'),
            'new_item'              => __('New Venue', 'event-manager'),
            'edit_item'             => __('Edit Venue', 'event-manager'),
            'update_item'           => __('Update Venue', 'event-manager'),
            'view_item'             => __('View Venue', 'event-manager'),
            'view_items'            => __('View Venues', 'event-manager'),
            'search_items'          => __('Search Venue', 'event-manager'),
            'not_found'             => __('Not found', 'event-manager'),
            'not_found_in_trash'    => __('Not found in Trash', 'event-manager'),
        );
        
        $args = array(
            'label'                 => __('Venue', 'event-manager'),
            'description'           => __('Event venues', 'event-manager'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 21,
            'menu_icon'             => 'dashicons-location',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );
        
        register_post_type('venue', $args);
        
        // Add venue meta boxes
        add_action('add_meta_boxes_venue', array($this, 'add_venue_meta_boxes'));
        add_action('save_post_venue', array($this, 'save_venue_meta'));
    }
    
    /**
     * Add meta boxes to venue post type
     */
    public function add_venue_meta_boxes() {
        add_meta_box(
            'venue_details',
            __('Venue Details', 'event-manager'),
            array($this, 'render_venue_meta_box'),
            'venue',
            'normal',
            'high'
        );
    }
    
    /**
     * Render venue meta box
     */
    public function render_venue_meta_box($post) {
        wp_nonce_field('venue_meta_box', 'venue_meta_box_nonce');
        
        $address = get_post_meta($post->ID, '_venue_address', true);
        $city = get_post_meta($post->ID, '_venue_city', true);
        $state = get_post_meta($post->ID, '_venue_state', true);
        $zip = get_post_meta($post->ID, '_venue_zip', true);
        $country = get_post_meta($post->ID, '_venue_country', true);
        $capacity = get_post_meta($post->ID, '_venue_capacity', true);
        $phone = get_post_meta($post->ID, '_venue_phone', true);
        $website = get_post_meta($post->ID, '_venue_website', true);
        
        ?>
        <div class="venue-meta-box">
            <p>
                <label for="venue_address"><strong><?php _e('Address:', 'event-manager'); ?></strong></label><br>
                <input type="text" id="venue_address" name="venue_address" value="<?php echo esc_attr($address); ?>" style="width: 100%;">
            </p>
            
            <p>
                <label for="venue_city"><strong><?php _e('City:', 'event-manager'); ?></strong></label><br>
                <input type="text" id="venue_city" name="venue_city" value="<?php echo esc_attr($city); ?>" style="width: 100%;">
            </p>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label for="venue_state"><strong><?php _e('State/Province:', 'event-manager'); ?></strong></label><br>
                    <input type="text" id="venue_state" name="venue_state" value="<?php echo esc_attr($state); ?>" style="width: 100%;">
                </div>
                
                <div>
                    <label for="venue_zip"><strong><?php _e('ZIP/Postal Code:', 'event-manager'); ?></strong></label><br>
                    <input type="text" id="venue_zip" name="venue_zip" value="<?php echo esc_attr($zip); ?>" style="width: 100%;">
                </div>
            </div>
            
            <p>
                <label for="venue_country"><strong><?php _e('Country:', 'event-manager'); ?></strong></label><br>
                <input type="text" id="venue_country" name="venue_country" value="<?php echo esc_attr($country); ?>" style="width: 100%;">
            </p>
            
            <p>
                <label for="venue_capacity"><strong><?php _e('Capacity:', 'event-manager'); ?></strong></label><br>
                <input type="number" id="venue_capacity" name="venue_capacity" value="<?php echo esc_attr($capacity); ?>" style="width: 100%; max-width: 200px;">
            </p>
            
            <p>
                <label for="venue_phone"><strong><?php _e('Phone:', 'event-manager'); ?></strong></label><br>
                <input type="tel" id="venue_phone" name="venue_phone" value="<?php echo esc_attr($phone); ?>" style="width: 100%; max-width: 300px;">
            </p>
            
            <p>
                <label for="venue_website"><strong><?php _e('Website:', 'event-manager'); ?></strong></label><br>
                <input type="url" id="venue_website" name="venue_website" value="<?php echo esc_attr($website); ?>" style="width: 100%;" placeholder="https://">
            </p>
        </div>
        <?php
    }
    
    /**
     * Save venue meta box data
     */
    public function save_venue_meta($post_id) {
        if (!isset($_POST['venue_meta_box_nonce']) || !wp_verify_nonce($_POST['venue_meta_box_nonce'], 'venue_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $fields = array('address', 'city', 'state', 'zip', 'country', 'capacity', 'phone', 'website');
        
        foreach ($fields as $field) {
            if (isset($_POST['venue_' . $field])) {
                $value = sanitize_text_field($_POST['venue_' . $field]);
                update_post_meta($post_id, '_venue_' . $field, $value);
            }
        }
    }
    
    /**
     * Add event meta boxes to pages
     */
    public function add_event_meta_boxes() {
        add_meta_box(
            'event_details',
            __('Event Details', 'event-manager'),
            array($this, 'render_event_meta_box'),
            'page',
            'normal',
            'high'
        );
    }
    
    /**
     * Render the event meta box
     */
    public function render_event_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('event_meta_box', 'event_meta_box_nonce');
        
        // Get existing event data from JSON
        $event_data = $this->get_event_data($post->ID);
        
        ?>
        <div class="event-meta-box">
            <p>
                <label for="event_start_date"><strong><?php _e('Start Date:', 'event-manager'); ?></strong></label><br>
                <input type="datetime-local" id="event_start_date" name="event_start_date" value="<?php echo esc_attr($event_data['start_date']); ?>" style="width: 100%; max-width: 300px;">
            </p>
            
            <p>
                <label for="event_end_date"><strong><?php _e('End Date:', 'event-manager'); ?></strong></label><br>
                <input type="datetime-local" id="event_end_date" name="event_end_date" value="<?php echo esc_attr($event_data['end_date']); ?>" style="width: 100%; max-width: 300px;">
            </p>
            
            <p>
                <label for="event_registration_deadline"><strong><?php _e('Registration Deadline:', 'event-manager'); ?></strong></label><br>
                <input type="datetime-local" id="event_registration_deadline" name="event_registration_deadline" value="<?php echo esc_attr($event_data['registration_deadline']); ?>" style="width: 100%; max-width: 300px;">
            </p>
            
            <p>
                <label for="event_registration_url"><strong><?php _e('Registration URL:', 'event-manager'); ?></strong></label><br>
                <input type="url" id="event_registration_url" name="event_registration_url" value="<?php echo esc_attr($event_data['registration_url']); ?>" style="width: 100%;" placeholder="https://">
                <span class="description"><?php _e('Link to registration page or form', 'event-manager'); ?></span>
            </p>
            
            <p>
                <label for="event_venue"><strong><?php _e('Venue:', 'event-manager'); ?></strong></label><br>
                <select id="event_venue" name="event_venue" style="width: 100%; max-width: 400px;">
                    <option value=""><?php _e('Select a venue', 'event-manager'); ?></option>
                    <?php
                    $venues = get_posts(array(
                        'post_type' => 'venue',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ));
                    foreach ($venues as $venue) {
                        $selected = ($event_data['venue_id'] == $venue->ID) ? 'selected' : '';
                        echo '<option value="' . esc_attr($venue->ID) . '" ' . $selected . '>' . esc_html($venue->post_title) . '</option>';
                    }
                    
                    if (empty($venues)) {
                        echo '<option value="" disabled>' . __('No venues found. Add venues first.', 'event-manager') . '</option>';
                    }
                    ?>
                </select>
            </p>
            
            <p>
                <label for="event_parent"><strong><?php _e('Parent Event:', 'event-manager'); ?></strong></label><br>
                <select id="event_parent" name="event_parent" style="width: 100%; max-width: 400px;">
                    <option value=""><?php _e('None', 'event-manager'); ?></option>
                    <?php
                    $pages = get_pages(array(
                        'post_status' => 'publish,draft',
                        'exclude' => $post->ID
                    ));
                    foreach ($pages as $page) {
                        $selected = ($event_data['parent_event_id'] == $page->ID) ? 'selected' : '';
                        echo '<option value="' . esc_attr($page->ID) . '" ' . $selected . '>' . esc_html($page->post_title) . '</option>';
                    }
                    ?>
                </select>
            </p>
            
            <p>
                <label><strong><?php _e('Speakers:', 'event-manager'); ?></strong></label><br>
                <div id="speakers-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">
                    <?php
                    $all_speakers = get_posts(array(
                        'post_type' => 'speaker',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ));
                    
                    foreach ($all_speakers as $speaker) {
                        $checked = in_array($speaker->ID, $event_data['speaker_ids']) ? 'checked' : '';
                        echo '<label style="display: block; margin-bottom: 5px;">';
                        echo '<input type="checkbox" name="event_speakers[]" value="' . esc_attr($speaker->ID) . '" ' . $checked . '> ';
                        echo esc_html($speaker->post_title);
                        echo '</label>';
                    }
                    
                    if (empty($all_speakers)) {
                        echo '<p><em>' . __('No speakers found. Add speakers first.', 'event-manager') . '</em></p>';
                    }
                    ?>
                </div>
            </p>
            
            <p>
                <label><strong><?php _e('Organizers:', 'event-manager'); ?></strong></label><br>
                <div id="organizers-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">
                    <?php
                    $users = get_users(array('orderby' => 'display_name'));
                    
                    foreach ($users as $user) {
                        $checked = in_array($user->ID, $event_data['organizer_ids']) ? 'checked' : '';
                        echo '<label style="display: block; margin-bottom: 5px;">';
                        echo '<input type="checkbox" name="event_organizers[]" value="' . esc_attr($user->ID) . '" ' . $checked . '> ';
                        echo esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')';
                        echo '</label>';
                    }
                    ?>
                </div>
            </p>
        </div>
        <?php
    }
    
    /**
     * Get event data from JSON metadata
     */
    private function get_event_data($post_id) {
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
     * Save event meta box data as JSON
     */
    public function save_event_meta($post_id) {
        // Check nonce
        if (!isset($_POST['event_meta_box_nonce']) || !wp_verify_nonce($_POST['event_meta_box_nonce'], 'event_meta_box')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_page', $post_id)) {
            return;
        }
        
        // Prepare event data array
        $event_data = array(
            'start_date' => isset($_POST['event_start_date']) ? sanitize_text_field($_POST['event_start_date']) : '',
            'end_date' => isset($_POST['event_end_date']) ? sanitize_text_field($_POST['event_end_date']) : '',
            'registration_deadline' => isset($_POST['event_registration_deadline']) ? sanitize_text_field($_POST['event_registration_deadline']) : '',
            'registration_url' => isset($_POST['event_registration_url']) ? esc_url_raw($_POST['event_registration_url']) : '',
            'venue_id' => isset($_POST['event_venue']) ? absint($_POST['event_venue']) : '',
            'parent_event_id' => isset($_POST['event_parent']) ? absint($_POST['event_parent']) : '',
            'speaker_ids' => isset($_POST['event_speakers']) && is_array($_POST['event_speakers']) 
                ? array_map('absint', $_POST['event_speakers']) 
                : array(),
            'organizer_ids' => isset($_POST['event_organizers']) && is_array($_POST['event_organizers']) 
                ? array_map('absint', $_POST['event_organizers']) 
                : array(),
        );
        
        // Store as JSON
        $json_data = wp_json_encode($event_data);
        update_post_meta($post_id, '_event_data', $json_data);
        
        // Also store a flag to indicate this is an event page
        update_post_meta($post_id, '_is_event_page', '1');
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
