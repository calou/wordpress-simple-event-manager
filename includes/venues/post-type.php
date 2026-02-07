<?php
/**
 * Venue Post Type
 *
 * @package Event_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'event_manager_venue_register_post_type');
add_action('add_meta_boxes_venue', 'event_manager_venue_add_meta_boxes');
add_action('save_post_venue', 'event_manager_venue_save_meta');

/**
 * Register Venue Custom Post Type
 */
function event_manager_venue_register_post_type() {
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
}

/**
 * Add meta boxes to venue post type
 */
function event_manager_venue_add_meta_boxes() {
    add_meta_box(
        'venue_details',
        __('Venue Details', 'event-manager'),
        'event_manager_venue_render_meta_box',
        'venue',
        'normal',
        'high'
    );
}

/**
 * Render venue meta box
 */
function event_manager_venue_render_meta_box($post) {
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
function event_manager_venue_save_meta($post_id) {
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
