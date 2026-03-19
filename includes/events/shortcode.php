<?php
/**
 * Event Shortcodes – loader
 *
 * @package Event_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

$shortcodes_dir = __DIR__ . '/shortcodes/';

require_once $shortcodes_dir . 'common.php';
require_once $shortcodes_dir . 'event-metadata.php';
require_once $shortcodes_dir . 'event-sidebar.php';
require_once $shortcodes_dir . 'event-speakers.php';
require_once $shortcodes_dir . 'event-organizers.php';
require_once $shortcodes_dir . 'event-programme.php';
require_once $shortcodes_dir . 'events-list.php';
require_once $shortcodes_dir . 'events-calendar.php';
