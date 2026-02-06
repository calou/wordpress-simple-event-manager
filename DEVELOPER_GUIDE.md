# Event Manager - Developer Guide

## Working with JSON Event Data

All event data is stored as a single JSON metadata field (`_event_data`). This makes it easy to work with and query.

### Accessing Event Data

```php
<?php
// Get event data
$event_data = event_manager_get_event_data($post_id);

// Access specific fields
$start_date = $event_data['start_date'];
$end_date = $event_data['end_date'];
$registration_deadline = $event_data['registration_deadline'];
$registration_url = $event_data['registration_url'];
$venue_id = $event_data['venue_id'];
$parent_event_id = $event_data['parent_event_id'];
$speaker_ids = $event_data['speaker_ids']; // Array
$organizer_ids = $event_data['organizer_ids']; // Array
?>
```

### Working with Venues

```php
<?php
// Get venue data
$venue_data = event_manager_get_venue_data($venue_id);

if ($venue_data) {
    echo $venue_data['name'];
    echo $venue_data['address'];
    echo $venue_data['city'];
    echo $venue_data['state'];
    echo $venue_data['zip'];
    echo $venue_data['country'];
    echo $venue_data['capacity'];
    echo $venue_data['phone'];
    echo $venue_data['website'];
    echo $venue_data['thumbnail']; // URL to featured image
}
?>
```

## Query Examples

### Display Upcoming Events with Venues

```php
<?php
// Get all pages with event data
$args = array(
    'post_type' => 'page',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => '_is_event_page',
            'value' => '1',
            'compare' => '='
        )
    )
);

$all_events = get_posts($args);
$upcoming_events = array();

foreach ($all_events as $event) {
    $event_data = event_manager_get_event_data($event->ID);
    
    // Filter for upcoming events
    if (!empty($event_data['start_date']) && 
        strtotime($event_data['start_date']) >= time()) {
        $upcoming_events[] = array(
            'post' => $event,
            'data' => $event_data
        );
    }
}

// Sort by start date
usort($upcoming_events, function($a, $b) {
    return strtotime($a['data']['start_date']) - strtotime($b['data']['start_date']);
});

// Display first 5 upcoming events
foreach (array_slice($upcoming_events, 0, 5) as $event) :
    $event_data = $event['data'];
    $venue = event_manager_get_venue_data($event_data['venue_id']);
    ?>
    <div class="event-item">
        <h3><a href="<?php echo get_permalink($event['post']->ID); ?>">
            <?php echo esc_html($event['post']->post_title); ?>
        </a></h3>
        <p>Date: <?php echo event_manager_format_date($event_data['start_date']); ?></p>
        <?php if ($venue) : ?>
            <p>Venue: <?php echo esc_html($venue['name']); ?>, <?php echo esc_html($venue['city']); ?></p>
        <?php endif; ?>
        <?php if ($event_data['registration_url']) : ?>
            <a href="<?php echo esc_url($event_data['registration_url']); ?>" class="register-btn">Register</a>
        <?php endif; ?>
    </div>
    <?php
endforeach;
?>
```

### Display All Speakers with Event Count

```php
<?php
$speakers = get_posts(array(
    'post_type' => 'speaker',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
));

foreach ($speakers as $speaker) :
    // Count events for this speaker
    $event_count = 0;
    $all_events = get_posts(array(
        'post_type' => 'page',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_is_event_page',
                'value' => '1',
                'compare' => '='
            )
        )
    ));
    
    foreach ($all_events as $event) {
        $event_data = event_manager_get_event_data($event->ID);
        if (in_array($speaker->ID, $event_data['speaker_ids'])) {
            $event_count++;
        }
    }
    ?>
    <div class="speaker">
        <?php echo get_the_post_thumbnail($speaker->ID, 'thumbnail'); ?>
        <h4><?php echo esc_html($speaker->post_title); ?></h4>
        <p><?php echo esc_html($speaker->post_excerpt); ?></p>
        <p><?php echo $event_count; ?> upcoming event(s)</p>
    </div>
    <?php
endforeach;
?>
```

### Get Events by Speaker

```php
<?php
function get_events_by_speaker($speaker_id) {
    $all_events = get_posts(array(
        'post_type' => 'page',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_is_event_page',
                'value' => '1',
                'compare' => '='
            )
        )
    ));
    
    $speaker_events = array();
    foreach ($all_events as $event) {
        $event_data = event_manager_get_event_data($event->ID);
        if (in_array($speaker_id, $event_data['speaker_ids'])) {
            $speaker_events[] = $event;
        }
    }
    
    return $speaker_events;
}

// Usage
$events = get_events_by_speaker(123);
foreach ($events as $event) {
    echo '<a href="' . get_permalink($event->ID) . '">' . $event->post_title . '</a>';
}
?>
```

### Get Events by Venue

```php
<?php
function get_events_by_venue($venue_id) {
    $all_events = get_posts(array(
        'post_type' => 'page',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_is_event_page',
                'value' => '1',
                'compare' => '='
            )
        )
    ));
    
    $venue_events = array();
    foreach ($all_events as $event) {
        $event_data = event_manager_get_event_data($event->ID);
        if ($event_data['venue_id'] == $venue_id) {
            $venue_events[] = $event;
        }
    }
    
    return $venue_events;
}

// Usage - display on venue single page
$venue_events = get_events_by_venue(get_the_ID());
if (!empty($venue_events)) {
    echo '<h3>Upcoming Events at this Venue</h3>';
    foreach ($venue_events as $event) {
        $event_data = event_manager_get_event_data($event->ID);
        ?>
        <div class="event-at-venue">
            <h4><a href="<?php echo get_permalink($event->ID); ?>">
                <?php echo esc_html($event->post_title); ?>
            </a></h4>
            <p><?php echo event_manager_format_date($event_data['start_date']); ?></p>
        </div>
        <?php
    }
}
?>
```

### Display Event with All Details

```php
<?php
$event_id = get_the_ID();
$event_data = event_manager_get_event_data($event_id);

// Display dates
if ($event_data['start_date']) {
    echo '<p>Starts: ' . event_manager_format_date($event_data['start_date']) . '</p>';
}

if ($event_data['end_date']) {
    echo '<p>Ends: ' . event_manager_format_date($event_data['end_date']) . '</p>';
}

// Display venue
$venue = event_manager_get_venue_data($event_data['venue_id']);
if ($venue) {
    echo '<div class="event-venue">';
    echo '<h3>' . esc_html($venue['name']) . '</h3>';
    echo '<p>' . esc_html($venue['address']) . '</p>';
    echo '<p>' . esc_html($venue['city']) . ', ' . esc_html($venue['state']) . '</p>';
    if ($venue['capacity']) {
        echo '<p>Capacity: ' . esc_html($venue['capacity']) . '</p>';
    }
    echo '</div>';
}

// Display speakers
if (!empty($event_data['speaker_ids'])) {
    echo '<h3>Speakers:</h3>';
    foreach ($event_data['speaker_ids'] as $speaker_id) {
        $speaker = get_post($speaker_id);
        if ($speaker) {
            echo '<p>' . esc_html($speaker->post_title) . '</p>';
        }
    }
}

// Display organizers
if (!empty($event_data['organizer_ids'])) {
    echo '<h3>Organizers:</h3>';
    foreach ($event_data['organizer_ids'] as $user_id) {
        $user = get_userdata($user_id);
        if ($user) {
            echo '<p>' . esc_html($user->display_name) . ' - ' . esc_html($user->user_email) . '</p>';
        }
    }
}

// Registration button
if ($event_data['registration_url']) {
    echo '<a href="' . esc_url($event_data['registration_url']) . '" class="btn-register">Register Now</a>';
}
?>
```

## REST API Support

Both Speakers and Venues have REST API support enabled. You can access them via:

```
GET /wp-json/wp/v2/speaker
GET /wp-json/wp/v2/speaker/{id}
GET /wp-json/wp/v2/venue
GET /wp-json/wp/v2/venue/{id}
```

### Custom REST Endpoint for Events

You can add a custom REST endpoint to query events:

```php
add_action('rest_api_init', function() {
    register_rest_route('event-manager/v1', '/events', array(
        'methods' => 'GET',
        'callback' => 'get_all_events_json',
        'permission_callback' => '__return_true'
    ));
});

function get_all_events_json($request) {
    $all_events = get_posts(array(
        'post_type' => 'page',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_is_event_page',
                'value' => '1',
                'compare' => '='
            )
        )
    ));
    
    $events_data = array();
    foreach ($all_events as $event) {
        $event_data = event_manager_get_event_data($event->ID);
        $venue_data = event_manager_get_venue_data($event_data['venue_id']);
        
        $events_data[] = array(
            'id' => $event->ID,
            'title' => $event->post_title,
            'url' => get_permalink($event->ID),
            'event_data' => $event_data,
            'venue' => $venue_data
        );
    }
    
    return rest_ensure_response($events_data);
}
```

Access at: `/wp-json/event-manager/v1/events`

## Hooks and Filters

### Available Filters

Customize the date format:

```php
add_filter('event_manager_date_format', 'custom_event_date_format');
function custom_event_date_format($format) {
    return 'd/m/Y H:i'; // European format
}
```

### Custom Meta Box Example

Add additional event fields:

```php
add_action('add_meta_boxes', 'add_custom_event_fields');
function add_custom_event_fields() {
    add_meta_box(
        'event_custom_fields',
        'Additional Event Information',
        'render_custom_event_fields',
        'page',
        'normal',
        'high'
    );
}

function render_custom_event_fields($post) {
    $location = get_post_meta($post->ID, '_event_location_notes', true);
    ?>
    <p>
        <label>Location Notes:</label>
        <textarea name="event_location_notes" style="width: 100%;" rows="3"><?php echo esc_textarea($location); ?></textarea>
    </p>
    <?php
}

add_action('save_post_page', 'save_custom_event_fields');
function save_custom_event_fields($post_id) {
    if (isset($_POST['event_location_notes'])) {
        update_post_meta($post_id, '_event_location_notes', sanitize_textarea_field($_POST['event_location_notes']));
    }
}
```

## Widget Example

Create a widget to display upcoming events:

```php
class Upcoming_Events_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'upcoming_events_widget',
            'Upcoming Events',
            array('description' => 'Display upcoming events')
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        echo $args['before_title'] . 'Upcoming Events' . $args['after_title'];
        
        // Get all events
        $all_events = get_posts(array(
            'post_type' => 'page',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_is_event_page',
                    'value' => '1',
                    'compare' => '='
                )
            )
        ));
        
        $upcoming = array();
        foreach ($all_events as $event) {
            $event_data = event_manager_get_event_data($event->ID);
            if (!empty($event_data['start_date']) && strtotime($event_data['start_date']) >= time()) {
                $upcoming[] = array('post' => $event, 'data' => $event_data);
            }
        }
        
        usort($upcoming, function($a, $b) {
            return strtotime($a['data']['start_date']) - strtotime($b['data']['start_date']);
        });
        
        if (!empty($upcoming)) {
            echo '<ul>';
            foreach (array_slice($upcoming, 0, 5) as $event) {
                echo '<li>';
                echo '<a href="' . get_permalink($event['post']->ID) . '">' . $event['post']->post_title . '</a>';
                echo '<br><small>' . event_manager_format_date($event['data']['start_date'], 'M j, Y') . '</small>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No upcoming events</p>';
        }
        
        echo $args['after_widget'];
    }
}

// Register widget
add_action('widgets_init', function() {
    register_widget('Upcoming_Events_Widget');
});
```

## Shortcode Examples

### Events List Shortcode

```php
function event_manager_list_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 10,
        'speaker' => '',
        'venue' => '',
    ), $atts);
    
    $all_events = get_posts(array(
        'post_type' => 'page',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_is_event_page',
                'value' => '1',
                'compare' => '='
            )
        )
    ));
    
    $filtered_events = array();
    foreach ($all_events as $event) {
        $event_data = event_manager_get_event_data($event->ID);
        
        // Filter by speaker
        if ($atts['speaker'] && !in_array($atts['speaker'], $event_data['speaker_ids'])) {
            continue;
        }
        
        // Filter by venue
        if ($atts['venue'] && $event_data['venue_id'] != $atts['venue']) {
            continue;
        }
        
        // Only upcoming
        if (!empty($event_data['start_date']) && strtotime($event_data['start_date']) >= time()) {
            $filtered_events[] = array('post' => $event, 'data' => $event_data);
        }
    }
    
    usort($filtered_events, function($a, $b) {
        return strtotime($a['data']['start_date']) - strtotime($b['data']['start_date']);
    });
    
    ob_start();
    foreach (array_slice($filtered_events, 0, $atts['limit']) as $event) :
        $venue = event_manager_get_venue_data($event['data']['venue_id']);
        ?>
        <div class="event-shortcode-item">
            <h3><a href="<?php echo get_permalink($event['post']->ID); ?>">
                <?php echo esc_html($event['post']->post_title); ?>
            </a></h3>
            <p class="event-date"><?php echo event_manager_format_date($event['data']['start_date']); ?></p>
            <?php if ($venue) : ?>
                <p class="event-venue"><?php echo esc_html($venue['name']); ?></p>
            <?php endif; ?>
        </div>
        <?php
    endforeach;
    
    return ob_get_clean();
}
add_shortcode('events_list', 'event_manager_list_shortcode');

// Usage: [events_list limit="5" speaker="123" venue="456"]
```

## Performance Tips

1. **Caching**: For frequently accessed event lists, use transients:

```php
function get_cached_upcoming_events() {
    $cache_key = 'upcoming_events_list';
    $events = get_transient($cache_key);
    
    if (false === $events) {
        // Query and process events
        $events = /* your event query code */;
        set_transient($cache_key, $events, HOUR_IN_SECONDS);
    }
    
    return $events;
}
```

2. **Limit Queries**: When possible, limit the number of posts queried and process them in PHP rather than making multiple database queries.

3. **Indexing**: The `_is_event_page` meta field makes it faster to query only event pages without parsing JSON for every page.

## Migration from Old Format

If you had event data in the old format (separate meta fields), here's a migration script:

```php
function migrate_event_data_to_json() {
    $pages = get_posts(array(
        'post_type' => 'page',
        'posts_per_page' => -1,
        'meta_key' => '_event_start_date'
    ));
    
    foreach ($pages as $page) {
        $event_data = array(
            'start_date' => get_post_meta($page->ID, '_event_start_date', true),
            'end_date' => get_post_meta($page->ID, '_event_end_date', true),
            'registration_deadline' => get_post_meta($page->ID, '_event_registration_deadline', true),
            'registration_url' => '',
            'venue_id' => '',
            'parent_event_id' => get_post_meta($page->ID, '_event_parent', true),
            'speaker_ids' => get_post_meta($page->ID, '_event_speakers', true) ?: array(),
            'organizer_ids' => get_post_meta($page->ID, '_event_organizers', true) ?: array(),
        );
        
        update_post_meta($page->ID, '_event_data', wp_json_encode($event_data));
        update_post_meta($page->ID, '_is_event_page', '1');
        
        // Optionally delete old meta
        // delete_post_meta($page->ID, '_event_start_date');
        // etc...
    }
    
    return count($pages) . ' events migrated';
}
```
