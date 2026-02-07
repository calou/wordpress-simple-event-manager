<?php
/**
 * Event Metadata Shortcode
 *
 * @package Event_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('event_metadata', 'event_manager_event_shortcode_render');
add_filter('the_content', 'event_manager_event_prepend_metadata');
add_action('wp_enqueue_scripts', 'event_manager_event_enqueue_frontend_styles');

/**
 * Auto-prepend event metadata to event page content
 */
function event_manager_event_prepend_metadata($content) {
    if (!is_singular('page') || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $post_id = get_the_ID();
    if (!get_post_meta($post_id, '_is_event_page', true)) {
        return $content;
    }

    $metadata = event_manager_event_shortcode_render(array());
    return $metadata . $content;
}

/**
 * Enqueue frontend styles for event pages
 */
function event_manager_event_enqueue_frontend_styles() {
    if (!is_singular('page')) {
        return;
    }

    $post_id = get_the_ID();
    if (!$post_id || !get_post_meta($post_id, '_is_event_page', true)) {
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
    $start_ts = strtotime($start_date);

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
 * Render event metadata shortcode
 */
function event_manager_event_shortcode_render($atts) {
    $post_id = get_the_ID();

    if (!$post_id) {
        return '';
    }

    $event_data = event_manager_get_event_data($post_id);

    // Get venue data
    $venue = null;
    if (!empty($event_data['venue_id'])) {
        $venue = event_manager_get_venue_data($event_data['venue_id']);
    }

    // Get speakers
    $speakers = array();
    if (!empty($event_data['speaker_ids'])) {
        foreach ($event_data['speaker_ids'] as $speaker_id) {
            $speaker = event_manager_get_speaker_data($speaker_id);
            if ($speaker) {
                $speakers[] = $speaker;
            }
        }
    }

    // Get organizers
    $organizers = array();
    if (!empty($event_data['organizer_ids'])) {
        foreach ($event_data['organizer_ids'] as $organizer_id) {
            $user = get_userdata($organizer_id);
            if ($user) {
                $organizers[] = $user;
            }
        }
    }

    // Get parent event
    $parent_event = null;
    if (!empty($event_data['parent_event_id'])) {
        $parent = get_post($event_data['parent_event_id']);
        if ($parent && $parent->post_status === 'publish') {
            $parent_event = $parent;
        }
    }

    // Get sub-events
    $sub_events = get_posts(array(
        'post_type' => 'page',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_is_event_page',
                'value' => '1',
            ),
        ),
    ));

    $child_events = array();
    foreach ($sub_events as $sub) {
        $sub_data = event_manager_get_event_data($sub->ID);
        if (!empty($sub_data['parent_event_id']) && (int) $sub_data['parent_event_id'] === $post_id) {
            $child_events[] = array(
                'post' => $sub,
                'data' => $sub_data,
            );
        }
    }

    // Sort child events by start date
    usort($child_events, function ($a, $b) {
        $a_date = !empty($a['data']['start_date']) ? strtotime($a['data']['start_date']) : 0;
        $b_date = !empty($b['data']['start_date']) ? strtotime($b['data']['start_date']) : 0;
        return $a_date - $b_date;
    });

    $date_range = event_manager_event_format_date_range($event_data['start_date'], $event_data['end_date']);

    ob_start();
    ?>
    <div class="event-profile">
        <!-- Parent event breadcrumb -->
        <?php if ($parent_event) : ?>
            <div class="event-breadcrumb">
                <a href="<?php echo get_permalink($parent_event->ID); ?>">
                    <i class="fas fa-arrow-left"></i> <?php echo esc_html($parent_event->post_title); ?>
                </a>
            </div>
        <?php endif; ?>

        <!-- Event Header -->
        <div class="event-header">
            <div class="event-header-details">
                <?php if (!empty($date_range)) : ?>
                    <div class="event-header-date">
                        <i class="fas fa-calendar"></i>
                        <?php echo esc_html($date_range); ?>
                    </div>
                <?php endif; ?>

                <?php if ($venue) : ?>
                    <div class="event-header-venue">
                        <i class="fas fa-location-dot"></i>
                        <a href="<?php echo get_permalink($venue['id']); ?>"><?php echo esc_html($venue['name']); ?></a>
                        <?php
                        $venue_location = array_filter(array($venue['city'], $venue['country']));
                        if (!empty($venue_location)) :
                        ?>
                            <span class="event-header-location">(<?php echo esc_html(implode(', ', $venue_location)); ?>)</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Registration -->
            <?php
            $reg_deadline = $event_data['registration_deadline'];
            $reg_url = $event_data['registration_url'];
            $has_registration = !empty($reg_deadline) || !empty($reg_url);
            ?>
            <?php if ($has_registration) : ?>
                <div class="event-registration">
                    <?php if (!empty($reg_deadline)) : ?>
                        <div class="event-reg-deadline">
                            <i class="fas fa-clock"></i>
                            <?php
                            printf(
                                __('Registration deadline: %s', 'event-manager'),
                                esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($reg_deadline)))
                            );
                            ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($reg_url)) : ?>
                        <a href="<?php echo esc_url($reg_url); ?>" class="event-reg-button" target="_blank" rel="noopener">
                            <?php _e('Register', 'event-manager'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Speakers -->
        <?php if (!empty($speakers)) : ?>
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
        <?php endif; ?>

        <!-- Organizers -->
        <?php if (!empty($organizers)) : ?>
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
        <?php endif; ?>

        <!-- Sub-events -->
        <?php if (!empty($child_events)) : ?>
            <div class="event-section">
                <h3><?php _e('Programme', 'event-manager'); ?></h3>
                <div class="event-programme">
                    <?php foreach ($child_events as $child) :
                        $child_date = event_manager_event_format_date_range($child['data']['start_date'], $child['data']['end_date']);
                        ?>
                        <div class="event-programme-item">
                            <div class="event-programme-title">
                                <a href="<?php echo get_permalink($child['post']->ID); ?>">
                                    <?php echo esc_html($child['post']->post_title); ?>
                                </a>
                            </div>
                            <?php if (!empty($child_date)) : ?>
                                <div class="event-programme-date"><?php echo esc_html($child_date); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
