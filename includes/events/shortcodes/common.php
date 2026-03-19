<?php
/**
 * Shared utilities for event shortcodes
 *
 * @package Event_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', 'event_manager_event_enqueue_frontend_styles');
add_action('template_redirect', 'event_manager_event_ics_download');

/**
 * Enqueue frontend styles for event pages and pages containing event shortcodes
 */
function event_manager_event_enqueue_frontend_styles() {
    global $post;

    if (!is_singular('page') || !$post) {
        return;
    }

    $is_event_page = event_manager_is_event_page($post->ID);
    $has_list_shortcode = has_shortcode($post->post_content, 'events_upcoming')
        || has_shortcode($post->post_content, 'events_past')
        || has_shortcode($post->post_content, 'events_calendar');

    if (!$is_event_page && !$has_list_shortcode) {
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
    $start_ts    = strtotime($start_date);

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
 * Format a single timestamp for event list display:
 * - "dd/mm"      when the date falls in the current year
 * - "dd/mm/yyyy" otherwise
 */
function event_manager_events_list_format_date($ts) {
    $current_year = (int) current_time('Y');
    $format = (int) date('Y', $ts) === $current_year ? 'd/m' : 'd/m/Y';
    return date_i18n($format, $ts);
}

/**
 * Format a date range for event list display
 */
function event_manager_events_list_format_date_range($start_date, $end_date) {
    if (empty($start_date)) {
        return '';
    }

    $start_ts = strtotime($start_date);

    if (empty($end_date) || date('Y-m-d', $start_ts) === date('Y-m-d', strtotime($end_date))) {
        return event_manager_events_list_format_date($start_ts);
    }

    return event_manager_events_list_format_date($start_ts)
        . ' – '
        . event_manager_events_list_format_date(strtotime($end_date));
}

/**
 * Get child events for a given event using page hierarchy
 */
function event_manager_event_get_children($post_id) {
    $child_pages = get_posts(array(
        'post_type'      => 'page',
        'post_parent'    => $post_id,
        'posts_per_page' => -1,
        'meta_query'     => event_manager_event_page_meta_query(),
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
    $post    = get_post($post_id);

    if (!$post || !event_manager_is_event_page($post_id)) {
        return;
    }

    $event_data = event_manager_get_event_data($post_id);

    if (empty($event_data['start_date'])) {
        return;
    }

    $start_ts = strtotime($event_data['start_date']);
    $end_ts   = !empty($event_data['end_date']) ? strtotime($event_data['end_date']) : $start_ts + 3600;

    $location = '';
    if (!empty($event_data['venue_id'])) {
        $venue = event_manager_get_venue_data($event_data['venue_id']);
        if ($venue) {
            $location_parts = array_filter(array($venue['name'], $venue['address'], $venue['city'], $venue['country']));
            $location = implode(', ', $location_parts);
        }
    }

    $summary     = $post->post_title;
    $description = wp_strip_all_tags($post->post_excerpt ?: wp_trim_words($post->post_content, 50, '...'));
    $url         = get_permalink($post_id);
    $uid         = 'event-' . $post_id . '@' . wp_parse_url(home_url(), PHP_URL_HOST);

    $ics  = "BEGIN:VCALENDAR\r\n";
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
    $post       = get_post($post_id);
    $event_data = event_manager_get_event_data($post_id);

    if (empty($event_data['start_date'])) {
        return '';
    }

    $start_ts = strtotime($event_data['start_date']);
    $end_ts   = !empty($event_data['end_date']) ? strtotime($event_data['end_date']) : $start_ts + 3600;

    $params = array(
        'action'  => 'TEMPLATE',
        'text'    => $post->post_title,
        'dates'   => gmdate('Ymd\THis\Z', $start_ts) . '/' . gmdate('Ymd\THis\Z', $end_ts),
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
