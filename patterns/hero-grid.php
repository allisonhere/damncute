<?php
/**
 * Title: Hero Grid
 * Slug: damncute/hero-grid
 * Categories: damncute
 */
?>
<!-- wp:group {"align":"wide","className":"dc-section dc-hero"} -->
<div class="wp-block-group alignwide dc-section dc-hero">
    <!-- wp:group {"layout":{"type":"constrained"}} -->
    <div class="wp-block-group">
        <!-- wp:heading {"level":2,"className":"dc-hero__title"} -->
        <h3 class="dc-hero__title">The internet’s cutest pets.<span class="dc-hero__break">Zero fluff.</span></h3>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"className":"dc-hero__lede"} -->
        <p class="dc-hero__lede">A visual feed of pure serotonin. Scroll. Smile. Share.</p>
        <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->

    <!-- wp:query {"query":{"perPage":6,"postType":"pets","order":"desc","orderBy":"date"},"displayLayout":{"type":"flex","columns":3},"className":"dc-query"} -->
    <div class="wp-block-query dc-query">
        <!-- wp:post-template {"className":"dc-grid dc-grid--hero"} -->
        <!-- wp:group {"className":"dc-card"} -->
        <div class="wp-block-group dc-card">
            <!-- wp:post-featured-image {"isLink":true,"sizeSlug":"large","className":"dc-card__media"} /-->
            <!-- wp:group {"className":"dc-card__body"} -->
            <div class="wp-block-group dc-card__body">
                <!-- wp:post-title {"isLink":true,"className":"dc-card__title"} /-->
                <!-- wp:post-terms {"taxonomy":"species","className":"dc-card__meta"} /-->
                <!-- wp:post-terms {"taxonomy":"vibe","className":"dc-card__meta"} /-->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:group -->
        <!-- /wp:post-template -->
    </div>
    <!-- /wp:query -->
</div>
<!-- /wp:group -->
