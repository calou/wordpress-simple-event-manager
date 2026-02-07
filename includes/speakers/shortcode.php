<?php
/**
 * Speaker Metadata Block
 *
 * @package Event_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('speaker_metadata', 'event_manager_speaker_block_render');

/**
 * Render speaker metadata shortcode
 */
function event_manager_speaker_block_render($atts) {
    $post_id = get_the_ID();

    if (!$post_id) {
        return '';
    }

    $speaker_data = event_manager_get_speaker_data($post_id);

    if (!$speaker_data) {
        return '';
    }

    ob_start();
    ?>
    <div class="speaker-profile">
        <!-- Profile Header -->
        <div class="speaker-header">
            <?php if (has_post_thumbnail($post_id)) : ?>
                <div class="speaker-photo">
                    <?php echo get_the_post_thumbnail($post_id, 'medium', array('alt' => get_the_title($post_id))); ?>
                </div>
            <?php endif; ?>

            <div class="speaker-header-info">
                <div class="speaker-name-title">
                    <h1 class="speaker-name"><?php echo esc_html(get_the_title($post_id)); ?></h1>

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

                <!-- Contact Icons -->
                <?php if ($speaker_data['email'] || $speaker_data['phone'] || $speaker_data['website'] || $speaker_data['linkedin'] || $speaker_data['orcid']) : ?>
                    <div class="speaker-contact-icons">
                        <?php if ($speaker_data['email']) : ?>
                            <a href="mailto:<?php echo esc_attr($speaker_data['email']); ?>" class="contact-icon-link" title="<?php echo esc_attr($speaker_data['email']); ?>">
                                <i class="fas fa-envelope"></i>
                            </a>
                        <?php endif; ?>

                        <?php if ($speaker_data['phone']) : ?>
                            <a href="tel:<?php echo esc_attr($speaker_data['phone']); ?>" class="contact-icon-link" title="<?php echo esc_attr($speaker_data['phone']); ?>">
                                <i class="fas fa-phone"></i>
                            </a>
                        <?php endif; ?>

                        <?php if ($speaker_data['website']) : ?>
                            <a href="<?php echo esc_url($speaker_data['website']); ?>" class="contact-icon-link" target="_blank" rel="noopener" title="<?php echo esc_attr($speaker_data['website']); ?>">
                                <i class="fas fa-globe"></i>
                            </a>
                        <?php endif; ?>

                        <?php if ($speaker_data['linkedin']) : ?>
                            <a href="<?php echo esc_url($speaker_data['linkedin']); ?>" class="contact-icon-link" target="_blank" rel="noopener" title="LinkedIn">
                                <i class="fab fa-linkedin"></i>
                            </a>
                        <?php endif; ?>

                        <?php if ($speaker_data['orcid']) : ?>
                            <a href="https://orcid.org/<?php echo esc_attr($speaker_data['orcid']); ?>" class="contact-icon-link" target="_blank" rel="noopener" title="ORCID: <?php echo esc_attr($speaker_data['orcid']); ?>">
                                <i class="fab fa-orcid"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

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
            if (in_array($post_id, $event_data['speaker_ids'])) {
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
    <?php
    return ob_get_clean();
}
