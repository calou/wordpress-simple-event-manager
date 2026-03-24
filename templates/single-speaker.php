<?php
/**
 * Template for displaying a single speaker.
 *
 * @package Event_Manager
 */

get_header();
?>

<div class="container speaker-page">
    <?php while (have_posts()) : the_post(); ?>
        <?php echo do_shortcode('[speaker_metadata]'); ?>
        <div class="speaker-bio">
            <?php the_content(); ?>
        </div>
    <?php endwhile; ?>
</div>

<?php get_footer(); ?>
