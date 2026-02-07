<?php
/**
 * Event Admin Menu
 *
 * @package Event_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'event_manager_admin_menu');

/**
 * Register the Events admin menu
 */
function event_manager_admin_menu() {
    add_menu_page(
        __('Events', 'event-manager'),
        __('Events', 'event-manager'),
        'edit_pages',
        'event-manager',
        'event_manager_events_list_page',
        'dashicons-calendar',
        26
    );

    add_submenu_page(
        'event-manager',
        __('Events', 'event-manager'),
        __('Events', 'event-manager'),
        'edit_pages',
        'event-manager',
        'event_manager_events_list_page'
    );

    $hook = add_submenu_page(
        'event-manager',
        __('Add New Event', 'event-manager'),
        __('Add New Event', 'event-manager'),
        'edit_pages',
        'event-manager-new',
        '__return_false'
    );

    add_action('load-' . $hook, 'event_manager_handle_new_event');
}

/**
 * Handle "Add New" event: create an auto-draft page with the Event template and redirect to the editor
 */
function event_manager_handle_new_event() {
    if (!current_user_can('edit_pages')) {
        wp_die(__('You do not have permission to create events.', 'event-manager'));
    }

    $post_id = wp_insert_post(array(
        'post_type' => 'page',
        'post_status' => 'auto-draft',
        'post_title' => '',
    ));

    if ($post_id && !is_wp_error($post_id)) {
        update_post_meta($post_id, '_wp_page_template', EVENT_MANAGER_EVENT_TEMPLATE);
        wp_safe_redirect(admin_url('post.php?post=' . $post_id . '&action=edit'));
        exit;
    }

    wp_safe_redirect(admin_url('admin.php?page=event-manager'));
    exit;
}

/**
 * Render the "All Events" admin page
 */
function event_manager_events_list_page() {
    $events = get_posts(array(
        'post_type' => 'page',
        'posts_per_page' => -1,
        'post_status' => array('publish', 'draft', 'pending'),
        'meta_query' => event_manager_event_page_meta_query(),
        'orderby' => 'date',
        'order' => 'DESC',
    ));

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e('Events', 'event-manager'); ?></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=event-manager-new')); ?>" class="page-title-action"><?php _e('Add New', 'event-manager'); ?></a>
        <hr class="wp-header-end">

        <?php if (empty($events)) : ?>
            <p><?php _e('No events found.', 'event-manager'); ?></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Title', 'event-manager'); ?></th>
                        <th><?php _e('Date', 'event-manager'); ?></th>
                        <th><?php _e('Venue', 'event-manager'); ?></th>
                        <th><?php _e('Status', 'event-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event) :
                        $event_data = event_manager_get_event_data($event->ID);
                        $venue_name = '';
                        if (!empty($event_data['venue_id'])) {
                            $venue_post = get_post($event_data['venue_id']);
                            if ($venue_post) {
                                $venue_name = $venue_post->post_title;
                            }
                        }
                        $date_range = event_manager_event_format_date_range($event_data['start_date'], $event_data['end_date']);
                        $status_obj = get_post_status_object($event->post_status);
                        ?>
                        <tr>
                            <td>
                                <strong>
                                    <a href="<?php echo esc_url(get_edit_post_link($event->ID)); ?>" class="row-title">
                                        <?php echo esc_html($event->post_title ?: __('(no title)', 'event-manager')); ?>
                                    </a>
                                </strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo esc_url(get_edit_post_link($event->ID)); ?>"><?php _e('Edit', 'event-manager'); ?></a> |
                                    </span>
                                    <span class="view">
                                        <a href="<?php echo esc_url(get_permalink($event->ID)); ?>"><?php _e('View', 'event-manager'); ?></a>
                                    </span>
                                </div>
                            </td>
                            <td><?php echo esc_html($date_range ?: '—'); ?></td>
                            <td><?php echo esc_html($venue_name ?: '—'); ?></td>
                            <td><?php echo esc_html($status_obj ? $status_obj->label : $event->post_status); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
