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

if (!function_exists('damncute_viewport_meta')) {
    function damncute_viewport_meta(): void
    {
        echo '<meta name="viewport" content="width=device-width, initial-scale=1" />';
    }
}
add_action('wp_head', 'damncute_viewport_meta', 1);

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
        $submit_page = get_page_by_path('submit');
        if (
            is_page('submit')
            || (is_singular('page') && $submit_page && get_queried_object_id() === (int) $submit_page->ID)
        ) {
            return '';
        }

        $atts = shortcode_atts([
            'variant' => 'button',
            'label' => __('Submit Your Pet', 'damncute'),
        ], $atts, 'damncute_submit_cta');

        $url = home_url('/submit/');
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

if (!function_exists('damncute_register_settings')) {
    function damncute_register_settings(): void
    {
        register_setting('general', 'damncute_forminator_id', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 39,
        ]);

        add_settings_field(
            'damncute_forminator_id',
            __('DamnCute Submit Form ID', 'damncute'),
            'damncute_forminator_id_field',
            'general'
        );
    }
}
add_action('admin_init', 'damncute_register_settings');

if (!function_exists('damncute_forminator_id_field')) {
    function damncute_forminator_id_field(): void
    {
        $value = (string) get_option('damncute_forminator_id', 39);
        printf(
            '<input type="number" min="1" name="damncute_forminator_id" value="%s" class="small-text" />',
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__('Forminator form ID for the submit page.', 'damncute') . '</p>';
    }
}

if (!function_exists('damncute_pet_submit_form_shortcode')) {
    function damncute_pet_submit_form_shortcode(array $atts = []): string
    {
        $atts = shortcode_atts([
            'id' => (string) get_option('damncute_forminator_id', 39),
        ], $atts, 'damncute_pet_submit_form');

        $form_id = absint($atts['id']);
        if ($form_id <= 0) {
            return '';
        }

        return do_shortcode(sprintf('[forminator_form id="%d"]', $form_id));
    }
}
add_shortcode('damncute_pet_submit_form', 'damncute_pet_submit_form_shortcode');

if (!function_exists('damncute_forminator_find_field')) {
    function damncute_forminator_find_field(array $field_data_array, string $field_id): ?array
    {
        foreach ($field_data_array as $field) {
            if (!empty($field['name']) && $field['name'] === $field_id) {
                return $field;
            }
        }
        return null;
    }
}

if (!function_exists('damncute_forminator_text_value')) {
    function damncute_forminator_text_value(array $field_data_array, string $field_id): string
    {
        $field = damncute_forminator_find_field($field_data_array, $field_id);
        if (!$field || !isset($field['value']) || is_array($field['value'])) {
            return '';
        }
        return trim((string) $field['value']);
    }
}

if (!function_exists('damncute_forminator_select_labels')) {
    function damncute_forminator_select_labels(array $field_data_array, string $field_id): array
    {
        $field = damncute_forminator_find_field($field_data_array, $field_id);
        if (!$field || !isset($field['value'])) {
            return [];
        }

        $selected = is_array($field['value']) ? $field['value'] : [$field['value']];
        $options = $field['field_array']['options'] ?? [];
        $labels = [];

        foreach ($options as $option) {
            $option_value = isset($option['value']) ? (string) $option['value'] : (string) ($option['label'] ?? '');
            if ($option_value === '') {
                continue;
            }
            if (in_array($option_value, $selected, true)) {
                $labels[] = isset($option['label']) ? (string) $option['label'] : $option_value;
            }
        }

        if (empty($labels)) {
            foreach ($selected as $value) {
                if ($value !== '') {
                    $labels[] = (string) $value;
                }
            }
        }

        return $labels;
    }
}

if (!function_exists('damncute_forminator_match_terms')) {
    function damncute_forminator_match_terms(string $taxonomy, array $labels): array
    {
        if (empty($labels)) {
            return [];
        }

        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }

        $term_ids = [];
        foreach ($labels as $label) {
            $needle = sanitize_title($label);
            foreach ($terms as $term) {
                if ($term->slug === $needle || sanitize_title($term->name) === $needle) {
                    $term_ids[] = (int) $term->term_id;
                }
            }
        }

        return array_values(array_unique($term_ids));
    }
}

if (!function_exists('damncute_forminator_insert_attachment')) {
    function damncute_forminator_insert_attachment(string $path, string $url, int $post_id): int
    {
        if ($path === '' || !file_exists($path)) {
            return 0;
        }

        $filetype = wp_check_filetype(basename($path), null);
        $attachment_id = wp_insert_attachment([
            'guid' => $url !== '' ? $url : $path,
            'post_mime_type' => $filetype['type'] ?? '',
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($path)),
            'post_content' => '',
            'post_status' => 'inherit',
        ], $path, $post_id);

        if (is_wp_error($attachment_id)) {
            return 0;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        $metadata = wp_generate_attachment_metadata($attachment_id, $path);
        if ($metadata) {
            wp_update_attachment_metadata($attachment_id, $metadata);
        }

        return (int) $attachment_id;
    }
}

if (!function_exists('damncute_forminator_upload_ids')) {
    function damncute_forminator_upload_ids(array $field_data_array, string $field_id, int $post_id): array
    {
        $field = damncute_forminator_find_field($field_data_array, $field_id);
        if (!$field || empty($field['value']['file'])) {
            return [];
        }

        $file_data = $field['value']['file'];
        $file_urls = $file_data['file_url'] ?? [];
        $file_paths = $file_data['file_path'] ?? [];

        if (!is_array($file_urls)) {
            $file_urls = $file_urls !== '' ? [$file_urls] : [];
        }
        if (!is_array($file_paths)) {
            $file_paths = $file_paths !== '' ? [$file_paths] : [];
        }

        $ids = [];
        foreach ($file_urls as $index => $url) {
            $url = (string) $url;
            $attachment_id = $url !== '' ? attachment_url_to_postid($url) : 0;
            if (!$attachment_id) {
                $path = (string) ($file_paths[$index] ?? '');
                $attachment_id = damncute_forminator_insert_attachment($path, $url, $post_id);
            }
            if ($attachment_id) {
                $ids[] = (int) $attachment_id;
            }
        }

        return $ids;
    }
}

if (!function_exists('damncute_forminator_create_pet')) {
    function damncute_forminator_create_pet($entry, int $form_id, array $field_data_array): void
    {
        $target_form_id = (int) get_option('damncute_forminator_id', 39);
        if ($form_id !== $target_form_id) {
            return;
        }

        $pet_name = sanitize_text_field(damncute_forminator_text_value($field_data_array, 'text-1'));
        $cute_description = sanitize_textarea_field(damncute_forminator_text_value($field_data_array, 'textarea-1'));

        if ($pet_name === '' || $cute_description === '') {
            return;
        }

        $post_id = wp_insert_post([
            'post_type' => 'pets',
            'post_title' => $pet_name,
            'post_content' => $cute_description,
            'post_status' => 'pending',
        ], true);

        if (is_wp_error($post_id)) {
            return;
        }

        $age = sanitize_text_field(damncute_forminator_text_value($field_data_array, 'text-2'));
        $owner_social = sanitize_text_field(damncute_forminator_text_value($field_data_array, 'text-3'));
        $adoption_status_labels = damncute_forminator_select_labels($field_data_array, 'select-4');
        $adoption_status = $adoption_status_labels[0] ?? '';

        $meta_map = [
            'pet_name' => $pet_name,
            'cute_description' => $cute_description,
            'age' => $age,
            'owner_social' => $owner_social,
            'adoption_status' => $adoption_status,
        ];

        foreach ($meta_map as $key => $value) {
            if ($value !== '') {
                update_post_meta($post_id, $key, $value);
            }
        }

        $species_ids = damncute_forminator_match_terms('species', damncute_forminator_select_labels($field_data_array, 'select-1'));
        $breed_ids = damncute_forminator_match_terms('breed', damncute_forminator_select_labels($field_data_array, 'select-2'));
        $vibe_ids = damncute_forminator_match_terms('vibe', damncute_forminator_select_labels($field_data_array, 'select-3'));

        if (!empty($species_ids)) {
            wp_set_object_terms($post_id, $species_ids, 'species', false);
        }
        if (!empty($breed_ids)) {
            wp_set_object_terms($post_id, $breed_ids, 'breed', false);
        }
        if (!empty($vibe_ids)) {
            wp_set_object_terms($post_id, $vibe_ids, 'vibe', false);
        }

        $gallery_ids = damncute_forminator_upload_ids($field_data_array, 'upload-1', $post_id);
        if (!empty($gallery_ids)) {
            update_post_meta($post_id, 'gallery', $gallery_ids);
            foreach ($gallery_ids as $attachment_id) {
                if (wp_attachment_is_image($attachment_id)) {
                    set_post_thumbnail($post_id, $attachment_id);
                    break;
                }
            }
        }
    }
}
add_action('forminator_custom_form_submit_before_set_fields', 'damncute_forminator_create_pet', 10, 3);
