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
add_filter('default_template_types', 'event_manager_venue_add_template_type');
add_filter('get_block_templates', 'event_manager_venue_add_block_template', 10, 3);
add_action('wp_enqueue_scripts', 'event_manager_venue_enqueue_frontend_styles');

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

    $venue_data = event_manager_venue_get_data($post->ID);

    ?>
    <div class="venue-meta-box">
        <p>
            <label for="venue_address"><strong><?php _e('Address:', 'event-manager'); ?></strong></label><br>
            <input type="text" id="venue_address" name="venue_address" value="<?php echo esc_attr($venue_data['address']); ?>" style="width: 100%;">
        </p>

        <p>
            <label for="venue_city"><strong><?php _e('City:', 'event-manager'); ?></strong></label><br>
            <input type="text" id="venue_city" name="venue_city" value="<?php echo esc_attr($venue_data['city']); ?>" style="width: 100%;">
        </p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <label for="venue_state"><strong><?php _e('State/Province:', 'event-manager'); ?></strong></label><br>
                <input type="text" id="venue_state" name="venue_state" value="<?php echo esc_attr($venue_data['state']); ?>" style="width: 100%;">
            </div>

            <div>
                <label for="venue_zip"><strong><?php _e('ZIP/Postal Code:', 'event-manager'); ?></strong></label><br>
                <input type="text" id="venue_zip" name="venue_zip" value="<?php echo esc_attr($venue_data['zip']); ?>" style="width: 100%;">
            </div>
        </div>

        <p>
            <label for="venue_country"><strong><?php _e('Country:', 'event-manager'); ?></strong></label><br>
            <input type="text" id="venue_country" name="venue_country" value="<?php echo esc_attr($venue_data['country']); ?>" style="width: 100%;">
        </p>

        <p>
            <label for="venue_capacity"><strong><?php _e('Capacity:', 'event-manager'); ?></strong></label><br>
            <input type="number" id="venue_capacity" name="venue_capacity" value="<?php echo esc_attr($venue_data['capacity']); ?>" style="width: 100%; max-width: 200px;">
        </p>

        <p>
            <label for="venue_phone"><strong><?php _e('Phone:', 'event-manager'); ?></strong></label><br>
            <input type="tel" id="venue_phone" name="venue_phone" value="<?php echo esc_attr($venue_data['phone']); ?>" style="width: 100%; max-width: 300px;">
        </p>

        <p>
            <label for="venue_website"><strong><?php _e('Website:', 'event-manager'); ?></strong></label><br>
            <input type="url" id="venue_website" name="venue_website" value="<?php echo esc_attr($venue_data['website']); ?>" style="width: 100%;" placeholder="https://">
        </p>

        <hr>
        <h4><?php _e('Contacts', 'event-manager'); ?></h4>
        <div id="venue-contacts-list">
            <?php
            $contacts = !empty($venue_data['contacts']) ? $venue_data['contacts'] : array();
            foreach ($contacts as $i => $contact) :
            ?>
                <div class="venue-contact-row" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 4px; background: #f9f9f9;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; align-items: end;">
                        <div>
                            <label><strong><?php _e('Name:', 'event-manager'); ?></strong></label><br>
                            <input type="text" name="venue_contacts[<?php echo $i; ?>][name]" value="<?php echo esc_attr($contact['name']); ?>" style="width: 100%;" required>
                        </div>
                        <div>
                            <label><strong><?php _e('Email:', 'event-manager'); ?></strong></label><br>
                            <input type="email" name="venue_contacts[<?php echo $i; ?>][email]" value="<?php echo esc_attr($contact['email']); ?>" style="width: 100%;">
                        </div>
                        <div>
                            <label><strong><?php _e('Phone:', 'event-manager'); ?></strong></label><br>
                            <input type="tel" name="venue_contacts[<?php echo $i; ?>][phone]" value="<?php echo esc_attr($contact['phone']); ?>" style="width: 100%;">
                        </div>
                    </div>
                    <p style="margin: 8px 0 0;"><button type="button" class="button venue-remove-contact"><?php _e('Remove', 'event-manager'); ?></button></p>
                </div>
            <?php endforeach; ?>
        </div>
        <p><button type="button" class="button" id="venue-add-contact"><?php _e('Add Contact', 'event-manager'); ?></button></p>

        <script>
        (function() {
            var list = document.getElementById('venue-contacts-list');
            var addBtn = document.getElementById('venue-add-contact');
            var index = <?php echo count($contacts); ?>;

            addBtn.addEventListener('click', function() {
                var row = document.createElement('div');
                row.className = 'venue-contact-row';
                row.style.cssText = 'border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 4px; background: #f9f9f9;';
                row.innerHTML = '<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; align-items: end;">' +
                    '<div><label><strong><?php echo esc_js(__('Name:', 'event-manager')); ?></strong></label><br>' +
                    '<input type="text" name="venue_contacts[' + index + '][name]" style="width: 100%;" required></div>' +
                    '<div><label><strong><?php echo esc_js(__('Email:', 'event-manager')); ?></strong></label><br>' +
                    '<input type="email" name="venue_contacts[' + index + '][email]" style="width: 100%;"></div>' +
                    '<div><label><strong><?php echo esc_js(__('Phone:', 'event-manager')); ?></strong></label><br>' +
                    '<input type="tel" name="venue_contacts[' + index + '][phone]" style="width: 100%;"></div>' +
                    '</div>' +
                    '<p style="margin: 8px 0 0;"><button type="button" class="button venue-remove-contact"><?php echo esc_js(__('Remove', 'event-manager')); ?></button></p>';
                list.appendChild(row);
                index++;
            });

            list.addEventListener('click', function(e) {
                if (e.target.classList.contains('venue-remove-contact')) {
                    e.target.closest('.venue-contact-row').remove();
                }
            });
        })();
        </script>
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

    $contacts = array();
    if (isset($_POST['venue_contacts']) && is_array($_POST['venue_contacts'])) {
        foreach ($_POST['venue_contacts'] as $contact) {
            $name = isset($contact['name']) ? sanitize_text_field($contact['name']) : '';
            $email = isset($contact['email']) ? sanitize_email($contact['email']) : '';
            $phone = isset($contact['phone']) ? sanitize_text_field($contact['phone']) : '';

            // Name is mandatory, and at least one of email/phone required
            if (!empty($name) && (!empty($email) || !empty($phone))) {
                $contacts[] = array(
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                );
            }
        }
    }

    $venue_data = array(
        'address' => isset($_POST['venue_address']) ? sanitize_text_field($_POST['venue_address']) : '',
        'city' => isset($_POST['venue_city']) ? sanitize_text_field($_POST['venue_city']) : '',
        'state' => isset($_POST['venue_state']) ? sanitize_text_field($_POST['venue_state']) : '',
        'zip' => isset($_POST['venue_zip']) ? sanitize_text_field($_POST['venue_zip']) : '',
        'country' => isset($_POST['venue_country']) ? sanitize_text_field($_POST['venue_country']) : '',
        'capacity' => isset($_POST['venue_capacity']) ? sanitize_text_field($_POST['venue_capacity']) : '',
        'phone' => isset($_POST['venue_phone']) ? sanitize_text_field($_POST['venue_phone']) : '',
        'website' => isset($_POST['venue_website']) ? esc_url_raw($_POST['venue_website']) : '',
        'contacts' => $contacts,
    );

    $json_data = wp_json_encode($venue_data);
    update_post_meta($post_id, '_venue_data', $json_data);
}

/**
 * Get venue data from JSON metadata
 */
function event_manager_venue_get_data($post_id) {
    $json_data = get_post_meta($post_id, '_venue_data', true);

    $default_data = array(
        'address' => '',
        'city' => '',
        'state' => '',
        'zip' => '',
        'country' => '',
        'capacity' => '',
        'phone' => '',
        'website' => '',
        'contacts' => array(),
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
 * Helper function to get venue data with post info
 */
function event_manager_get_venue_data($venue_id) {
    if (empty($venue_id)) {
        return null;
    }

    $venue = get_post($venue_id);
    if (!$venue || $venue->post_type !== 'venue') {
        return null;
    }

    $venue_meta = event_manager_venue_get_data($venue_id);

    return array(
        'id' => $venue->ID,
        'name' => $venue->post_title,
        'description' => $venue->post_content,
        'address' => $venue_meta['address'],
        'city' => $venue_meta['city'],
        'state' => $venue_meta['state'],
        'zip' => $venue_meta['zip'],
        'country' => $venue_meta['country'],
        'capacity' => $venue_meta['capacity'],
        'phone' => $venue_meta['phone'],
        'website' => $venue_meta['website'],
        'contacts' => $venue_meta['contacts'],
        'thumbnail' => get_the_post_thumbnail_url($venue_id, 'medium'),
    );
}

/**
 * Add venue template type
 */
function event_manager_venue_add_template_type($template_types) {
    $template_types['single-venue'] = array(
        'title' => __('Single Venue', 'event-manager'),
        'description' => __('Displays a single venue post', 'event-manager'),
    );
    return $template_types;
}

/**
 * Register block template for venue post type
 */
function event_manager_venue_add_block_template($query_result, $query, $template_type) {
    $template_file = EVENT_MANAGER_PLUGIN_DIR . 'templates/single-venue.html';

    if (!file_exists($template_file)) {
        return $query_result;
    }

    $template_content = file_get_contents($template_file);

    $new_template = new WP_Block_Template();
    $new_template->type = 'wp_template';
    $new_template->theme = get_stylesheet();
    $new_template->slug = 'single-venue';
    $new_template->id = get_stylesheet() . '//single-venue';
    $new_template->title = __('Single Venue', 'event-manager');
    $new_template->description = __('Template for displaying single venue posts', 'event-manager');
    $new_template->source = 'plugin';
    $new_template->origin = 'plugin';
    $new_template->content = $template_content;
    $new_template->status = 'publish';
    $new_template->has_theme_file = false;
    $new_template->is_custom = false;
    $new_template->post_types = array('venue');

    if (
        (isset($query['slug__in']) && in_array('single-venue', $query['slug__in'])) ||
        (isset($query['post_type']) && $query['post_type'] === 'venue') ||
        !isset($query['slug__in'])
    ) {
        $query_result[] = $new_template;
    }

    return $query_result;
}

/**
 * Enqueue frontend styles for venue pages
 */
function event_manager_venue_enqueue_frontend_styles() {
    if (is_singular('venue')) {
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), '6.5.1');
        wp_enqueue_style('event-manager-venue-content', EVENT_MANAGER_PLUGIN_URL . 'assets/css/venue-content.css', array('font-awesome'), EVENT_MANAGER_VERSION);
    }
}
