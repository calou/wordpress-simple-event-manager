<?php
/**
 * Template Name: Page Event
 * Description: Custom template for displaying event information
 */

get_header();

// Get event data
$event_data = event_manager_get_event_data();
?>

<div id="primary" class="content-area event-page">
    <main id="main" class="site-main">
        
        <?php while (have_posts()) : the_post(); ?>
        
        <article id="post-<?php the_ID(); ?>" <?php post_class('event-article'); ?>>
            
            <header class="entry-header">
                <h1 class="entry-title"><?php the_title(); ?></h1>
            </header>
            
            <div class="event-content">
                
                <!-- Event Dates Section -->
                <div class="event-section event-dates">
                    <h2><?php _e('Event Information', 'event-manager'); ?></h2>
                    
                    <?php if (!empty($event_data['start_date'])) : ?>
                        <div class="event-date-item">
                            <span class="event-label"><?php _e('Start Date:', 'event-manager'); ?></span>
                            <span class="event-value"><?php echo esc_html(event_manager_format_date($event_data['start_date'])); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($event_data['end_date'])) : ?>
                        <div class="event-date-item">
                            <span class="event-label"><?php _e('End Date:', 'event-manager'); ?></span>
                            <span class="event-value"><?php echo esc_html(event_manager_format_date($event_data['end_date'])); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($event_data['registration_deadline'])) : ?>
                        <div class="event-date-item registration-deadline">
                            <span class="event-label"><?php _e('Registration Deadline:', 'event-manager'); ?></span>
                            <span class="event-value"><?php echo esc_html(event_manager_format_date($event_data['registration_deadline'])); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($event_data['registration_url'])) : ?>
                        <div class="event-registration-cta">
                            <a href="<?php echo esc_url($event_data['registration_url']); ?>" class="registration-button" target="_blank" rel="noopener noreferrer">
                                <?php _e('Register Now', 'event-manager'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Venue Section -->
                <?php 
                $venue_data = event_manager_get_venue_data($event_data['venue_id']);
                if ($venue_data) :
                ?>
                <div class="event-section event-venue">
                    <h2><?php _e('Venue', 'event-manager'); ?></h2>
                    <div class="venue-details">
                        <?php if ($venue_data['thumbnail']) : ?>
                            <div class="venue-image">
                                <img src="<?php echo esc_url($venue_data['thumbnail']); ?>" alt="<?php echo esc_attr($venue_data['name']); ?>">
                            </div>
                        <?php endif; ?>
                        
                        <div class="venue-info">
                            <h3 class="venue-name"><?php echo esc_html($venue_data['name']); ?></h3>
                            
                            <?php if (!empty($venue_data['address']) || !empty($venue_data['city'])) : ?>
                                <div class="venue-address">
                                    <?php if (!empty($venue_data['address'])) : ?>
                                        <p><?php echo esc_html($venue_data['address']); ?></p>
                                    <?php endif; ?>
                                    <p>
                                        <?php 
                                        $location_parts = array_filter(array(
                                            $venue_data['city'],
                                            $venue_data['state'],
                                            $venue_data['zip'],
                                            $venue_data['country']
                                        ));
                                        echo esc_html(implode(', ', $location_parts));
                                        ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($venue_data['capacity'])) : ?>
                                <p class="venue-capacity">
                                    <strong><?php _e('Capacity:', 'event-manager'); ?></strong>
                                    <?php echo esc_html($venue_data['capacity']); ?> <?php _e('people', 'event-manager'); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="venue-links">
                                <?php if (!empty($venue_data['phone'])) : ?>
                                    <a href="tel:<?php echo esc_attr($venue_data['phone']); ?>" class="venue-phone">
                                        <?php echo esc_html($venue_data['phone']); ?>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (!empty($venue_data['website'])) : ?>
                                    <a href="<?php echo esc_url($venue_data['website']); ?>" class="venue-website" target="_blank" rel="noopener noreferrer">
                                        <?php _e('Visit Website', 'event-manager'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($venue_data['description'])) : ?>
                                <div class="venue-description">
                                    <?php echo wpautop(wp_kses_post($venue_data['description'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Event Description -->
                <?php if (get_the_content()) : ?>
                <div class="event-section event-description">
                    <h2><?php _e('About This Event', 'event-manager'); ?></h2>
                    <div class="event-content-text">
                        <?php the_content(); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Speakers Section -->
                <?php 
                $speakers = $event_data['speaker_ids'];
                if (!empty($speakers) && is_array($speakers)) :
                ?>
                <div class="event-section event-speakers">
                    <h2><?php _e('Speakers', 'event-manager'); ?></h2>
                    <div class="speakers-grid">
                        <?php
                        foreach ($speakers as $speaker_id) {
                            $speaker = get_post($speaker_id);
                            if ($speaker && $speaker->post_status == 'publish') :
                                $speaker_image = get_the_post_thumbnail($speaker_id, 'medium', array('class' => 'speaker-photo'));
                                $speaker_bio = get_the_excerpt($speaker_id);
                        ?>
                            <div class="speaker-card">
                                <?php if ($speaker_image) : ?>
                                    <div class="speaker-image">
                                        <?php echo $speaker_image; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="speaker-info">
                                    <h3 class="speaker-name"><?php echo esc_html($speaker->post_title); ?></h3>
                                    <?php if ($speaker_bio) : ?>
                                        <p class="speaker-bio"><?php echo esc_html($speaker_bio); ?></p>
                                    <?php endif; ?>
                                    <a href="<?php echo get_permalink($speaker_id); ?>" class="speaker-link"><?php _e('View Profile', 'event-manager'); ?></a>
                                </div>
                            </div>
                        <?php
                            endif;
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Organizers Section -->
                <?php 
                $organizers = $event_data['organizer_ids'];
                if (!empty($organizers) && is_array($organizers)) :
                ?>
                <div class="event-section event-organizers">
                    <h2><?php _e('Organizers', 'event-manager'); ?></h2>
                    <div class="organizers-list">
                        <?php
                        foreach ($organizers as $user_id) {
                            $user = get_userdata($user_id);
                            if ($user) :
                                $avatar = get_avatar($user_id, 64, '', $user->display_name, array('class' => 'organizer-avatar'));
                        ?>
                            <div class="organizer-item">
                                <div class="organizer-avatar-wrap">
                                    <?php echo $avatar; ?>
                                </div>
                                <div class="organizer-info">
                                    <h4 class="organizer-name"><?php echo esc_html($user->display_name); ?></h4>
                                    <?php if (!empty($user->user_email)) : ?>
                                        <a href="mailto:<?php echo esc_attr($user->user_email); ?>" class="organizer-email"><?php echo esc_html($user->user_email); ?></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php
                            endif;
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Parent Event Section -->
                <?php 
                $parent_event_id = $event_data['parent_event_id'];
                if (!empty($parent_event_id)) :
                    $parent_event = get_post($parent_event_id);
                    if ($parent_event) :
                ?>
                <div class="event-section event-parent">
                    <h2><?php _e('Part of', 'event-manager'); ?></h2>
                    <div class="parent-event-info">
                        <h3>
                            <a href="<?php echo get_permalink($parent_event_id); ?>">
                                <?php echo esc_html($parent_event->post_title); ?>
                            </a>
                        </h3>
                        <?php if ($parent_event->post_excerpt) : ?>
                            <p><?php echo esc_html($parent_event->post_excerpt); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php 
                    endif;
                endif; 
                ?>
                
                <!-- Sub-Events Section -->
                <?php
                // Query for pages that have this event as their parent
                $all_pages = get_pages(array(
                    'post_status' => 'publish',
                    'meta_key' => '_event_data',
                ));
                
                $sub_events = array();
                foreach ($all_pages as $page) {
                    $page_event_data = event_manager_get_event_data($page->ID);
                    if ($page_event_data['parent_event_id'] == get_the_ID()) {
                        $sub_events[] = $page;
                    }
                }
                
                if (!empty($sub_events)) :
                ?>
                <div class="event-section event-sub-events">
                    <h2><?php _e('Related Events', 'event-manager'); ?></h2>
                    <div class="sub-events-list">
                        <?php foreach ($sub_events as $sub_event) : 
                            $sub_event_data = event_manager_get_event_data($sub_event->ID);
                        ?>
                            <div class="sub-event-item">
                                <h3>
                                    <a href="<?php echo get_permalink($sub_event->ID); ?>">
                                        <?php echo esc_html($sub_event->post_title); ?>
                                    </a>
                                </h3>
                                <?php if (!empty($sub_event_data['start_date'])) : ?>
                                    <p class="sub-event-date">
                                        <strong><?php _e('Date:', 'event-manager'); ?></strong>
                                        <?php echo esc_html(event_manager_format_date($sub_event_data['start_date'], 'F j, Y')); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($sub_event->post_excerpt) : ?>
                                    <p class="sub-event-excerpt"><?php echo esc_html($sub_event->post_excerpt); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
            
        </article>
        
        <?php endwhile; ?>
        
    </main>
</div>

<style>
/* Event Template Styles */
.event-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.event-article {
    background: #fff;
    padding: 40px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.event-section {
    margin: 40px 0;
    padding: 30px 0;
    border-bottom: 1px solid #e0e0e0;
}

.event-section:last-child {
    border-bottom: none;
}

.event-section h2 {
    font-size: 1.8em;
    margin-bottom: 20px;
    color: #333;
    border-left: 4px solid #0073aa;
    padding-left: 15px;
}

/* Event Dates */
.event-date-item {
    margin: 15px 0;
    display: flex;
    flex-wrap: wrap;
    font-size: 1.1em;
}

.event-label {
    font-weight: bold;
    margin-right: 10px;
    color: #555;
}

.event-value {
    color: #333;
}

.registration-deadline {
    background: #fff3cd;
    padding: 15px;
    border-radius: 4px;
    border-left: 4px solid #ffc107;
}

.event-registration-cta {
    margin-top: 25px;
    text-align: center;
}

.registration-button {
    display: inline-block;
    background: #0073aa;
    color: #fff;
    padding: 15px 40px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    font-size: 1.2em;
    transition: background 0.3s ease, transform 0.2s ease;
    box-shadow: 0 3px 10px rgba(0,115,170,0.3);
}

.registration-button:hover {
    background: #005a87;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,115,170,0.4);
    color: #fff;
}

/* Venue Section */
.venue-details {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 30px;
    background: #f9f9f9;
    padding: 25px;
    border-radius: 8px;
}

.venue-image img {
    width: 100%;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.venue-name {
    font-size: 1.5em;
    margin: 0 0 15px 0;
    color: #333;
}

.venue-address {
    margin: 15px 0;
    color: #555;
    line-height: 1.6;
}

.venue-capacity {
    margin: 10px 0;
    color: #666;
}

.venue-links {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin: 15px 0;
}

.venue-phone,
.venue-website {
    color: #0073aa;
    text-decoration: none;
    font-weight: 500;
    padding: 8px 16px;
    border: 1px solid #0073aa;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.venue-phone:hover,
.venue-website:hover {
    background: #0073aa;
    color: #fff;
}

.venue-description {
    margin-top: 20px;
    color: #555;
    line-height: 1.7;
}

/* Event Description */
.event-content-text {
    font-size: 1.1em;
    line-height: 1.8;
    color: #444;
}

/* Speakers Grid */
.speakers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 20px;
}

.speaker-card {
    background: #f9f9f9;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.speaker-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.speaker-image {
    width: 100%;
    height: 250px;
    overflow: hidden;
}

.speaker-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.speaker-info {
    padding: 20px;
}

.speaker-name {
    font-size: 1.3em;
    margin: 0 0 10px 0;
    color: #333;
}

.speaker-bio {
    color: #666;
    line-height: 1.6;
    margin-bottom: 15px;
}

.speaker-link {
    color: #0073aa;
    text-decoration: none;
    font-weight: bold;
}

.speaker-link:hover {
    text-decoration: underline;
}

/* Organizers List */
.organizers-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.organizer-item {
    display: flex;
    align-items: center;
    background: #f9f9f9;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.organizer-avatar-wrap {
    margin-right: 15px;
}

.organizer-avatar {
    border-radius: 50%;
}

.organizer-name {
    margin: 0 0 5px 0;
    font-size: 1.1em;
    color: #333;
}

.organizer-email {
    color: #0073aa;
    text-decoration: none;
    font-size: 0.9em;
}

.organizer-email:hover {
    text-decoration: underline;
}

/* Parent Event */
.parent-event-info {
    background: #e7f3ff;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #0073aa;
}

.parent-event-info h3 {
    margin-top: 0;
}

.parent-event-info a {
    color: #0073aa;
    text-decoration: none;
    font-size: 1.2em;
}

.parent-event-info a:hover {
    text-decoration: underline;
}

/* Sub-Events */
.sub-events-list {
    display: grid;
    gap: 20px;
    margin-top: 20px;
}

.sub-event-item {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #0073aa;
}

.sub-event-item h3 {
    margin-top: 0;
}

.sub-event-item a {
    color: #0073aa;
    text-decoration: none;
}

.sub-event-item a:hover {
    text-decoration: underline;
}

.sub-event-date {
    color: #666;
    font-size: 0.95em;
}

.sub-event-excerpt {
    color: #555;
    line-height: 1.6;
}

/* Responsive Design */
@media (max-width: 768px) {
    .event-article {
        padding: 20px;
    }
    
    .speakers-grid {
        grid-template-columns: 1fr;
    }
    
    .organizers-list {
        grid-template-columns: 1fr;
    }
    
    .venue-details {
        grid-template-columns: 1fr;
    }
    
    .venue-links {
        flex-direction: column;
    }
}
</style>

<?php get_footer(); ?>
