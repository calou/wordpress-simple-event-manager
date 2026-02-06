<?php
/**
 * Speaker Post Type Class
 *
 * @package Event_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Speaker Post Type Handler
 */
class Event_Manager_Speaker {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_speaker_post_type'));
        add_action('add_meta_boxes', array($this, 'add_speaker_meta_boxes'));
        add_action('save_post_speaker', array($this, 'save_speaker_meta'));
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
     * Add speaker meta boxes
     */
    public function add_speaker_meta_boxes() {
        add_meta_box(
            'speaker_details',
            __('Speaker Details', 'event-manager'),
            array($this, 'render_speaker_meta_box'),
            'speaker',
            'normal',
            'high'
        );
    }

    /**
     * Render the speaker meta box
     */
    public function render_speaker_meta_box($post) {
        wp_nonce_field('speaker_meta_box', 'speaker_meta_box_nonce');

        $speaker_data = $this->get_speaker_data($post->ID);

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
     * Get speaker data from metadata
     */
    private function get_speaker_data($post_id) {
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
     * Save speaker meta box data
     */
    public function save_speaker_meta($post_id) {
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
}
