<?php
/**
 * Event Metadata Shortcodes
 *
 * @package Event_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('event_metadata', 'event_manager_event_metadata_render');
add_shortcode('event_sidebar', 'event_manager_event_sidebar_render');
add_shortcode('event_speakers', 'event_manager_event_speakers_render');
add_shortcode('event_organizers', 'event_manager_event_organizers_render');
add_shortcode('event_programme', 'event_manager_event_programme_render');
add_action('wp_enqueue_scripts', 'event_manager_event_enqueue_frontend_styles');
add_action('template_redirect', 'event_manager_event_ics_download');

/**
 * Enqueue frontend styles for event pages
 */
function event_manager_event_enqueue_frontend_styles() {
    if (!is_singular('page')) {
        return;
    }

    $post_id = get_the_ID();
    if (!$post_id || !event_manager_is_event_page($post_id)) {
        return;
    }

    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), '6.5.1');
    wp_enqueue_style('event-manager-event-content', EVENT_MANAGER_PLUGIN_URL . 'assets/css/event-content.css', array('font-awesome'), EVENT_MANAGER_VERSION);
}

/**
 * Format event date range for display
 */
function event_manager_event_format_date_range($start_date, $end_date) {
    if (empty($start_date)) {
        return '';
    }

    $date_format = get_option('date_format');
    $time_format = get_option('time_format');
    $start_ts = strtotime($start_date);

    if (empty($end_date)) {
        return date_i18n($date_format . ' ' . $time_format, $start_ts);
    }

    $end_ts = strtotime($end_date);

    // Same day: show date once with time range
    if (date('Y-m-d', $start_ts) === date('Y-m-d', $end_ts)) {
        return date_i18n($date_format, $start_ts) . ', '
            . date_i18n($time_format, $start_ts) . ' – '
            . date_i18n($time_format, $end_ts);
    }

    // Different days
    return date_i18n($date_format . ' ' . $time_format, $start_ts) . ' – '
        . date_i18n($date_format . ' ' . $time_format, $end_ts);
}

/**
 * Get child events for a given event using page hierarchy
 */
function event_manager_event_get_children($post_id) {
    $child_pages = get_posts(array(
        'post_type' => 'page',
        'post_parent' => $post_id,
        'posts_per_page' => -1,
        'meta_query' => event_manager_event_page_meta_query(),
    ));

    $child_events = array();
    foreach ($child_pages as $page) {
        $child_events[] = array(
            'post' => $page,
            'data' => event_manager_get_event_data($page->ID),
        );
    }

    return $child_events;
}

/**
 * Handle ICS file download for an event
 */
function event_manager_event_ics_download() {
    if (empty($_GET['event_ics'])) {
        return;
    }

    $post_id = absint($_GET['event_ics']);
    $post = get_post($post_id);

    if (!$post || !event_manager_is_event_page($post_id)) {
        return;
    }

    $event_data = event_manager_get_event_data($post_id);

    if (empty($event_data['start_date'])) {
        return;
    }

    $start_ts = strtotime($event_data['start_date']);
    $end_ts = !empty($event_data['end_date']) ? strtotime($event_data['end_date']) : $start_ts + 3600;

    $location = '';
    if (!empty($event_data['venue_id'])) {
        $venue = event_manager_get_venue_data($event_data['venue_id']);
        if ($venue) {
            $location_parts = array_filter(array($venue['name'], $venue['address'], $venue['city'], $venue['country']));
            $location = implode(', ', $location_parts);
        }
    }

    $summary = $post->post_title;
    $description = wp_strip_all_tags($post->post_excerpt ?: wp_trim_words($post->post_content, 50, '...'));
    $url = get_permalink($post_id);
    $uid = 'event-' . $post_id . '@' . wp_parse_url(home_url(), PHP_URL_HOST);

    $ics = "BEGIN:VCALENDAR\r\n";
    $ics .= "VERSION:2.0\r\n";
    $ics .= "PRODID:-//Event Manager//EN\r\n";
    $ics .= "BEGIN:VEVENT\r\n";
    $ics .= "UID:" . $uid . "\r\n";
    $ics .= "DTSTART:" . gmdate('Ymd\THis\Z', $start_ts) . "\r\n";
    $ics .= "DTEND:" . gmdate('Ymd\THis\Z', $end_ts) . "\r\n";
    $ics .= "SUMMARY:" . event_manager_event_ics_escape($summary) . "\r\n";
    if (!empty($description)) {
        $ics .= "DESCRIPTION:" . event_manager_event_ics_escape($description) . "\r\n";
    }
    if (!empty($location)) {
        $ics .= "LOCATION:" . event_manager_event_ics_escape($location) . "\r\n";
    }
    $ics .= "URL:" . $url . "\r\n";
    $ics .= "END:VEVENT\r\n";
    $ics .= "END:VCALENDAR\r\n";

    $filename = sanitize_file_name($post->post_name ?: 'event') . '.ics';

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $ics;
    exit;
}

/**
 * Escape text for ICS format
 */
function event_manager_event_ics_escape($text) {
    $text = str_replace(array("\r\n", "\r", "\n"), '\n', $text);
    $text = str_replace(array(',', ';', '\\'), array('\,', '\;', '\\\\'), $text);
    return $text;
}

/**
 * Build Google Calendar URL for an event
 */
function event_manager_event_google_calendar_url($post_id) {
    $post = get_post($post_id);
    $event_data = event_manager_get_event_data($post_id);

    if (empty($event_data['start_date'])) {
        return '';
    }

    $start_ts = strtotime($event_data['start_date']);
    $end_ts = !empty($event_data['end_date']) ? strtotime($event_data['end_date']) : $start_ts + 3600;

    $params = array(
        'action' => 'TEMPLATE',
        'text' => $post->post_title,
        'dates' => gmdate('Ymd\THis\Z', $start_ts) . '/' . gmdate('Ymd\THis\Z', $end_ts),
        'details' => wp_strip_all_tags(wp_trim_words($post->post_content, 50, '...')) . "\n\n" . get_permalink($post_id),
    );

    if (!empty($event_data['venue_id'])) {
        $venue = event_manager_get_venue_data($event_data['venue_id']);
        if ($venue) {
            $location_parts = array_filter(array($venue['name'], $venue['city'], $venue['country']));
            $params['location'] = implode(', ', $location_parts);
        }
    }

    return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
}

/**
 * Render event metadata shortcode (breadcrumb + date range above content)
 */
function event_manager_event_metadata_render($atts) {
    $post_id = get_the_ID();

    if (!$post_id) {
        return '';
    }

    $event_data = event_manager_get_event_data($post_id);

    // Get parent event from page hierarchy
    $parent_event = null;
    $post = get_post($post_id);
    if ($post && $post->post_parent) {
        $parent = get_post($post->post_parent);
        if ($parent && $parent->post_status === 'publish') {
            $parent_event = $parent;
        }
    }

    // Date-only range (no time)
    $date_only = '';
    if (!empty($event_data['start_date'])) {
        $date_format = get_option('date_format');
        $start_ts = strtotime($event_data['start_date']);
        if (!empty($event_data['end_date']) && date('Y-m-d', $start_ts) !== date('Y-m-d', strtotime($event_data['end_date']))) {
            $date_only = date_i18n($date_format, $start_ts) . ' – ' . date_i18n($date_format, strtotime($event_data['end_date']));
        } else {
            $date_only = date_i18n($date_format, $start_ts);
        }
    }

    if (!$parent_event && empty($date_only)) {
        return '';
    }

    ob_start();
    ?>
    <div class="event-meta">
        <?php if ($parent_event) : ?>
            <div class="event-breadcrumb">
                <a href="<?php echo get_permalink($parent_event->ID); ?>">
                    <i class="fas fa-arrow-left"></i> <?php echo esc_html($parent_event->post_title); ?>
                </a>
            </div>
        <?php endif; ?>

        <?php if (!empty($date_only)) : ?>
            <div class="event-date"><?php echo esc_html($date_only); ?></div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render event sidebar shortcode (venue, registration, calendar)
 */
function event_manager_event_sidebar_render($atts) {
    $post_id = get_the_ID();

    if (!$post_id) {
        return '';
    }

    $event_data = event_manager_get_event_data($post_id);

    // Get venue data
    $venue = null;
    if (!empty($event_data['venue_id'])) {
        $venue = event_manager_get_venue_data($event_data['venue_id']);
    }

    $reg_deadline = $event_data['registration_deadline'];
    $reg_url = $event_data['registration_url'];
    $has_calendar = !empty($event_data['start_date']);

    if (!$venue && empty($reg_url) && empty($reg_deadline) && !$has_calendar) {
        return '';
    }

    $date_format = get_option('date_format');
    $time_format = get_option('time_format');

    ob_start();
    ?>
    <aside class="event-sidebar">
        

        <?php if ($venue) : ?>
            <div class="event-sidebar-section">
                <a href="<?php echo get_permalink($venue['id']); ?>" class="event-sidebar-venue-name">
                    <i class="fas fa-location-dot"></i>
                    <?php echo esc_html($venue['name']); ?>
                </a>
            </div>
        <?php endif; ?>
        
        <?php if ($has_calendar) :
            $ics_url = add_query_arg('event_ics', $post_id, home_url('/'));
            $gcal_url = event_manager_event_google_calendar_url($post_id);
            ?>
            <div class="event-sidebar-section">
                <div class="event-calendar-dropdown">
                    <button type="button" class="event-calendar-button">
                        <i class="fas fa-calendar-plus"></i> <?php _e('Add to Calendar', 'event-manager'); ?>
                    </button>
                    <div class="event-calendar-menu">
                        <a href="<?php echo esc_url($ics_url); ?>">
                            <i class="fas fa-download"></i> <?php _e('iCalendar (.ics)', 'event-manager'); ?>
                        </a>
                        <a href="<?php echo esc_url($gcal_url); ?>" target="_blank" rel="noopener">
                            <i class="fab fa-google"></i> <?php _e('Google Calendar', 'event-manager'); ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($reg_url) || !empty($reg_deadline)) : ?>
            <div class="event-sidebar-section">
                <?php if (!empty($reg_deadline)) : ?>
                    <div class="event-sidebar-deadline">
                        <i class="fas fa-clock"></i>
                        <?php
                        printf(
                            __('Registration until %s', 'event-manager'),
                            esc_html(date_i18n(get_option('date_format'), strtotime($reg_deadline)))
                        );
                        ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($reg_url)) : ?>
                    <a href="<?php echo esc_url($reg_url); ?>" class="event-sidebar-reg-button" target="_blank" rel="noopener">
                        <?php _e('Register', 'event-manager'); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </aside>
    <script>
    (function() {
        document.querySelectorAll('.event-calendar-dropdown').forEach(function(dropdown) {
            var btn = dropdown.querySelector('.event-calendar-button');
            var menu = dropdown.querySelector('.event-calendar-menu');
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                menu.classList.toggle('is-open');
            });
        });
        document.addEventListener('click', function() {
            document.querySelectorAll('.event-calendar-menu.is-open').forEach(function(m) {
                m.classList.remove('is-open');
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Render event speakers shortcode (including child event speakers)
 */
function event_manager_event_speakers_render($atts) {
    $post_id = get_the_ID();

    if (!$post_id) {
        return '';
    }

    $event_data = event_manager_get_event_data($post_id);
    $child_events = event_manager_event_get_children($post_id);

    // Collect speaker IDs from this event and child events
    $all_speaker_ids = !empty($event_data['speaker_ids']) ? $event_data['speaker_ids'] : array();
    foreach ($child_events as $child) {
        if (!empty($child['data']['speaker_ids'])) {
            $all_speaker_ids = array_merge($all_speaker_ids, $child['data']['speaker_ids']);
        }
    }
    $all_speaker_ids = array_unique($all_speaker_ids);

    $speakers = array();
    foreach ($all_speaker_ids as $speaker_id) {
        $speaker = event_manager_get_speaker_data($speaker_id);
        if ($speaker) {
            $speakers[] = $speaker;
        }
    }

    if (empty($speakers)) {
        return '';
    }

    ob_start();
    ?>
    <div class="event-section">
        <h3><?php _e('Speakers', 'event-manager'); ?></h3>
        <div class="event-speakers">
            <?php foreach ($speakers as $speaker) : ?>
                <a href="<?php echo get_permalink($speaker['id']); ?>" class="event-speaker-card">
                    <?php if (!empty($speaker['picture'])) : ?>
                        <img src="<?php echo esc_url($speaker['picture']); ?>" alt="<?php echo esc_attr($speaker['name']); ?>" class="event-speaker-photo">
                    <?php else : ?>
                        <div class="event-speaker-photo event-speaker-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                    <div class="event-speaker-info">
                        <div class="event-speaker-name"><?php echo esc_html($speaker['name']); ?></div>
                        <?php if (!empty($speaker['organization'])) : ?>
                            <div class="event-speaker-org"><?php echo esc_html($speaker['organization']); ?></div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render event organizers shortcode
 */
function event_manager_event_organizers_render($atts) {
    $post_id = get_the_ID();

    if (!$post_id) {
        return '';
    }

    $event_data = event_manager_get_event_data($post_id);

    $organizers = array();
    if (!empty($event_data['organizer_ids'])) {
        foreach ($event_data['organizer_ids'] as $organizer_id) {
            $user = get_userdata($organizer_id);
            if ($user) {
                $organizers[] = $user;
            }
        }
    }

    if (empty($organizers)) {
        return '';
    }

    ob_start();
    ?>
    <div class="event-section">
        <h3><?php _e('Organizers', 'event-manager'); ?></h3>
        <div class="event-organizers">
            <?php foreach ($organizers as $user) : ?>
                <div class="event-organizer">
                    <?php echo get_avatar($user->ID, 40, '', $user->display_name, array('class' => 'event-organizer-avatar')); ?>
                    <span class="event-organizer-name"><?php echo esc_html($user->display_name); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render event programme shortcode (child events)
 */
function event_manager_event_programme_render($atts) {
    $post_id = get_the_ID();

    if (!$post_id) {
        return '';
    }

    $child_events = event_manager_event_get_children($post_id);

    if (empty($child_events)) {
        return '';
    }

    // Sort by start date
    usort($child_events, function ($a, $b) {
        $a_date = !empty($a['data']['start_date']) ? strtotime($a['data']['start_date']) : 0;
        $b_date = !empty($b['data']['start_date']) ? strtotime($b['data']['start_date']) : 0;
        return $a_date - $b_date;
    });

    ob_start();
    ?>
    <div class="event-section">
        <h3><?php _e('Programme', 'event-manager'); ?></h3>
        <div class="event-programme">
            <?php foreach ($child_events as $child) :
                $child_date = event_manager_event_format_date_range($child['data']['start_date'], $child['data']['end_date']);
                ?>
                <div class="event-programme-item">
                    <?php if (!empty($child_date)) : ?>
                        <div class="event-programme-date"><?php echo esc_html($child_date); ?></div>
                    <?php endif; ?>
                    <div class="event-programme-title">
                        <a href="<?php echo get_permalink($child['post']->ID); ?>">
                            <?php echo esc_html($child['post']->post_title); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
