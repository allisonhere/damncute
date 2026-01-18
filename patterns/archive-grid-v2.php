<?php
/**
 * Title: Archive Grid V2
 * Slug: damncute/archive-grid-v2
 * Categories: damncute
 * Inserter: false
 */
?>
<!-- wp:query {"query":{"perPage":12,"postType":"pets","order":"desc","orderBy":"date"},"displayLayout":{"type":"flex","columns":3},"className":"dc-query"} -->
<div class="wp-block-query dc-query">
    <!-- wp:post-template {"className":"dc-grid"} -->
    <!-- wp:group {"className":"dc-card"} -->
    <div class="wp-block-group dc-card">
        <!-- wp:post-featured-image {"isLink":true,"sizeSlug":"large","className":"dc-card__media"} /-->
        
        <!-- wp:group {"className":"dc-card__body","style":{"spacing":{"blockGap":"0"}}} -->
        <div class="wp-block-group dc-card__body">
            <div class="dc-card-header">
                <!-- wp:post-terms {"taxonomy":"vibe","className":"dc-card-vibe"} /-->
                <!-- wp:post-meta {"key":"reaction_total","className":"dc-card-hearts"} /-->
            </div>
            <!-- wp:post-title {"isLink":true,"className":"dc-card__title"} /-->
        </div>
        <!-- /wp:group -->
    </div>
    <!-- /wp:group -->
    <!-- /wp:post-template -->

    <!-- Infinite Scroll Loader (Skeletons) -->
    <div class="dc-loader">
        <div class="dc-skeleton-card"><div class="dc-skeleton-media"></div><div class="dc-skeleton-text"><div class="dc-skeleton-line"></div><div class="dc-skeleton-line dc-skeleton-line--short"></div></div></div>
        <div class="dc-skeleton-card"><div class="dc-skeleton-media"></div><div class="dc-skeleton-text"><div class="dc-skeleton-line"></div><div class="dc-skeleton-line dc-skeleton-line--short"></div></div></div>
        <div class="dc-skeleton-card"><div class="dc-skeleton-media"></div><div class="dc-skeleton-text"><div class="dc-skeleton-line"></div><div class="dc-skeleton-line dc-skeleton-line--short"></div></div></div>
    </div>

    <!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"},"className":"dc-pagination"} -->
    <!-- wp:query-pagination-previous /-->
    <!-- wp:query-pagination-numbers /-->
    <!-- wp:query-pagination-next /-->
    <!-- /wp:query-pagination -->
</div>
<!-- /wp:query -->
