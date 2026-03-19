<?php
/**
 * Shortcode: [event_speakers]
 * Displays speakers for the event and all its child events.
 *
 * @package Event_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('event_speakers', 'event_manager_event_speakers_render');

/**
 * Render event speakers shortcode (including child event speakers)
 */
function event_manager_event_speakers_render($atts) {
    $post_id = get_the_ID();

    if (!$post_id) {
        return '';
    }

    $event_data   = event_manager_get_event_data($post_id);
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
