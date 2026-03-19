<?php
/**
 * Shortcode: [events_calendar]
 * Displays a FullCalendar month view of all parent events.
 *
 * Attributes:
 *   category – category slug or ID to filter by (optional)
 *
 * @package Event_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('events_calendar', 'event_manager_events_calendar_render');

/**
 * Render the FullCalendar shortcode
 */
function event_manager_events_calendar_render($atts) {
    static $instance = 0;
    $instance++;

    $atts = shortcode_atts(array(
        'category' => '',
    ), $atts, 'events_calendar');

    $query_args = array(
        'post_type'      => 'page',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'post_parent'    => 0,
        'meta_query'     => event_manager_event_page_meta_query(),
    );

    if (!empty($atts['category'])) {
        $cat = sanitize_text_field($atts['category']);
        if (is_numeric($cat)) {
            $query_args['cat'] = intval($cat);
        } else {
            $query_args['category_name'] = $cat;
        }
    }

    $posts = get_posts($query_args);

    $fc_events = array();
    foreach ($posts as $post) {
        $data = event_manager_get_event_data($post->ID);
        if (empty($data['start_date'])) {
            continue;
        }
        $fc_event = array(
            'title' => $post->post_title,
            'start' => $data['start_date'],
            'url'   => get_permalink($post->ID),
        );
        if (!empty($data['end_date'])) {
            $fc_event['end'] = $data['end_date'];
        }
        if (!empty($data['color'])) {
            $fc_event['backgroundColor'] = $data['color'];
            $fc_event['borderColor']     = $data['color'];
        }
        $fc_events[] = $fc_event;
    }

    $calendar_id = 'events-calendar-' . $instance;

    wp_enqueue_script(
        'fullcalendar',
        'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js',
        array(),
        '6.1.15',
        true
    );

    $init_script = sprintf(
        '(function(){
    var el = document.getElementById(%s);
    if (!el) return;
    new FullCalendar.Calendar(el, {
        initialView: "dayGridMonth",
        headerToolbar: { left: "prev,next today", center: "title", right: "dayGridMonth,listMonth" },
        events: %s,
        eventClick: function(info) {
            if (info.event.url) { info.jsEvent.preventDefault(); window.location.href = info.event.url; }
        }
    }).render();
})();',
        wp_json_encode($calendar_id),
        wp_json_encode($fc_events)
    );

    wp_add_inline_script('fullcalendar', $init_script);

    return '<div id="' . esc_attr($calendar_id) . '" class="events-calendar"></div>';
}
