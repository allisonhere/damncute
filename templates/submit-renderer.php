<?php
/**
 * Template Name: Submit Page (Custom)
 * Description: Renders the block template manually for the custom route.
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    
    <!-- LOAD THEME CSS MANUALLY AFTER WP_HEAD -->
    <link rel="stylesheet" href="<?php echo get_theme_file_uri('assets/css/theme.css'); ?>?ver=<?php echo time(); ?>">
    
    <style>
      /* Ensure body background inherits from theme.css */
      body { background-color: var(--dc-base); color: var(--dc-ink); }
    </style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="wp-site-blocks">

<!-- HARDCODED HEADER TO BYPASS BLOCK PARSING ISSUES -->
<header class="dc-header">
    <div class="dc-logo">
        <a href="<?php echo home_url('/'); ?>"><?php bloginfo('name'); ?></a>
    </div>
    
    <nav class="dc-nav">
        <ul class="wp-block-navigation__container" style="display: flex; list-style: none; padding: 0;">
            <li style="margin: 0 1rem;"><a href="<?php echo home_url('/'); ?>">Home</a></li>
            <li style="margin: 0 1rem;"><a href="<?php echo home_url('/?dc_route=submit'); ?>">Submit Your Pet</a></li>
        </ul>
    </nav>
</header>

<div class="wp-block-group alignwide dc-section dc-submit-page">
    <h1>Submit Your Pet</h1>
    <p>Keep it joyful, respectful, and consent-first. We review every submission.</p>
    
    <div class="dc-form-wrapper">
        <?php echo do_shortcode('[forminator_form id="39"]'); ?>
    </div>
</div>

<?php 
// Manually load and render footer content
$footer_content = file_get_contents(get_theme_file_path('parts/footer.html'));
echo do_shortcode(do_blocks($footer_content)); 
?>

</div>

<?php wp_footer(); ?>
</body>
</html>