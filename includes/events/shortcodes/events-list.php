<?php
/**
 * Shortcodes: [events_upcoming] and [events_past]
 * Paginated lists of parent events filtered by time.
 *
 * Attributes (both shortcodes):
 *   per_page  – items per page (default: 10)
 *   category  – category slug or ID to filter by (optional)
 *
 * @package Event_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('events_upcoming', 'event_manager_events_upcoming_render');
add_shortcode('events_past', 'event_manager_events_past_render');

/**
 * Shared helper: fetch, filter, sort and paginate events.
 *
 * @param string $mode  'upcoming' or 'past'
 * @param array  $atts  Raw shortcode attributes
 * @return string       HTML output
 */
function event_manager_events_list_render($mode, $atts)
{
    $atts = shortcode_atts(array(
        'per_page' => 10,
        'category' => '',
    ), $atts, 'events_' . $mode);

    $per_page = max(1, intval($atts['per_page']));
    $now      = current_time('timestamp');

    $query_args = array(
        'post_type'      => 'page',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => event_manager_event_page_meta_query(),
    );

    if (!empty($atts['category'])) {
        $cat = sanitize_text_field($atts['category']);
        if (is_numeric($cat)) {
            $query_args['cat'] = intval($cat);
        } else {
            $query_args['category_name'] = $cat;
        }
    }

    $all_posts = get_posts($query_args);

    $filtered = array();
    foreach ($all_posts as $post) {
        $data = event_manager_get_event_data($post->ID);
        if (empty($data['start_date'])) {
            continue;
        }
        $start_ts = strtotime($data['start_date']);
        $end_ts   = !empty($data['end_date']) ? strtotime($data['end_date']) : $start_ts;

        if ($mode === 'upcoming' && $end_ts >= $now) {
            $filtered[] = array('post' => $post, 'data' => $data, 'start_ts' => $start_ts);
        } elseif ($mode === 'past' && $end_ts < $now) {
            $filtered[] = array('post' => $post, 'data' => $data, 'start_ts' => $start_ts);
        }
    }

    if ($mode === 'upcoming') {
        usort($filtered, fn ($a, $b) => $a['start_ts'] - $b['start_ts']);
    } else {
        usort($filtered, fn ($a, $b) => $b['start_ts'] - $a['start_ts']);
    }

    $total        = count($filtered);
    $total_pages  = max(1, (int) ceil($total / $per_page));
    $paged_key    = 'em_page_' . $mode;
    $current_page = isset($_GET[$paged_key]) ? min($total_pages, max(1, intval($_GET[$paged_key]))) : 1;
    $page_items   = array_slice($filtered, ($current_page - 1) * $per_page, $per_page);

    ob_start();

    if (empty($filtered)) {
        echo '<p>' . esc_html($mode === 'upcoming'
            ? __('No upcoming events.', 'event-manager')
            : __('No past events.', 'event-manager')) . '</p>';
        return ob_get_clean();
    }
    ?>
    <div class="event-list">
        <?php foreach ($page_items as $item) :
            $post       = $item['post'];
            $data       = $item['data'];
            $date_str   = event_manager_events_list_format_date_range($data['start_date'], $data['end_date']);
            $venue_name = '';
            if (!empty($data['venue_id'])) {
                $venue_post = get_post($data['venue_id']);
                if ($venue_post) {
                    $venue_name = $venue_post->post_title;
                }
            }
            $terms = get_the_terms($post->ID, 'category');
            ?>
            <div class="event-list-item">
                <div class="event-list-item-date"><?php echo esc_html($date_str ?: '—'); ?></div>
                <div class="event-list-item-body">
                    <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="event-list-item-title">
                        <?php echo esc_html($post->post_title ?: __('(no title)', 'event-manager')); ?>
                    </a>
                    <?php if ($venue_name) : ?>
                        <div class="event-list-item-venue">
                            <i class="fas fa-location-dot"></i>
                            <?php echo esc_html($venue_name); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($terms && !is_wp_error($terms)) : ?>
                        <div class="event-list-item-cats">
                            <?php foreach ($terms as $term) : ?>
                                <span class="event-list-item-cat"><?php echo esc_html($term->name); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1) :
        $base_url = remove_query_arg($paged_key);
        ?>
        <nav class="event-list-pagination">
            <?php if ($current_page > 1) : ?>
                <a href="<?php echo esc_url(add_query_arg($paged_key, $current_page - 1, $base_url)); ?>" class="event-list-pagination-prev">
                    <i class="fas fa-chevron-left"></i> <?php _e('Previous', 'event-manager'); ?>
                </a>
            <?php else : ?>
                <span></span>
            <?php endif; ?>
            <span class="event-list-pagination-info">
                <?php printf(__('Page %d of %d', 'event-manager'), $current_page, $total_pages); ?>
            </span>
            <?php if ($current_page < $total_pages) : ?>
                <a href="<?php echo esc_url(add_query_arg($paged_key, $current_page + 1, $base_url)); ?>" class="event-list-pagination-next">
                    <?php _e('Next', 'event-manager'); ?> <i class="fas fa-chevron-right"></i>
                </a>
            <?php else : ?>
                <span></span>
            <?php endif; ?>
        </nav>
    <?php endif;

    return ob_get_clean();
}

/**
 * [events_upcoming per_page="10" category="slug"]
 * Displays a paginated list of upcoming events, sorted by start date ascending.
 */
function event_manager_events_upcoming_render($atts)
{
    return event_manager_events_list_render('upcoming', $atts);
}

/**
 * [events_past per_page="10" category="slug"]
 * Displays a paginated list of past events, sorted by start date descending.
 */
function event_manager_events_past_render($atts)
{
    return event_manager_events_list_render('past', $atts);
}
