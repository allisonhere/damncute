<?php
/**
 * Title: Pet of the Day
 * Slug: damncute/pet-of-day
 * Categories: damncute
 */
?>
<!-- wp:group {"align":"wide","className":"dc-section dc-feature"} -->
<div class="wp-block-group alignwide dc-section dc-feature">
    <!-- wp:heading {"level":2} -->
    <h2>Pet of the Day</h2>
    <!-- /wp:heading -->

    <!-- wp:query {"query":{"perPage":1,"postType":"pets","order":"desc","orderBy":"rand"},"displayLayout":{"type":"flex","columns":1},"className":"dc-query"} -->
    <div class="wp-block-query dc-query">
        <!-- wp:post-template -->
        <!-- wp:group {"className":"dc-card dc-card--feature"} -->
        <div class="wp-block-group dc-card dc-card--feature">
            <!-- wp:post-featured-image {"isLink":true,"sizeSlug":"large","className":"dc-card__media"} /-->
            <!-- wp:group {"className":"dc-card__body"} -->
            <div class="wp-block-group dc-card__body">
                <!-- wp:post-title {"isLink":true,"className":"dc-card__title"} /-->
                <!-- wp:post-excerpt {"className":"dc-card__excerpt"} /-->
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
