<?php
/**
 * Title: Pet Card
 * Slug: damncute/card-pet-v2
 * Categories: damncute
 * Inserter: false
 */
?>
<!-- wp:group {"className":"dc-card"} -->
<div class="wp-block-group dc-card">
    <?php die('PHP IS RUNNING'); ?>
    <!-- wp:post-featured-image {"isLink":true,"sizeSlug":"large","className":"dc-card__media"} /-->
    
    <!-- wp:group {"className":"dc-card__body","style":{"spacing":{"blockGap":"0"}}} -->
    <div class="wp-block-group dc-card__body">
        <div class="dc-card-header">
            <?php 
            // Manual render because shortcode blocks can be flaky in nested FSE templates
            if (function_exists('damncute_card_meta_shortcode')) {
                echo damncute_card_meta_shortcode();
            }
            ?>
        </div>
        <!-- wp:post-title {"isLink":true,"className":"dc-card__title"} /-->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->
