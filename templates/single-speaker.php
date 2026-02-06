<?php
/**
 * Template for displaying single speaker
 *
 * @package Event_Manager
 *
 * Theme Customization:
 * - Copy this file to your theme as 'single-speaker.php' to customize
 * - Use action hooks:
 *   - 'event_manager_before_main_content' - before content wrapper
 *   - 'event_manager_after_main_content' - after content wrapper
 * - Override wrapper functions:
 *   - event_manager_output_content_wrapper()
 *   - event_manager_output_content_wrapper_end()
 *
 * Available data:
 * - $speaker_data = event_manager_get_speaker_data(get_the_ID())
 * - Contains: name, position, organization, orcid, email, phone, website, linkedin, picture
 */

get_header();

// Allow themes to add wrapper start
do_action('event_manager_before_main_content');
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

    <?php
    while (have_posts()) :
        the_post();
        $speaker_data = event_manager_get_speaker_data(get_the_ID());
        ?>

        <article id="speaker-<?php the_ID(); ?>" <?php post_class('speaker-single'); ?>>
            <div class="speaker-container">
            <header class="speaker-header">
                <h1 class="speaker-name"><?php the_title(); ?></h1>

                <?php if ($speaker_data['position'] || $speaker_data['organization']) : ?>
                    <p class="speaker-position">
                        <?php
                        if ($speaker_data['position']) {
                            echo esc_html($speaker_data['position']);
                        }
                        if ($speaker_data['position'] && $speaker_data['organization']) {
                            echo ' &middot; ';
                        }
                        if ($speaker_data['organization']) {
                            if ($speaker_data['organization_url']) {
                                echo '<a href="' . esc_url($speaker_data['organization_url']) . '" target="_blank" rel="noopener">' . esc_html($speaker_data['organization']) . '</a>';
                            } else {
                                echo esc_html($speaker_data['organization']);
                            }
                        }
                        ?>
                    </p>
                <?php endif; ?>
            </header>

            <div class="speaker-content">
                <?php if (has_post_thumbnail()) : ?>
                    <div class="speaker-photo">
                        <?php the_post_thumbnail('large', array('alt' => get_the_title())); ?>
                    </div>
                <?php endif; ?>

                <div class="speaker-info">
                    <?php if (get_the_content()) : ?>
                        <div class="speaker-bio">
                            <h2><?php _e('Biography', 'event-manager'); ?></h2>
                            <?php the_content(); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($speaker_data['email'] || $speaker_data['phone'] || $speaker_data['website'] || $speaker_data['linkedin'] || $speaker_data['orcid']) : ?>
                        <div class="speaker-contact">
                            <h2><?php _e('Contact & Links', 'event-manager'); ?></h2>
                            <ul class="speaker-contact-list">
                                <?php if ($speaker_data['email']) : ?>
                                    <li>
                                        <span class="contact-label"><?php _e('Email:', 'event-manager'); ?></span>
                                        <a href="mailto:<?php echo esc_attr($speaker_data['email']); ?>"><?php echo esc_html($speaker_data['email']); ?></a>
                                    </li>
                                <?php endif; ?>

                                <?php if ($speaker_data['phone']) : ?>
                                    <li>
                                        <span class="contact-label"><?php _e('Phone:', 'event-manager'); ?></span>
                                        <a href="tel:<?php echo esc_attr($speaker_data['phone']); ?>"><?php echo esc_html($speaker_data['phone']); ?></a>
                                    </li>
                                <?php endif; ?>

                                <?php if ($speaker_data['website']) : ?>
                                    <li>
                                        <span class="contact-label"><?php _e('Website:', 'event-manager'); ?></span>
                                        <a href="<?php echo esc_url($speaker_data['website']); ?>" target="_blank" rel="noopener"><?php echo esc_html($speaker_data['website']); ?></a>
                                    </li>
                                <?php endif; ?>

                                <?php if ($speaker_data['linkedin']) : ?>
                                    <li>
                                        <span class="contact-label"><?php _e('LinkedIn:', 'event-manager'); ?></span>
                                        <a href="<?php echo esc_url($speaker_data['linkedin']); ?>" target="_blank" rel="noopener"><?php _e('View Profile', 'event-manager'); ?></a>
                                    </li>
                                <?php endif; ?>

                                <?php if ($speaker_data['orcid']) : ?>
                                    <li>
                                        <span class="contact-label"><?php _e('ORCID:', 'event-manager'); ?></span>
                                        <a href="https://orcid.org/<?php echo esc_attr($speaker_data['orcid']); ?>" target="_blank" rel="noopener"><?php echo esc_html($speaker_data['orcid']); ?></a>
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
                            <h2><?php _e('Upcoming Events', 'event-manager'); ?></h2>
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
                            <h2><?php _e('Past Events', 'event-manager'); ?></h2>
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
            </div>
            </div>
        </article>

        <?php
    endwhile;
    ?>

    </main>
</div>

<?php
// Allow themes to add wrapper end
do_action('event_manager_after_main_content');

get_sidebar();
get_footer();
