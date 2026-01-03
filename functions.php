<?php

declare(strict_types=1);

if (!function_exists('damncute_setup')) {
    function damncute_setup(): void
    {
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('align-wide');
        add_theme_support('responsive-embeds');
        add_theme_support('editor-styles');
        add_theme_support('wp-block-styles');

        register_nav_menus([
            'primary' => __('Primary Navigation', 'damncute'),
        ]);
    }
}
add_action('after_setup_theme', 'damncute_setup');

if (!function_exists('damncute_assets')) {
    function damncute_assets(): void
    {
        $theme_version = wp_get_theme()->get('Version');
        $css_path = get_theme_file_path('assets/css/theme.css');
        $js_path = get_theme_file_path('assets/js/theme.js');

        wp_enqueue_style(
            'damncute-theme',
            get_theme_file_uri('assets/css/theme.css'),
            [],
            file_exists($css_path) ? (string) filemtime($css_path) : $theme_version
        );

        wp_enqueue_script(
            'damncute-theme',
            get_theme_file_uri('assets/js/theme.js'),
            [],
            file_exists($js_path) ? (string) filemtime($js_path) : $theme_version,
            true
        );
        wp_script_add_data('damncute-theme', 'defer', true);
    }
}
add_action('wp_enqueue_scripts', 'damncute_assets');

if (!function_exists('damncute_register_content_types')) {
    function damncute_register_content_types(): void
    {
        register_post_type('pets', [
            'labels' => [
                'name' => __('Pets', 'damncute'),
                'singular_name' => __('Pet', 'damncute'),
                'add_new_item' => __('Add New Pet', 'damncute'),
                'edit_item' => __('Edit Pet', 'damncute'),
                'view_item' => __('View Pet', 'damncute'),
                'search_items' => __('Search Pets', 'damncute'),
            ],
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-pets',
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions'],
            'has_archive' => true,
            'rewrite' => ['slug' => 'pets'],
        ]);

        register_taxonomy('species', ['pets'], [
            'labels' => [
                'name' => __('Species', 'damncute'),
                'singular_name' => __('Species', 'damncute'),
            ],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'species'],
        ]);

        register_taxonomy('breed', ['pets'], [
            'labels' => [
                'name' => __('Breed', 'damncute'),
                'singular_name' => __('Breed', 'damncute'),
            ],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'breed'],
        ]);

        register_taxonomy('vibe', ['pets'], [
            'labels' => [
                'name' => __('Vibe', 'damncute'),
                'singular_name' => __('Vibe', 'damncute'),
            ],
            'public' => true,
            'hierarchical' => false,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'vibe'],
        ]);
    }
}
add_action('init', 'damncute_register_content_types');

if (!function_exists('damncute_register_meta')) {
    function damncute_register_meta(): void
    {
        $auth_callback = static function (): bool {
            return current_user_can('edit_posts');
        };

        $meta_fields = [
            'pet_name' => 'string',
            'breed' => 'string',
            'age' => 'string',
            'owner_social' => 'string',
            'adoption_status' => 'string',
            'cute_description' => 'string',
            'vote_count' => 'integer',
        ];

        foreach ($meta_fields as $key => $type) {
            $args = [
                'type' => $type,
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => $type === 'integer' ? 'absint' : 'sanitize_text_field',
                'auth_callback' => $auth_callback,
            ];
            if ($key === 'vote_count') {
                $args['default'] = 0;
            }
            register_post_meta('pets', $key, $args);
        }

        register_post_meta('pets', 'gallery', [
            'type' => 'array',
            'single' => true,
            'show_in_rest' => [
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'integer',
                    ],
                ],
            ],
            'auth_callback' => $auth_callback,
        ]);
    }
}
add_action('init', 'damncute_register_meta');

if (!function_exists('damncute_register_pattern_category')) {
    function damncute_register_pattern_category(): void
    {
        if (function_exists('register_block_pattern_category')) {
            register_block_pattern_category('damncute', [
                'label' => __('Damn Cute', 'damncute'),
            ]);
        }
    }
}
add_action('init', 'damncute_register_pattern_category');

if (!function_exists('damncute_term_filters_shortcode')) {
    function damncute_term_filters_shortcode(): string
    {
        $taxonomies = [
            'species' => __('Species', 'damncute'),
            'vibe' => __('Vibe', 'damncute'),
        ];

        $html = '<div class="dc-filters">';
        foreach ($taxonomies as $taxonomy => $label) {
            $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
            if (empty($terms) || is_wp_error($terms)) {
                continue;
            }
            $html .= sprintf('<span class="dc-filters__label">%s</span>', esc_html($label));
            foreach ($terms as $term) {
                $html .= sprintf(
                    '<a class="dc-chip" href="%s">%s</a>',
                    esc_url(get_term_link($term)),
                    esc_html($term->name)
                );
            }
        }
        $html .= '</div>';

        return $html;
    }
}
add_shortcode('damncute_term_filters', 'damncute_term_filters_shortcode');

if (!function_exists('damncute_pet_meta_shortcode')) {
    function damncute_pet_meta_shortcode(): string
    {
        if (!is_singular('pets')) {
            return '';
        }

        $post_id = get_the_ID();
        $meta_keys = [
            'age' => __('Age', 'damncute'),
            'owner_social' => __('Owner', 'damncute'),
            'adoption_status' => __('Adoption', 'damncute'),
        ];

        $items = '';
        foreach ($meta_keys as $key => $label) {
            $value = get_post_meta($post_id, $key, true);
            if ($value === '') {
                continue;
            }
            $items .= sprintf(
                '<div class="dc-meta__item"><span class="dc-meta__label">%s</span><span class="dc-meta__value">%s</span></div>',
                esc_html($label),
                esc_html($value)
            );
        }

        if ($items === '') {
            return '';
        }

        return '<div class="dc-meta">' . $items . '</div>';
    }
}
add_shortcode('damncute_pet_meta', 'damncute_pet_meta_shortcode');

if (!function_exists('damncute_pet_gallery_shortcode')) {
    function damncute_pet_gallery_shortcode(): string
    {
        if (!is_singular('pets')) {
            return '';
        }

        $gallery = get_post_meta(get_the_ID(), 'gallery', true);
        if (empty($gallery) || !is_array($gallery)) {
            return '';
        }

        $items = '';
        foreach ($gallery as $attachment_id) {
            $mime = get_post_mime_type($attachment_id);
            if (strpos((string) $mime, 'image/') === 0) {
                $items .= wp_get_attachment_image($attachment_id, 'large', false, ['class' => 'dc-gallery__image']);
            } elseif (strpos((string) $mime, 'video/') === 0) {
                $url = wp_get_attachment_url($attachment_id);
                if ($url) {
                    $items .= sprintf(
                        '<video class="dc-gallery__video" controls playsinline preload="metadata" src="%s"></video>',
                        esc_url($url)
                    );
                }
            }
        }

        if ($items === '') {
            return '';
        }

        return '<div class="dc-gallery">' . $items . '</div>';
    }
}
add_shortcode('damncute_pet_gallery', 'damncute_pet_gallery_shortcode');

if (!function_exists('damncute_submit_cta_shortcode')) {
    function damncute_submit_cta_shortcode(array $atts = []): string
    {
        $atts = shortcode_atts([
            'variant' => 'button',
            'label' => __('Submit Your Pet', 'damncute'),
        ], $atts, 'damncute_submit_cta');

        $url = home_url('/?dc_route=submit');
        $label = esc_html($atts['label']);

        if ($atts['variant'] === 'floating') {
            return sprintf('<a class="dc-floating-cta" href="%s">%s</a>', esc_url($url), $label);
        }

        return sprintf(
            '<a class="wp-element-button wp-block-button__link dc-cta__button" href="%s">%s</a>',
            esc_url($url),
            $label
        );
    }
}
add_shortcode('damncute_submit_cta', 'damncute_submit_cta_shortcode');

if (!function_exists('damncute_handle_pet_submission')) {
    function damncute_handle_pet_submission(): void
    {
        if (!isset($_POST['damncute_nonce']) || !wp_verify_nonce($_POST['damncute_nonce'], 'damncute_submit_pet')) {
            wp_die(__('Invalid submission. Please try again.', 'damncute'));
        }

        if (!empty($_POST['dc_hp'])) {
            wp_die(__('Submission rejected.', 'damncute'));
        }

        $submitted_at = isset($_POST['dc_time']) ? (int) $_POST['dc_time'] : 0;
        if ($submitted_at > 0 && (time() - $submitted_at) < 3) {
            wp_die(__('Please take a moment before submitting.', 'damncute'));
        }

        $pet_name = sanitize_text_field(wp_unslash($_POST['pet_name'] ?? ''));
        $cute_description = sanitize_textarea_field(wp_unslash($_POST['cute_description'] ?? ''));

        if ($pet_name === '' || $cute_description === '') {
            wp_die(__('Missing required fields.', 'damncute'));
        }

        $post_id = wp_insert_post([
            'post_type' => 'pets',
            'post_title' => $pet_name,
            'post_content' => $cute_description,
            'post_status' => 'pending',
        ], true);

        if (is_wp_error($post_id)) {
            wp_die(__('Unable to save submission.', 'damncute'));
        }

        $meta_map = [
            'pet_name' => $pet_name,
            'cute_description' => $cute_description,
            'age' => sanitize_text_field(wp_unslash($_POST['age'] ?? '')),
            'owner_social' => sanitize_text_field(wp_unslash($_POST['owner_social'] ?? '')),
            'adoption_status' => sanitize_text_field(wp_unslash($_POST['adoption_status'] ?? '')),
        ];

        foreach ($meta_map as $key => $value) {
            if ($value !== '') {
                update_post_meta($post_id, $key, $value);
            }
        }

        $tax_assignments = [
            'species' => array_filter([(int) ($_POST['species'] ?? 0)]),
            'breed' => array_filter([(int) ($_POST['breed'] ?? 0)]),
            'vibe' => array_filter(array_map('intval', (array) ($_POST['vibe'] ?? []))),
        ];

        foreach ($tax_assignments as $taxonomy => $term_ids) {
            if (!empty($term_ids)) {
                wp_set_object_terms($post_id, $term_ids, $taxonomy, false);
            }
        }

        $gallery_ids = [];
        if (!empty($_FILES['gallery']['name'][0])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $files = $_FILES['gallery'];
            $count = min(count($files['name']), 5);
            $total_bytes = 0;
            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }
                $total_bytes += (int) $files['size'][$i];
                if ($files['size'][$i] > 10 * 1024 * 1024) {
                    continue;
                }
                if ($total_bytes > 30 * 1024 * 1024) {
                    break;
                }
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];

                $check = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
                $allowed = [
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                    'image/gif',
                    'video/mp4',
                    'video/quicktime',
                ];
                if (empty($check['type']) || !in_array($check['type'], $allowed, true)) {
                    continue;
                }

                $attachment_id = media_handle_sideload($file, $post_id);
                if (!is_wp_error($attachment_id)) {
                    $gallery_ids[] = $attachment_id;
                    if (!has_post_thumbnail($post_id) && wp_attachment_is_image($attachment_id)) {
                        set_post_thumbnail($post_id, $attachment_id);
                    }
                }
            }
        }

        if (!empty($gallery_ids)) {
            update_post_meta($post_id, 'gallery', $gallery_ids);
        }

        $redirect = wp_get_referer() ?: home_url('/');
        $redirect = add_query_arg('submitted', 'true', $redirect);
        wp_safe_redirect($redirect);
        exit;
    }
}
add_action('admin_post_nopriv_damncute_submit_pet', 'damncute_handle_pet_submission');
add_action('admin_post_damncute_submit_pet', 'damncute_handle_pet_submission');

if (!function_exists('damncute_submit_rewrite')) {
    function damncute_submit_rewrite(): void {
        add_rewrite_rule('^submit/?$', 'index.php?dc_route=submit', 'top');
    }
}
add_action('init', 'damncute_submit_rewrite');

if (!function_exists('damncute_query_vars')) {
    function damncute_query_vars(array $vars): array {
        $vars[] = 'dc_route';
        return $vars;
    }
}
add_filter('query_vars', 'damncute_query_vars');

if (!function_exists('damncute_template_include')) {
    function damncute_template_include(string $template): string {
        if (get_query_var('dc_route') === 'submit') {
            $path = get_stylesheet_directory() . '/templates/submit-renderer.php';
            if (file_exists($path)) {
                return $path;
            }
        }
        return $template;
    }
}
add_filter('template_include', 'damncute_template_include');

// Force flush on next load (dev only, remove in prod)
add_action('init', function() {
    if (!get_option('damncute_flush_flag')) {
        flush_rewrite_rules();
        update_option('damncute_flush_flag', true);
    }
}, 999);