<?php
/**
 * Template Name: Event
 * Template Post Type: page
 *
 * @package Event_Manager
 */

get_header();
?>

<div class="event-page">
    <?php while (have_posts()) : the_post(); ?>

        <h1 class="event-title"><?php the_title(); ?></h1>

        <?php echo do_shortcode('[event_metadata]'); ?>

        <div class="event-page-columns">
            <div class="event-page-main">
                <?php the_content(); ?>
                <?php echo do_shortcode('[event_speakers]'); ?>
                <?php echo do_shortcode('[event_organizers]'); ?>
                <?php echo do_shortcode('[event_programme]'); ?>
            </div>
            <div class="event-page-sidebar">
                <?php echo do_shortcode('[event_sidebar]'); ?>
            </div>
        </div>

    <?php endwhile; ?>
</div>

<?php get_footer(); ?>
