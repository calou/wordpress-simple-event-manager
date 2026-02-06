<?php
/**
 * Speaker Post Type Class
 *
 * @package Event_Manager
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Speaker Post Type Handler
 */
class Event_Manager_Speaker {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_speaker_post_type'));
    }

    /**
     * Register Speaker Custom Post Type
     */
    public function register_speaker_post_type() {
        $labels = array(
            'name'                  => _x('Speakers', 'Post Type General Name', 'event-manager'),
            'singular_name'         => _x('Speaker', 'Post Type Singular Name', 'event-manager'),
            'menu_name'             => __('Speakers', 'event-manager'),
            'name_admin_bar'        => __('Speaker', 'event-manager'),
            'archives'              => __('Speaker Archives', 'event-manager'),
            'attributes'            => __('Speaker Attributes', 'event-manager'),
            'parent_item_colon'     => __('Parent Speaker:', 'event-manager'),
            'all_items'             => __('All Speakers', 'event-manager'),
            'add_new_item'          => __('Add New Speaker', 'event-manager'),
            'add_new'               => __('Add New', 'event-manager'),
            'new_item'              => __('New Speaker', 'event-manager'),
            'edit_item'             => __('Edit Speaker', 'event-manager'),
            'update_item'           => __('Update Speaker', 'event-manager'),
            'view_item'             => __('View Speaker', 'event-manager'),
            'view_items'            => __('View Speakers', 'event-manager'),
            'search_items'          => __('Search Speaker', 'event-manager'),
            'not_found'             => __('Not found', 'event-manager'),
            'not_found_in_trash'    => __('Not found in Trash', 'event-manager'),
        );

        $args = array(
            'label'                 => __('Speaker', 'event-manager'),
            'description'           => __('Event speakers', 'event-manager'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 20,
            'menu_icon'             => 'dashicons-businessperson',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );

        register_post_type('speaker', $args);
    }
}
