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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_flatpickr'));
    }

    /**
     * Enqueue Flatpickr datetime picker
     */
    public function enqueue_flatpickr($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }

        global $post;
        if ($post && $post->post_type === 'page') {
            // Enqueue Flatpickr from CDN
            wp_enqueue_style('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), '4.6.13');
            wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js', array(), '4.6.13', true);
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
        wp_nonce_field('event_meta_box', 'event_meta_box_nonce');

        $event_data = $this->get_event_data($post->ID);

        // Get WordPress date and time formats
        $date_format = get_option('date_format');
        $time_format = get_option('time_format');

        // Convert to Flatpickr format
        $flatpickr_format = $this->convert_php_to_flatpickr_format($date_format, $time_format);

        // Format dates for display
        $start_display = !empty($event_data['start_date'])
            ? $this->format_datetime_for_display($event_data['start_date'], $date_format, $time_format)
            : '';
        $end_display = !empty($event_data['end_date'])
            ? $this->format_datetime_for_display($event_data['end_date'], $date_format, $time_format)
            : '';
        $reg_display = !empty($event_data['registration_deadline'])
            ? $this->format_datetime_for_display($event_data['registration_deadline'], $date_format, $time_format)
            : '';

        ?>
        <div class="event-meta-box">
            <div class="event-dates-row">
                <div class="event-date-field">
                    <label for="event_registration_deadline"><strong><?php _e('Registration Deadline:', 'event-manager'); ?></strong></label>
                    <input type="text" id="event_registration_deadline" name="event_registration_deadline"
                           value="<?php echo esc_attr($reg_display); ?>"
                           class="event-datetime-picker"
                           placeholder="<?php echo esc_attr($date_format . ' ' . $time_format); ?>">
                    <input type="hidden" id="event_registration_deadline_iso" name="event_registration_deadline_iso" value="<?php echo esc_attr($event_data['registration_deadline']); ?>">
                </div>

                <div class="event-date-field">
                    <label for="event_start_date"><strong><?php _e('Start Date & Time:', 'event-manager'); ?></strong></label>
                    <input type="text" id="event_start_date" name="event_start_date"
                           value="<?php echo esc_attr($start_display); ?>"
                           class="event-datetime-picker"
                           placeholder="<?php echo esc_attr($date_format . ' ' . $time_format); ?>">
                    <input type="hidden" id="event_start_date_iso" name="event_start_date_iso" value="<?php echo esc_attr($event_data['start_date']); ?>">
                </div>

                <div class="event-date-field">
                    <label for="event_end_date"><strong><?php _e('End Date & Time:', 'event-manager'); ?></strong></label>
                    <input type="text" id="event_end_date" name="event_end_date"
                           value="<?php echo esc_attr($end_display); ?>"
                           class="event-datetime-picker"
                           placeholder="<?php echo esc_attr($date_format . ' ' . $time_format); ?>">
                    <input type="hidden" id="event_end_date_iso" name="event_end_date_iso" value="<?php echo esc_attr($event_data['end_date']); ?>">
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const config = {
                    enableTime: true,
                    dateFormat: '<?php echo esc_js($flatpickr_format); ?>',
                    time_24hr: <?php echo (strpos($time_format, 'H') !== false || strpos($time_format, 'G') !== false) ? 'true' : 'false'; ?>,
                    altInput: false,
                    onChange: function(selectedDates, dateStr, instance) {
                        // Store ISO format in hidden field for server-side processing
                        if (selectedDates[0]) {
                            const isoFormat = selectedDates[0].toISOString().slice(0, 19).replace('T', ' ');
                            const hiddenInput = instance.element.nextElementSibling;
                            if (hiddenInput && hiddenInput.type === 'hidden') {
                                hiddenInput.value = isoFormat;
                            }
                        }
                    }
                };

                document.querySelectorAll('.event-datetime-picker').forEach(function(input) {
                    flatpickr(input, config);
                });
            });
            </script>

            <p>
                <label for="event_registration_url"><strong><?php _e('Registration URL:', 'event-manager'); ?></strong></label>
                <input type="url" id="event_registration_url" name="event_registration_url" value="<?php echo esc_attr($event_data['registration_url']); ?>" class="event-full-width" placeholder="https://">
                <span class="description"><?php _e('Link to registration page or form', 'event-manager'); ?></span>
            </p>

            <p>
                <label for="event_venue"><strong><?php _e('Venue:', 'event-manager'); ?></strong></label>
                <select id="event_venue" name="event_venue" class="event-select-width">
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
                <label for="event_parent"><strong><?php _e('Parent Event:', 'event-manager'); ?></strong></label>
                <select id="event_parent" name="event_parent" class="event-select-width">
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
                <label><strong><?php _e('Speakers:', 'event-manager'); ?></strong></label>
                <div id="speakers-list" class="event-scrollable-list">
                    <?php
                    $all_speakers = get_posts(array(
                        'post_type' => 'speaker',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ));

                    foreach ($all_speakers as $speaker) {
                        $checked = in_array($speaker->ID, $event_data['speaker_ids']) ? 'checked' : '';
                        echo '<label>';
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
                <label><strong><?php _e('Organizers:', 'event-manager'); ?></strong></label>
                <div id="organizers-list" class="event-scrollable-list">
                    <?php
                    $users = get_users(array('orderby' => 'display_name'));

                    foreach ($users as $user) {
                        $checked = in_array($user->ID, $event_data['organizer_ids']) ? 'checked' : '';
                        echo '<label>';
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

        // Use ISO format from hidden fields for reliable storage
        $event_data = array(
            'start_date' => isset($_POST['event_start_date_iso']) ? sanitize_text_field($_POST['event_start_date_iso']) : '',
            'end_date' => isset($_POST['event_end_date_iso']) ? sanitize_text_field($_POST['event_end_date_iso']) : '',
            'registration_deadline' => isset($_POST['event_registration_deadline_iso']) ? sanitize_text_field($_POST['event_registration_deadline_iso']) : '',
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

    /**
     * Convert PHP date/time format to Flatpickr format
     */
    private function convert_php_to_flatpickr_format($date_format, $time_format) {
        $combined_format = $date_format . ' ' . $time_format;
        $flatpickr_format = '';
        $length = strlen($combined_format);

        for ($i = 0; $i < $length; $i++) {
            $char = $combined_format[$i];

            switch ($char) {
                case 's': // Seconds: PHP uses 's', Flatpickr uses 'S'
                    $flatpickr_format .= 'S';
                    break;

                case 'A': // AM/PM: PHP uses 'A' or 'a', Flatpickr uses 'K'
                case 'a':
                    $flatpickr_format .= 'K';
                    break;

                default:
                    $flatpickr_format .= $char;
                    break;
            }
        }

        return $flatpickr_format;
    }

    /**
     * Format datetime from ISO storage format to display format
     */
    private function format_datetime_for_display($datetime_string, $date_format, $time_format) {
        if (empty($datetime_string)) {
            return '';
        }

        $timestamp = strtotime($datetime_string);
        if ($timestamp === false) {
            return '';
        }

        return date_i18n($date_format . ' ' . $time_format, $timestamp);
    }
}
