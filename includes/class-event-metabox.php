<?php
/**
 * Event Meta Box Class
 *
 * @package Event_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Event Meta Box Handler
 */
class Event_Manager_Metabox {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_event_meta_boxes'));
        add_action('save_post_page', array($this, 'save_event_meta'));
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
        wp_nonce_field('event_meta_box', 'event_meta_box_nonce');

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
        if (!isset($_POST['event_meta_box_nonce']) || !wp_verify_nonce($_POST['event_meta_box_nonce'], 'event_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_page', $post_id)) {
            return;
        }

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

        $json_data = wp_json_encode($event_data);
        update_post_meta($post_id, '_event_data', $json_data);

        update_post_meta($post_id, '_is_event_page', '1');
    }
}
