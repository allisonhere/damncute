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
        <!-- wp:shortcode -->
        [damncute_current_pet_card]
        <!-- /wp:shortcode -->
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
