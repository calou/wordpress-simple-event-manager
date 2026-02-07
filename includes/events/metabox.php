<?php
/**
 * Event Meta Box
 *
 * @package Event_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

add_action('add_meta_boxes', 'event_manager_metabox_add');
add_action('save_post_page', 'event_manager_metabox_save');
add_action('admin_enqueue_scripts', 'event_manager_metabox_enqueue_flatpickr');
add_filter('default_template_types', 'event_manager_event_add_template_type');
add_filter('get_block_templates', 'event_manager_event_add_block_template', 10, 3);

/**
 * Enqueue Flatpickr datetime picker
 */
function event_manager_metabox_enqueue_flatpickr($hook) {
    if ('post.php' !== $hook && 'post-new.php' !== $hook) {
        return;
    }

    global $post;
    if ($post && $post->post_type === 'page' && event_manager_is_event_page($post->ID)) {
        wp_enqueue_style('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), '4.6.13');
        wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js', array(), '4.6.13', true);
    }
}

/**
 * Add event meta boxes to pages with Event template
 */
function event_manager_metabox_add() {
    global $post;
    if (!$post || !event_manager_is_event_page($post->ID)) {
        return;
    }

    add_meta_box(
        'event_details',
        __('Event Details', 'event-manager'),
        'event_manager_metabox_render',
        'page',
        'normal',
        'high'
    );
}

/**
 * Get event data from JSON metadata
 */
function event_manager_metabox_get_event_data($post_id) {
    $json_data = get_post_meta($post_id, '_event_data', true);

    // Default structure
    $default_data = array(
        'start_date' => '',
        'end_date' => '',
        'registration_deadline' => '',
        'registration_url' => '',
        'venue_id' => '',
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
 * Convert PHP date/time format to Flatpickr format
 */
function event_manager_metabox_convert_php_to_flatpickr_format($date_format, $time_format) {
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
function event_manager_metabox_format_datetime_for_display($datetime_string, $date_format, $time_format) {
    if (empty($datetime_string)) {
        return '';
    }

    $timestamp = strtotime($datetime_string);
    if ($timestamp === false) {
        return '';
    }

    return date_i18n($date_format . ' ' . $time_format, $timestamp);
}

/**
 * Render the event meta box
 */
function event_manager_metabox_render($post) {
    wp_nonce_field('event_meta_box', 'event_meta_box_nonce');

    $event_data = event_manager_metabox_get_event_data($post->ID);

    // Get WordPress date and time formats
    $date_format = get_option('date_format');
    $time_format = get_option('time_format');

    // Convert to Flatpickr format
    $flatpickr_format = event_manager_metabox_convert_php_to_flatpickr_format($date_format, $time_format);

    // Format dates for display
    $start_display = !empty($event_data['start_date'])
        ? event_manager_metabox_format_datetime_for_display($event_data['start_date'], $date_format, $time_format)
        : '';
    $end_display = !empty($event_data['end_date'])
        ? event_manager_metabox_format_datetime_for_display($event_data['end_date'], $date_format, $time_format)
        : '';
    $reg_display = !empty($event_data['registration_deadline'])
        ? event_manager_metabox_format_datetime_for_display($event_data['registration_deadline'], $date_format, $time_format)
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
            <label for="speaker-search"><strong><?php _e('Speakers:', 'event-manager'); ?></strong></label>
            <div class="event-autocomplete-container">
                <input type="text" id="speaker-search" class="event-autocomplete-input" placeholder="<?php _e('Search speakers...', 'event-manager'); ?>" autocomplete="off">
                <div id="speaker-results" class="event-autocomplete-results" style="display: none;"></div>
                <div id="selected-speakers" class="event-chips-container">
                    <?php
                    $all_speakers = get_posts(array(
                        'post_type' => 'speaker',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ));

                    foreach ($all_speakers as $speaker) {
                        if (in_array($speaker->ID, $event_data['speaker_ids'])) {
                            echo '<span class="event-chip" data-id="' . esc_attr($speaker->ID) . '">';
                            echo esc_html($speaker->post_title);
                            echo '<button type="button" class="event-chip-remove">&times;</button>';
                            echo '<input type="hidden" name="event_speakers[]" value="' . esc_attr($speaker->ID) . '">';
                            echo '</span>';
                        }
                    }

                    if (empty($all_speakers)) {
                        echo '<p class="event-empty-message"><em>' . __('No speakers found. Add speakers first.', 'event-manager') . '</em></p>';
                    }
                    ?>
                </div>
            </div>
        </p>

        <p>
            <label for="organizer-search"><strong><?php _e('Organizers:', 'event-manager'); ?></strong></label>
            <div class="event-autocomplete-container">
                <input type="text" id="organizer-search" class="event-autocomplete-input" placeholder="<?php _e('Search organizers...', 'event-manager'); ?>" autocomplete="off">
                <div id="organizer-results" class="event-autocomplete-results" style="display: none;"></div>
                <div id="selected-organizers" class="event-chips-container">
                    <?php
                    $users = get_users(array('orderby' => 'display_name'));

                    foreach ($users as $user) {
                        if (in_array($user->ID, $event_data['organizer_ids'])) {
                            echo '<span class="event-chip" data-id="' . esc_attr($user->ID) . '">';
                            echo esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')';
                            echo '<button type="button" class="event-chip-remove">&times;</button>';
                            echo '<input type="hidden" name="event_organizers[]" value="' . esc_attr($user->ID) . '">';
                            echo '</span>';
                        }
                    }
                    ?>
                </div>
            </div>
        </p>

        <script>
        (function() {
            const speakerData = <?php echo json_encode(array_map(function($s) {
                return ['id' => $s->ID, 'title' => $s->post_title];
            }, $all_speakers)); ?>;

            const organizerData = <?php echo json_encode(array_map(function($u) {
                return ['id' => $u->ID, 'title' => $u->display_name . ' (' . $u->user_email . ')'];
            }, $users)); ?>;

            setupAutocomplete('speaker-search', 'speaker-results', 'selected-speakers', speakerData);
            setupAutocomplete('organizer-search', 'organizer-results', 'selected-organizers', organizerData);

            function setupAutocomplete(inputId, resultsId, containerId, data) {
                const input = document.getElementById(inputId);
                const results = document.getElementById(resultsId);
                const container = document.getElementById(containerId);

                input.addEventListener('input', function() {
                    const query = this.value.toLowerCase().trim();

                    if (query.length === 0) {
                        results.style.display = 'none';
                        return;
                    }

                    const selectedIds = Array.from(container.querySelectorAll('.event-chip')).map(chip => chip.dataset.id);
                    const filtered = data.filter(item =>
                        !selectedIds.includes(String(item.id)) &&
                        item.title.toLowerCase().includes(query)
                    );

                    if (filtered.length === 0) {
                        results.style.display = 'none';
                        return;
                    }

                    results.innerHTML = filtered.map(item =>
                        `<div class="event-autocomplete-item" data-id="${item.id}" data-title="${item.title}">${item.title}</div>`
                    ).join('');
                    results.style.display = 'block';
                });

                results.addEventListener('click', function(e) {
                    if (e.target.classList.contains('event-autocomplete-item')) {
                        const id = e.target.dataset.id;
                        const title = e.target.dataset.title;
                        const fieldName = inputId.includes('speaker') ? 'event_speakers[]' : 'event_organizers[]';

                        const emptyMessage = container.querySelector('.event-empty-message');
                        if (emptyMessage) {
                            emptyMessage.remove();
                        }

                        const chip = document.createElement('span');
                        chip.className = 'event-chip';
                        chip.dataset.id = id;
                        chip.innerHTML = `${title}<button type="button" class="event-chip-remove">&times;</button><input type="hidden" name="${fieldName}" value="${id}">`;
                        container.appendChild(chip);

                        input.value = '';
                        results.style.display = 'none';
                    }
                });

                container.addEventListener('click', function(e) {
                    if (e.target.classList.contains('event-chip-remove')) {
                        e.target.closest('.event-chip').remove();
                    }
                });

                document.addEventListener('click', function(e) {
                    if (!input.contains(e.target) && !results.contains(e.target)) {
                        results.style.display = 'none';
                    }
                });
            }
        })();
        </script>
    </div>
    <?php
}

/**
 * Save event meta box data as JSON
 */
function event_manager_metabox_save($post_id) {
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
        'speaker_ids' => isset($_POST['event_speakers']) && is_array($_POST['event_speakers'])
            ? array_map('absint', $_POST['event_speakers'])
            : array(),
        'organizer_ids' => isset($_POST['event_organizers']) && is_array($_POST['event_organizers'])
            ? array_map('absint', $_POST['event_organizers'])
            : array(),
    );

    $json_data = wp_json_encode($event_data);
    update_post_meta($post_id, '_event_data', $json_data);
}

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
 * Add event page template type
 */
function event_manager_event_add_template_type($template_types) {
    $template_types[EVENT_MANAGER_EVENT_TEMPLATE] = array(
        'title' => __('Event', 'event-manager'),
        'description' => __('Template for event pages', 'event-manager'),
    );
    return $template_types;
}

/**
 * Register block template for event pages
 */
function event_manager_event_add_block_template($query_result, $query, $template_type) {
    $template_file = EVENT_MANAGER_PLUGIN_DIR . 'templates/page-event.html';

    if (!file_exists($template_file)) {
        return $query_result;
    }

    $template_content = file_get_contents($template_file);

    $new_template = new WP_Block_Template();
    $new_template->type = 'wp_template';
    $new_template->theme = get_stylesheet();
    $new_template->slug = EVENT_MANAGER_EVENT_TEMPLATE;
    $new_template->id = get_stylesheet() . '//' . EVENT_MANAGER_EVENT_TEMPLATE;
    $new_template->title = __('Event', 'event-manager');
    $new_template->description = __('Template for event pages', 'event-manager');
    $new_template->source = 'plugin';
    $new_template->origin = 'plugin';
    $new_template->content = $template_content;
    $new_template->status = 'publish';
    $new_template->has_theme_file = false;
    $new_template->is_custom = true;
    $new_template->post_types = array('page');

    if (
        (isset($query['slug__in']) && in_array(EVENT_MANAGER_EVENT_TEMPLATE, $query['slug__in'])) ||
        !isset($query['slug__in'])
    ) {
        $query_result[] = $new_template;
    }

    return $query_result;
}
