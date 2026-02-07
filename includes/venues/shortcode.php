<?php
/**
 * Venue Metadata Shortcode
 *
 * @package Event_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('venue_metadata', 'event_manager_venue_shortcode_render');

/**
 * Build the full address string for map embedding
 */
function event_manager_venue_build_address($venue_data) {
    $parts = array_filter(array(
        $venue_data['address'],
        $venue_data['city'],
        $venue_data['state'],
        $venue_data['zip'],
        $venue_data['country'],
    ));

    return implode(', ', $parts);
}

/**
 * Render venue metadata shortcode
 */
function event_manager_venue_shortcode_render($atts) {
    $post_id = get_the_ID();

    if (!$post_id) {
        return '';
    }

    $venue_data = event_manager_venue_get_data($post_id);
    $full_address = event_manager_venue_build_address($venue_data);

    // Find events held at this venue
    $venue_events = get_posts(array(
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

    foreach ($venue_events as $event) {
        $event_data = event_manager_get_event_data($event->ID);
        if (!empty($event_data['venue_id']) && (int) $event_data['venue_id'] === $post_id) {
            $event_timestamp = !empty($event_data['start_date']) ? strtotime($event_data['start_date']) : 0;
            if ($event_timestamp >= $current_time) {
                $upcoming_events[] = $event;
            } else {
                $past_events[] = $event;
            }
        }
    }

    ob_start();
    ?>
    <div class="venue-profile">
        <!-- Venue Header -->
        <div class="venue-header">
            <?php if (has_post_thumbnail($post_id)) : ?>
                <div class="venue-photo">
                    <?php echo get_the_post_thumbnail($post_id, 'medium', array('alt' => get_the_title($post_id))); ?>
                </div>
            <?php endif; ?>

            <div class="venue-header-info">
                <h1 class="venue-name">
                    <?php if ($venue_data['website']) : ?>
                        <a href="<?php echo esc_url($venue_data['website']); ?>" target="_blank" rel="noopener"><?php echo esc_html(get_the_title($post_id)); ?></a>
                    <?php else : ?>
                        <?php echo esc_html(get_the_title($post_id)); ?>
                    <?php endif; ?>
                </h1>

                <?php if (!empty($full_address)) : ?>
                    <div class="venue-address"><?php echo esc_html($full_address); ?></div>
                <?php endif; ?>

                <div class="venue-meta">
                    <?php if ($venue_data['phone']) : ?>
                        <span class="venue-meta-item">
                            <i class="fas fa-phone"></i>
                            <a href="tel:<?php echo esc_attr($venue_data['phone']); ?>"><?php echo esc_html($venue_data['phone']); ?></a>
                        </span>
                    <?php endif; ?>

                    <?php if ($venue_data['capacity']) : ?>
                        <span class="venue-meta-item">
                            <i class="fas fa-users"></i>
                            <?php printf(__('Capacity: %s', 'event-manager'), esc_html($venue_data['capacity'])); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Map -->
        <?php if (!empty($full_address)) : ?>
            <div class="venue-map">
                <iframe
                    width="100%"
                    height="400"
                    frameborder="0"
                    style="border:0"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    src="https://www.google.com/maps?q=<?php echo urlencode($full_address); ?>&output=embed">
                </iframe>
            </div>
        <?php endif; ?>

        <!-- Events at this venue -->
        <?php if (!empty($upcoming_events) || !empty($past_events)) : ?>
            <div class="venue-section">
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
