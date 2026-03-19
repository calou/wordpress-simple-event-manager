<?php
/**
 * Shortcode: [event_sidebar]
 * Displays venue, add-to-calendar dropdown, and registration button/deadline.
 *
 * @package Event_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('event_sidebar', 'event_manager_event_sidebar_render');

/**
 * Render event sidebar shortcode (venue, registration, calendar)
 */
function event_manager_event_sidebar_render($atts) {
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

    $reg_deadline = $event_data['registration_deadline'];
    $reg_url      = $event_data['registration_url'];
    $has_calendar = !empty($event_data['start_date']);

    if (!$venue && empty($reg_url) && empty($reg_deadline) && !$has_calendar) {
        return '';
    }

    ob_start();
    ?>
    <aside class="event-sidebar">

        <?php if ($venue) : ?>
            <div class="event-sidebar-section">
                <a href="<?php echo get_permalink($venue['id']); ?>" class="event-sidebar-venue-name">
                    <i class="fas fa-location-dot"></i>
                    <?php echo esc_html($venue['name']); ?>
                </a>
            </div>
        <?php endif; ?>

        <?php if ($has_calendar) :
            $ics_url  = add_query_arg('event_ics', $post_id, home_url('/'));
            $gcal_url = event_manager_event_google_calendar_url($post_id);
            ?>
            <div class="event-sidebar-section">
                <div class="event-calendar-dropdown">
                    <button type="button" class="event-calendar-button">
                        <i class="fas fa-calendar-plus"></i> <?php _e('Add to Calendar', 'event-manager'); ?>
                    </button>
                    <div class="event-calendar-menu">
                        <a href="<?php echo esc_url($ics_url); ?>">
                            <i class="fas fa-download"></i> <?php _e('iCalendar (.ics)', 'event-manager'); ?>
                        </a>
                        <a href="<?php echo esc_url($gcal_url); ?>" target="_blank" rel="noopener">
                            <i class="fab fa-google"></i> <?php _e('Google Calendar', 'event-manager'); ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($reg_url) || !empty($reg_deadline)) : ?>
            <div class="event-sidebar-section">
                <?php if (!empty($reg_deadline)) : ?>
                    <div class="event-sidebar-deadline">
                        <i class="fas fa-clock"></i>
                        <?php
                        printf(
                            __('Registration until %s', 'event-manager'),
                            esc_html(date_i18n(get_option('date_format'), strtotime($reg_deadline)))
                        );
                        ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($reg_url)) : ?>
                    <a href="<?php echo esc_url($reg_url); ?>" class="event-sidebar-reg-button" target="_blank" rel="noopener">
                        <?php _e('Register', 'event-manager'); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </aside>
    <script>
    (function() {
        document.querySelectorAll('.event-calendar-dropdown').forEach(function(dropdown) {
            var btn  = dropdown.querySelector('.event-calendar-button');
            var menu = dropdown.querySelector('.event-calendar-menu');
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                menu.classList.toggle('is-open');
            });
        });
        document.addEventListener('click', function() {
            document.querySelectorAll('.event-calendar-menu.is-open').forEach(function(m) {
                m.classList.remove('is-open');
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}
