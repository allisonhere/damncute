<?php
/**
 * Title: Trending Cute
 * Slug: damncute/trending-cute
 * Categories: damncute
 */
?>
<!-- wp:group {"align":"wide","className":"dc-section dc-section--trending"} -->
<div class="wp-block-group alignwide dc-section dc-section--trending">
    <!-- wp:heading {"level":2} -->
    <h2>Trending Cute</h2>
    <!-- /wp:heading -->

    <!-- wp:shortcode -->
    [damncute_pet_grid count="8" compact="true" pagination="false"]
    <!-- /wp:shortcode -->

    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|m"}}}} -->
    <div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--m)">
        <!-- wp:button {"className":"is-style-outline"} -->
        <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/pets">See all trending</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->
</div>
<!-- /wp:group -->
