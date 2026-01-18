<?php
/**
 * Title: Hero Grid
 * Slug: damncute/hero-grid
 * Categories: damncute
 */
?>
<!-- wp:group {"align":"wide","className":"dc-section dc-hero dc-hero--news"} -->
<div class="wp-block-group alignwide dc-section dc-hero dc-hero--news">
    <!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center","alignItems":"center"}} -->
    <div class="wp-block-group" style="text-align:center;">
        <!-- wp:heading {"textAlign":"center","level":2,"className":"dc-hero__title"} -->
        <h2 class="wp-block-heading has-text-align-center dc-hero__title" style="text-align:center;">The internet’s cutest pets.<span class="dc-hero__break">Zero fluff.</span></h2>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"align":"center","className":"dc-hero__lede"} -->
        <p class="has-text-align-center dc-hero__lede" style="text-align:center;">A visual feed of pure serotonin. Scroll. Smile. Share.</p>
        <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->

    <!-- wp:spacer {"height":"var:preset|spacing|m"} -->
    <div style="height:var(--wp--preset--spacing--m)" aria-hidden="true" class="wp-block-spacer"></div>
    <!-- /wp:spacer -->

    <!-- wp:group {"style":{"spacing":{"margin":{"top":"3rem"}}}} -->
    <div class="wp-block-group" style="margin-top:3rem;">
        <?php
        if (function_exists('damncute_pet_of_day_shortcode')) {
            echo damncute_pet_of_day_shortcode();
        }
        ?>
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->

<?php echo do_shortcode('[damncute_vibe_check]'); ?>

