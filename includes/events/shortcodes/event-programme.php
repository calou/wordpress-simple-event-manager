<?php
/**
 * Shortcode: [event_programme]
 * Displays child events (sessions) of the current event, sorted by start date.
 *
 * @package Event_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('event_programme', 'event_manager_event_programme_render');

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
