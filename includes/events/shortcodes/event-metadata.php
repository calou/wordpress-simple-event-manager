<?php
/**
 * Shortcode: [event_metadata]
 * Displays breadcrumb back to parent event and the event date.
 *
 * @package Event_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('event_metadata', 'event_manager_event_metadata_render');

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
        $start_ts    = strtotime($event_data['start_date']);
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
