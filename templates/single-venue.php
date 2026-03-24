<?php
/**
 * Template for displaying a single venue.
 *
 * @package Event_Manager
 */

get_header();
?>

<div class="venue-page">
    <?php while (have_posts()) : the_post(); ?>
        <div class="venue-page-columns">
            <div class="venue-page-main">
                <?php echo do_shortcode('[venue_metadata]'); ?>
                <div class="venue-description">
                    <?php the_content(); ?>
                </div>
            </div>
            <div class="venue-page-sidebar">
                <?php echo do_shortcode('[venue_contacts]'); ?>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php get_footer(); ?>
