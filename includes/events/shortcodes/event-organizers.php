<?php
/**
 * Shortcode: [event_organizers]
 * Displays organizers for the current event.
 *
 * @package Event_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('event_organizers', 'event_manager_event_organizers_render');

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
