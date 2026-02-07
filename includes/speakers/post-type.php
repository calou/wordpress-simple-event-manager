<?php
/**
 * Speaker Page Handler
 *
 * @package Event_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'event_manager_speaker_register_post_type');
add_action('add_meta_boxes', 'event_manager_speaker_add_meta_boxes');
add_action('save_post_speaker', 'event_manager_speaker_save_meta');
add_filter('default_template_types', 'event_manager_speaker_add_template_type');
add_filter('get_block_templates', 'event_manager_speaker_add_block_template', 10, 3);
add_action('wp_enqueue_scripts', 'event_manager_speaker_enqueue_frontend_styles');

/**
 * Register speaker custom post type
 */
function event_manager_speaker_register_post_type() {
    $labels = array(
        'name' => __('Speakers', 'event-manager'),
        'singular_name' => __('Speaker', 'event-manager'),
        'add_new' => __('Add New', 'event-manager'),
        'add_new_item' => __('Add New Speaker', 'event-manager'),
        'edit_item' => __('Edit Speaker', 'event-manager'),
        'new_item' => __('New Speaker', 'event-manager'),
        'view_item' => __('View Speaker', 'event-manager'),
        'search_items' => __('Search Speakers', 'event-manager'),
        'not_found' => __('No speakers found', 'event-manager'),
        'not_found_in_trash' => __('No speakers found in trash', 'event-manager'),
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-microphone',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
        'rewrite' => array('slug' => 'speaker'),
        'template' => array(
            array('core/paragraph', array(
                'placeholder' => 'Add speaker biography and additional information here...'
            )),
        ),
        'template_lock' => false,
    );

    register_post_type('speaker', $args);
}

/**
 * Add speaker meta boxes
 */
function event_manager_speaker_add_meta_boxes() {
    add_meta_box(
        'speaker_details',
        __('Speaker Details', 'event-manager'),
        'event_manager_speaker_render_meta_box',
        'speaker',
        'normal',
        'high'
    );
}

/**
 * Get speaker data from metadata
 */
function event_manager_speaker_get_data($post_id) {
    $json_data = get_post_meta($post_id, '_speaker_data', true);

    // Default structure
    $default_data = array(
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
            return array_merge($default_data, $decoded);
        }
    }

    return $default_data;
}

/**
 * Render the speaker meta box
 */
function event_manager_speaker_render_meta_box($post) {
    wp_nonce_field('speaker_meta_box', 'speaker_meta_box_nonce');

    $speaker_data = event_manager_speaker_get_data($post->ID);

    ?>
    <div class="speaker-meta-box">
        <p>
            <label for="speaker_position"><strong><?php _e('Position:', 'event-manager'); ?></strong></label>
            <input type="text" id="speaker_position" name="speaker_position" value="<?php echo esc_attr($speaker_data['position']); ?>" class="event-full-width" placeholder="<?php _e('Chief Scientist', 'event-manager'); ?>">
            <span class="description"><?php _e('Job title or position', 'event-manager'); ?></span>
        </p>

        <p>
            <label for="speaker_organization"><strong><?php _e('Organization:', 'event-manager'); ?></strong></label>
            <input type="text" id="speaker_organization" name="speaker_organization" value="<?php echo esc_attr($speaker_data['organization']); ?>" class="event-full-width" placeholder="<?php _e('European Synchrotron Radiation Facility', 'event-manager'); ?>">
            <span class="description"><?php _e('Organization or institution', 'event-manager'); ?></span>
        </p>

        <p>
            <label for="speaker_organization_url"><strong><?php _e('Organization URL:', 'event-manager'); ?></strong></label>
            <input type="url" id="speaker_organization_url" name="speaker_organization_url" value="<?php echo esc_attr($speaker_data['organization_url']); ?>" class="event-full-width" placeholder="https://www.esrf.fr">
            <span class="description"><?php _e('Link to organization website', 'event-manager'); ?></span>
        </p>

        <p>
            <label for="speaker_orcid"><strong><?php _e('ORCID:', 'event-manager'); ?></strong></label>
            <input type="text" id="speaker_orcid" name="speaker_orcid" value="<?php echo esc_attr($speaker_data['orcid']); ?>" class="event-full-width" placeholder="0000-0002-1825-0097">
            <span class="description"><?php _e('ORCID iD (e.g., 0000-0002-1825-0097)', 'event-manager'); ?></span>
        </p>

        <h4><?php _e('Contact Information', 'event-manager'); ?></h4>

        <p>
            <label for="speaker_email"><strong><?php _e('Email:', 'event-manager'); ?></strong></label>
            <input type="email" id="speaker_email" name="speaker_email" value="<?php echo esc_attr($speaker_data['email']); ?>" class="event-full-width" placeholder="speaker@example.com">
        </p>

        <p>
            <label for="speaker_phone"><strong><?php _e('Phone:', 'event-manager'); ?></strong></label>
            <input type="tel" id="speaker_phone" name="speaker_phone" value="<?php echo esc_attr($speaker_data['phone']); ?>" class="event-full-width" placeholder="+33 4 76 88 20 00">
        </p>

        <p>
            <label for="speaker_website"><strong><?php _e('Website:', 'event-manager'); ?></strong></label>
            <input type="url" id="speaker_website" name="speaker_website" value="<?php echo esc_attr($speaker_data['website']); ?>" class="event-full-width" placeholder="https://example.com">
        </p>

        <p>
            <label for="speaker_linkedin"><strong><?php _e('LinkedIn:', 'event-manager'); ?></strong></label>
            <input type="url" id="speaker_linkedin" name="speaker_linkedin" value="<?php echo esc_attr($speaker_data['linkedin']); ?>" class="event-full-width" placeholder="https://linkedin.com/in/username">
        </p>

        <p class="description">
            <strong><?php _e('Profile Picture:', 'event-manager'); ?></strong><br>
            <?php _e('Use the "Featured Image" box on the right to set the speaker\'s profile picture.', 'event-manager'); ?>
        </p>
    </div>
    <?php
}

/**
 * Save speaker meta box data
 */
function event_manager_speaker_save_meta($post_id) {
    if (!isset($_POST['speaker_meta_box_nonce']) || !wp_verify_nonce($_POST['speaker_meta_box_nonce'], 'speaker_meta_box')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $speaker_data = array(
        'position' => isset($_POST['speaker_position']) ? sanitize_text_field($_POST['speaker_position']) : '',
        'organization' => isset($_POST['speaker_organization']) ? sanitize_text_field($_POST['speaker_organization']) : '',
        'organization_url' => isset($_POST['speaker_organization_url']) ? esc_url_raw($_POST['speaker_organization_url']) : '',
        'orcid' => isset($_POST['speaker_orcid']) ? sanitize_text_field($_POST['speaker_orcid']) : '',
        'email' => isset($_POST['speaker_email']) ? sanitize_email($_POST['speaker_email']) : '',
        'phone' => isset($_POST['speaker_phone']) ? sanitize_text_field($_POST['speaker_phone']) : '',
        'website' => isset($_POST['speaker_website']) ? esc_url_raw($_POST['speaker_website']) : '',
        'linkedin' => isset($_POST['speaker_linkedin']) ? esc_url_raw($_POST['speaker_linkedin']) : '',
    );

    $json_data = wp_json_encode($speaker_data);
    update_post_meta($post_id, '_speaker_data', $json_data);
}

/**
 * Add speaker template type
 */
function event_manager_speaker_add_template_type($template_types) {
    $template_types['single-speaker'] = array(
        'title' => __('Single Speaker', 'event-manager'),
        'description' => __('Displays a single speaker post', 'event-manager'),
    );
    return $template_types;
}

/**
 * Register block template for speaker post type
 */
function event_manager_speaker_add_block_template($query_result, $query, $template_type) {
    $template_file = EVENT_MANAGER_PLUGIN_DIR . 'templates/single-speaker.html';

    if (!file_exists($template_file)) {
        return $query_result;
    }

    $template_content = file_get_contents($template_file);

    $new_template = new WP_Block_Template();
    $new_template->type = 'wp_template';
    $new_template->theme = get_stylesheet();
    $new_template->slug = 'single-speaker';
    $new_template->id = get_stylesheet() . '//single-speaker';
    $new_template->title = __('Single Speaker', 'event-manager');
    $new_template->description = __('Template for displaying single speaker posts', 'event-manager');
    $new_template->source = 'plugin';
    $new_template->origin = 'plugin';
    $new_template->content = $template_content;
    $new_template->status = 'publish';
    $new_template->has_theme_file = false;
    $new_template->is_custom = false;
    $new_template->post_types = array('speaker');

    // Add to query result if it matches the criteria
    if (
        (isset($query['slug__in']) && in_array('single-speaker', $query['slug__in'])) ||
        (isset($query['post_type']) && $query['post_type'] === 'speaker') ||
        !isset($query['slug__in'])
    ) {
        $query_result[] = $new_template;
    }

    return $query_result;
}

/**
 * Enqueue frontend styles
 */
function event_manager_speaker_enqueue_frontend_styles() {
    if (is_singular('speaker')) {
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), '6.5.1');
        wp_enqueue_style('event-manager-speaker-content', EVENT_MANAGER_PLUGIN_URL . 'assets/css/speaker-content.css', array('font-awesome'), EVENT_MANAGER_VERSION);
    }
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
