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

$speaker_post = get_post(get_the_ID());
?>

<div class="speaker-profile">
    <!-- Profile Header -->
    <div class="speaker-header">
        <?php if (has_post_thumbnail()) : ?>
            <div class="speaker-photo">
                <?php the_post_thumbnail('medium', array('alt' => get_the_title())); ?>
            </div>
        <?php endif; ?>

        <div class="speaker-header-info">
            <h2 class="speaker-name"><?php echo esc_html($speaker_post->post_title); ?></h2>

            <?php if ($speaker_data['position']) : ?>
                <div class="speaker-position"><?php echo esc_html($speaker_data['position']); ?></div>
            <?php endif; ?>

            <?php if ($speaker_data['organization']) : ?>
                <div class="speaker-organization">
                    <?php if ($speaker_data['organization_url']) : ?>
                        <a href="<?php echo esc_url($speaker_data['organization_url']); ?>" target="_blank" rel="noopener">
                            <?php echo esc_html($speaker_data['organization']); ?>
                        </a>
                    <?php else : ?>
                        <?php echo esc_html($speaker_data['organization']); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Contact Information -->
    <?php if ($speaker_data['email'] || $speaker_data['phone'] || $speaker_data['website'] || $speaker_data['linkedin'] || $speaker_data['orcid']) : ?>
        <div class="speaker-section">
            <h3><?php _e('Contact Information', 'event-manager'); ?></h3>
            <div class="speaker-contact-grid">
                <?php if ($speaker_data['email']) : ?>
                    <div class="contact-item">
                        <strong><?php _e('Email', 'event-manager'); ?></strong>
                        <a href="mailto:<?php echo esc_attr($speaker_data['email']); ?>">
                            <?php echo esc_html($speaker_data['email']); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($speaker_data['phone']) : ?>
                    <div class="contact-item">
                        <strong><?php _e('Phone', 'event-manager'); ?></strong>
                        <a href="tel:<?php echo esc_attr($speaker_data['phone']); ?>">
                            <?php echo esc_html($speaker_data['phone']); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($speaker_data['website']) : ?>
                    <div class="contact-item">
                        <strong><?php _e('Website', 'event-manager'); ?></strong>
                        <a href="<?php echo esc_url($speaker_data['website']); ?>" target="_blank" rel="noopener">
                            <?php echo esc_html($speaker_data['website']); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($speaker_data['linkedin']) : ?>
                    <div class="contact-item">
                        <strong><?php _e('LinkedIn', 'event-manager'); ?></strong>
                        <a href="<?php echo esc_url($speaker_data['linkedin']); ?>" target="_blank" rel="noopener">
                            <?php _e('View Profile', 'event-manager'); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($speaker_data['orcid']) : ?>
                    <div class="contact-item">
                        <strong><?php _e('ORCID', 'event-manager'); ?></strong>
                        <a href="https://orcid.org/<?php echo esc_attr($speaker_data['orcid']); ?>" target="_blank" rel="noopener">
                            <?php echo esc_html($speaker_data['orcid']); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Events -->
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

    <?php if (!empty($upcoming_events) || !empty($past_events)) : ?>
        <div class="speaker-section">
            <h3><?php _e('Events', 'event-manager'); ?></h3>

            <?php if (!empty($upcoming_events)) : ?>
                <div class="events-group">
                    <h4><?php _e('Upcoming', 'event-manager'); ?></h4>
                    <?php foreach ($upcoming_events as $event) :
                        $event_data = event_manager_get_event_data($event->ID);
                        ?>
                        <div class="event-item">
                            <div class="event-title">
                                <a href="<?php echo get_permalink($event->ID); ?>">
                                    <?php echo esc_html($event->post_title); ?>
                                </a>
                            </div>
                            <?php if ($event_data['start_date']) : ?>
                                <div class="event-date">
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event_data['start_date']))); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($past_events)) : ?>
                <div class="events-group">
                    <h4><?php _e('Past', 'event-manager'); ?></h4>
                    <?php foreach ($past_events as $event) :
                        $event_data = event_manager_get_event_data($event->ID);
                        ?>
                        <div class="event-item">
                            <div class="event-title">
                                <a href="<?php echo get_permalink($event->ID); ?>">
                                    <?php echo esc_html($event->post_title); ?>
                                </a>
                            </div>
                            <?php if ($event_data['start_date']) : ?>
                                <div class="event-date">
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event_data['start_date']))); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
