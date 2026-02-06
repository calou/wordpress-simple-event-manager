<?php
/**
 * Content template for speaker metadata
 * This content is appended to the speaker's bio
 *
 * @package Event_Manager
 */

// Don't access directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="speaker-metadata">
    <?php if ($speaker_data['position'] || $speaker_data['organization']) : ?>
        <div class="speaker-role">
            <?php if ($speaker_data['position']) : ?>
                <strong><?php echo esc_html($speaker_data['position']); ?></strong>
            <?php endif; ?>
            <?php if ($speaker_data['organization']) : ?>
                <?php if ($speaker_data['position']) echo ' · '; ?>
                <?php if ($speaker_data['organization_url']) : ?>
                    <a href="<?php echo esc_url($speaker_data['organization_url']); ?>" target="_blank" rel="noopener">
                        <?php echo esc_html($speaker_data['organization']); ?>
                    </a>
                <?php else : ?>
                    <?php echo esc_html($speaker_data['organization']); ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($speaker_data['email'] || $speaker_data['phone'] || $speaker_data['website'] || $speaker_data['linkedin'] || $speaker_data['orcid']) : ?>
        <div class="speaker-contact">
            <h3><?php _e('Contact & Links', 'event-manager'); ?></h3>
            <ul class="speaker-contact-list">
                <?php if ($speaker_data['email']) : ?>
                    <li>
                        <span class="contact-label"><?php _e('Email:', 'event-manager'); ?></span>
                        <a href="mailto:<?php echo esc_attr($speaker_data['email']); ?>">
                            <?php echo esc_html($speaker_data['email']); ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($speaker_data['phone']) : ?>
                    <li>
                        <span class="contact-label"><?php _e('Phone:', 'event-manager'); ?></span>
                        <a href="tel:<?php echo esc_attr($speaker_data['phone']); ?>">
                            <?php echo esc_html($speaker_data['phone']); ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($speaker_data['website']) : ?>
                    <li>
                        <span class="contact-label"><?php _e('Website:', 'event-manager'); ?></span>
                        <a href="<?php echo esc_url($speaker_data['website']); ?>" target="_blank" rel="noopener">
                            <?php echo esc_html($speaker_data['website']); ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($speaker_data['linkedin']) : ?>
                    <li>
                        <span class="contact-label"><?php _e('LinkedIn:', 'event-manager'); ?></span>
                        <a href="<?php echo esc_url($speaker_data['linkedin']); ?>" target="_blank" rel="noopener">
                            <?php _e('View Profile', 'event-manager'); ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($speaker_data['orcid']) : ?>
                    <li>
                        <span class="contact-label"><?php _e('ORCID:', 'event-manager'); ?></span>
                        <a href="https://orcid.org/<?php echo esc_attr($speaker_data['orcid']); ?>" target="_blank" rel="noopener">
                            <?php echo esc_html($speaker_data['orcid']); ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php
    // Find events where this speaker is presenting
    $speaker_events = get_posts(array(
        'post_type' => 'page',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_is_event_page',
                'value' => '1',
            ),
        ),
    ));

    $upcoming_events = array();
    $past_events = array();
    $current_time = current_time('timestamp');

    foreach ($speaker_events as $event) {
        $event_data = event_manager_get_event_data($event->ID);
        if (in_array(get_the_ID(), $event_data['speaker_ids'])) {
            $event_timestamp = !empty($event_data['start_date']) ? strtotime($event_data['start_date']) : 0;
            if ($event_timestamp >= $current_time) {
                $upcoming_events[] = $event;
            } else {
                $past_events[] = $event;
            }
        }
    }
    ?>

    <?php if (!empty($upcoming_events)) : ?>
        <div class="speaker-events upcoming">
            <h3><?php _e('Upcoming Events', 'event-manager'); ?></h3>
            <ul class="events-list">
                <?php foreach ($upcoming_events as $event) :
                    $event_data = event_manager_get_event_data($event->ID);
                    ?>
                    <li>
                        <a href="<?php echo get_permalink($event->ID); ?>">
                            <?php echo esc_html($event->post_title); ?>
                        </a>
                        <?php if ($event_data['start_date']) : ?>
                            <span class="event-date">
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event_data['start_date']))); ?>
                            </span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($past_events)) : ?>
        <div class="speaker-events past">
            <h3><?php _e('Past Events', 'event-manager'); ?></h3>
            <ul class="events-list">
                <?php foreach ($past_events as $event) :
                    $event_data = event_manager_get_event_data($event->ID);
                    ?>
                    <li>
                        <a href="<?php echo get_permalink($event->ID); ?>">
                            <?php echo esc_html($event->post_title); ?>
                        </a>
                        <?php if ($event_data['start_date']) : ?>
                            <span class="event-date">
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event_data['start_date']))); ?>
                            </span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
