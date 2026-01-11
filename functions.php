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

if (!function_exists('damncute_social_meta')) {
    function damncute_social_meta(): void
    {
        $title = get_bloginfo('name');
        $description = get_bloginfo('description');
        $url = home_url('/');
        $image_url = get_theme_file_uri('pic.png');

        if (is_singular('pets')) {
            $title = get_the_title();
            $description = get_the_excerpt();
            $url = get_permalink();
            $image_id = get_post_thumbnail_id();
            if ($image_id) {
                $image_url = wp_get_attachment_image_url($image_id, 'large');
            }
        } elseif (is_archive()) {
            $title = get_the_archive_title();
            $url = get_pagenum_link();
        }

        if (!$description) {
            $description = 'The internet’s cutest pets. Zero fluff. Just pure dopamine.';
        }

        // OpenGraph
        printf('<meta property="og:type" content="website" />' . "\n");
        printf('<meta property="og:title" content="%s" />' . "\n", esc_attr($title));
        printf('<meta property="og:description" content="%s" />' . "\n", esc_attr($description));
        printf('<meta property="og:url" content="%s" />' . "\n", esc_url($url));
        printf('<meta property="og:site_name" content="%s" />' . "\n", esc_attr(get_bloginfo('name')));
        if ($image_url) {
            printf('<meta property="og:image" content="%s" />' . "\n", esc_url($image_url));
        }

        // Twitter Card
        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '" />' . "\n";
        if ($image_url) {
            echo '<meta name="twitter:image" content="' . esc_url($image_url) . '" />' . "\n";
        }
    }
}
add_action('wp_head', 'damncute_social_meta', 5);

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
        wp_localize_script('damncute-theme', 'damncuteData', [
            'restUrl' => esc_url_raw(rest_url('damncute/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'damncute_assets');

if (!function_exists('damncute_admin_assets')) {
    function damncute_admin_assets(): void
    {
        $theme_version = wp_get_theme()->get('Version');
        $css_path = get_theme_file_path('assets/css/admin.css');
        $js_path = get_theme_file_path('assets/js/breed-manager.js');

        wp_enqueue_style(
            'damncute-admin',
            get_theme_file_uri('assets/css/admin.css'),
            [],
            file_exists($css_path) ? (string) filemtime($css_path) : $theme_version
        );

        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if ($screen && $screen->id === 'pets_page_damncute-breed-manager') {
                wp_enqueue_script(
                    'damncute-breed-manager',
                    get_theme_file_uri('assets/js/breed-manager.js'),
                    [],
                    file_exists($js_path) ? (string) filemtime($js_path) : $theme_version,
                    true
                );
            }
        }
    }
}
add_action('admin_enqueue_scripts', 'damncute_admin_assets');

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
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'comments'],
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
            'hierarchical' => false,
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

if (!function_exists('damncute_breed_species_field')) {
    function damncute_breed_species_field(?WP_Term $term = null): void
    {
        $species_terms = get_terms(['taxonomy' => 'species', 'hide_empty' => false]);
        if (is_wp_error($species_terms) || empty($species_terms)) {
            echo '<p>' . esc_html__('Create species terms first.', 'damncute') . '</p>';
            return;
        }

        $selected = [];
        if ($term) {
            $stored = get_term_meta($term->term_id, '_damncute_breed_species', true);
            $selected = is_array($stored) ? array_map('absint', $stored) : [];
        }

        wp_nonce_field('damncute_breed_species', 'damncute_breed_species_nonce');

        echo '<div class="form-field term-breed-species-wrap">';
        echo '<label>' . esc_html__('Applies to species', 'damncute') . '</label>';
        echo '<p class="description">' . esc_html__('Select which species this breed belongs to.', 'damncute') . '</p>';
        echo '<div style="margin-top:6px;">';
        foreach ($species_terms as $species) {
            $checked = in_array((int) $species->term_id, $selected, true) ? 'checked' : '';
            echo '<label style="display:block; margin-bottom:4px;">';
            echo '<input type="checkbox" name="damncute_breed_species[]" value="' . esc_attr((string) $species->term_id) . '" ' . $checked . ' /> ';
            echo esc_html($species->name);
            echo '</label>';
        }
        echo '</div>';
        echo '</div>';
    }
}

if (!function_exists('damncute_breed_species_add_field')) {
    function damncute_breed_species_add_field(): void
    {
        damncute_breed_species_field(null);
    }
}
add_action('breed_add_form_fields', 'damncute_breed_species_add_field');

if (!function_exists('damncute_breed_species_edit_field')) {
    function damncute_breed_species_edit_field(WP_Term $term): void
    {
        echo '<tr class="form-field term-breed-species-wrap"><th scope="row">';
        echo '<label>' . esc_html__('Applies to species', 'damncute') . '</label></th><td>';
        damncute_breed_species_field($term);
        echo '</td></tr>';
    }
}
add_action('breed_edit_form_fields', 'damncute_breed_species_edit_field');

if (!function_exists('damncute_save_breed_species_meta')) {
    function damncute_save_breed_species_meta(int $term_id): void
    {
        if (!isset($_POST['damncute_breed_species_nonce']) || !wp_verify_nonce($_POST['damncute_breed_species_nonce'], 'damncute_breed_species')) {
            return;
        }

        if (!current_user_can('manage_categories')) {
            return;
        }

        $species_ids = isset($_POST['damncute_breed_species']) ? (array) $_POST['damncute_breed_species'] : [];
        $species_ids = array_values(array_filter(array_map('absint', $species_ids)));

        if (empty($species_ids)) {
            delete_term_meta($term_id, '_damncute_breed_species');
            return;
        }

        update_term_meta($term_id, '_damncute_breed_species', $species_ids);
    }
}
add_action('created_breed', 'damncute_save_breed_species_meta');
add_action('edited_breed', 'damncute_save_breed_species_meta');

if (!function_exists('damncute_filter_breeds_by_species')) {
    function damncute_filter_breeds_by_species(int $post_id, WP_Post $post): void
    {
        if ($post->post_type !== 'pets') {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }

        $species_ids = wp_get_post_terms($post_id, 'species', ['fields' => 'ids']);
        if (empty($species_ids) || is_wp_error($species_ids)) {
            return;
        }

        $breed_terms = wp_get_post_terms($post_id, 'breed', ['fields' => 'ids']);
        if (empty($breed_terms) || is_wp_error($breed_terms)) {
            return;
        }

        $allowed = [];
        foreach ($breed_terms as $breed_id) {
            $allowed_species = get_term_meta((int) $breed_id, '_damncute_breed_species', true);
            $allowed_species = is_array($allowed_species) ? array_map('absint', $allowed_species) : [];

            if (empty($allowed_species)) {
                $allowed[] = (int) $breed_id;
                continue;
            }

            if (array_intersect($species_ids, $allowed_species)) {
                $allowed[] = (int) $breed_id;
            }
        }

        wp_set_object_terms($post_id, $allowed, 'breed', false);
    }
}
add_action('save_post_pets', 'damncute_filter_breeds_by_species', 10, 2);

if (!function_exists('damncute_register_meta')) {
    function damncute_register_meta(): void
    {
        $auth_callback = static function (): bool {
            return current_user_can('edit_posts');
        };

        $meta_fields = [
            'pet_name' => 'string',
            'breed' => 'string',
            'breed_type' => 'string',
            'age' => 'string',
            'owner_social' => 'string',
            'adoption_status' => 'string',
            'cute_description' => 'string',
            'about' => 'string',
            'favorite_snack' => 'string',
            'reaction_heart' => 'integer',
            'reaction_laugh' => 'integer',
            'reaction_adorable' => 'integer',
            'reaction_total' => 'integer',
            'vote_count' => 'integer',
        ];

        foreach ($meta_fields as $key => $type) {
            $sanitize_callback = $type === 'integer' ? 'absint' : 'sanitize_text_field';
            if (in_array($key, ['cute_description', 'about', 'favorite_snack'], true)) {
                $sanitize_callback = 'sanitize_textarea_field';
            }
            $args = [
                'type' => $type,
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => $sanitize_callback,
                'auth_callback' => $auth_callback,
            ];
            if (in_array($key, ['vote_count', 'reaction_heart', 'reaction_laugh', 'reaction_adorable', 'reaction_total'], true)) {
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
            $html .= '<div class="dc-filters__group">';
            $html .= sprintf('<span class="dc-filters__label">%s</span>', esc_html($label));
            foreach ($terms as $term) {
                $html .= sprintf(
                    '<a class="dc-chip" href="%s">%s</a>',
                    esc_url(get_term_link($term)),
                    esc_html($term->name)
                );
            }
            $html .= '</div>';
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

if (!function_exists('damncute_reaction_map')) {
    function damncute_reaction_map(): array
    {
        return [
            'heart' => '❤️',
            'laugh' => '😹',
            'adorable' => '😍',
        ];
    }
}

if (!function_exists('damncute_reaction_counts')) {
    function damncute_reaction_counts(int $post_id): array
    {
        $counts = [];
        $total = 0;
        foreach (damncute_reaction_map() as $key => $emoji) {
            $value = (int) get_post_meta($post_id, 'reaction_' . $key, true);
            $counts[$key] = $value;
            $total += $value;
        }
        $counts['total'] = $total;
        return $counts;
    }
}

if (!function_exists('damncute_owner_profile_data')) {
    function damncute_owner_profile_data(string $owner_social): ?array
    {
        $owner_social = trim($owner_social);
        if ($owner_social === '') {
            return null;
        }

        if (filter_var($owner_social, FILTER_VALIDATE_URL)) {
            $url = $owner_social;
            $parts = wp_parse_url($owner_social);
            $path = isset($parts['path']) ? trim($parts['path'], '/') : '';
            $handle = $path !== '' ? '@' . strtok($path, '/') : $owner_social;
            return [
                'label' => $handle,
                'url' => $url,
            ];
        }

        $handle = ltrim($owner_social, '@');
        if ($handle === '') {
            return null;
        }

        return [
            'label' => '@' . $handle,
            'url' => 'https://www.instagram.com/' . rawurlencode($handle) . '/',
        ];
    }
}

if (!function_exists('damncute_render_pet_card')) {
    function damncute_render_pet_card(int $post_id, ?string $meta = null): string
    {
        $title = get_the_title($post_id);
        $permalink = get_permalink($post_id);
        // Remove class from img, put it on wrapper
        $image = get_the_post_thumbnail($post_id, 'medium'); 
        $meta_html = $meta ? sprintf('<div class="dc-card__meta">%s</div>', esc_html($meta)) : '';

        return sprintf(
            '<div class="dc-card dc-card--compact"><div class="dc-card__media"><a href="%s">%s</a></div><div class="dc-card__body"><h3 class="dc-card__title"><a href="%s">%s</a></h3>%s</div></div>',
            esc_url($permalink),
            $image,
            esc_url($permalink),
            esc_html($title),
            $meta_html
        );
    }
}

if (!function_exists('damncute_pet_social_shortcode')) {
    function damncute_pet_social_shortcode(): string
    {
        if (!is_singular('pets')) {
            return '';
        }

        $post_id = get_the_ID();
        $share_url = get_permalink($post_id);
        $share_text = sprintf(
            __('Meet %s on Damn Cute.', 'damncute'),
            get_the_title($post_id)
        );
        $counts = damncute_reaction_counts($post_id);
        $profile = damncute_owner_profile_data((string) get_post_meta($post_id, 'owner_social', true));

        $submitter = '';
        if ($profile) {
            $submitter = sprintf(
                '<div class="dc-submitter"><span class="dc-submitter__label">%s</span><a class="dc-submitter__handle" href="%s" target="_blank" rel="noopener noreferrer">%s</a><a class="dc-submitter__follow" href="%s" target="_blank" rel="noopener noreferrer">%s</a></div>',
                esc_html__('Submitted by', 'damncute'),
                esc_url($profile['url']),
                esc_html($profile['label']),
                esc_url($profile['url']),
                esc_html__('Follow', 'damncute')
            );
        }

        $image_id = get_post_thumbnail_id($post_id);

        $reaction_buttons = '';
        foreach (damncute_reaction_map() as $key => $emoji) {
            $reaction_buttons .= sprintf(
                '<button class="dc-reaction" type="button" data-reaction-button data-reaction="%s" aria-pressed="false">%s <span data-reaction-count="%s">%d</span></button>',
                esc_attr($key),
                esc_html($emoji),
                esc_attr($key),
                $counts[$key] ?? 0
            );
        }

        return sprintf(
            '<div class="dc-social" data-image-id="%d" data-share-text="%s" data-share-url="%s"><div class="dc-social__row">%s<div class="dc-reactions" data-reaction-group data-post-id="%d">%s</div></div><div class="dc-share-row"><span class="dc-share__label">%s</span><button class="dc-share-button" type="button" data-share-platform="x">X</button><button class="dc-share-button" type="button" data-share-platform="facebook">Facebook</button><button class="dc-share-button" type="button" data-share-platform="instagram">IG</button><button class="dc-share-button" type="button" data-share-platform="tiktok">TikTok</button><button class="dc-share-button" type="button" data-share-platform="card">%s</button><button class="dc-share-button" type="button" data-share-platform="copy">%s</button></div></div>',
            (int) $image_id,
            esc_attr($share_text),
            esc_url($share_url),
            $submitter,
            $post_id,
            $reaction_buttons,
            esc_html__('Share', 'damncute'),
            esc_html__('Poster', 'damncute'),
            esc_html__('Copy link', 'damncute')
        );
    }
}
add_shortcode('damncute_pet_social', 'damncute_pet_social_shortcode');

if (!function_exists('damncute_pet_most_loved_shortcode')) {
    function damncute_pet_most_loved_shortcode(): string
    {
        if (!is_singular('pets')) {
            return '';
        }

        $posts = get_posts([
            'post_type' => 'pets',
            'post_status' => 'publish',
            'posts_per_page' => 4,
            'post__not_in' => [get_the_ID()],
            'meta_key' => 'reaction_total',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'date_query' => [
                [
                    'after' => '1 week ago',
                ],
            ],
        ]);

        if (empty($posts)) {
            return '';
        }

        $cards = '';
        foreach ($posts as $post) {
            $count = (int) get_post_meta($post->ID, 'reaction_total', true);
            $cards .= damncute_render_pet_card($post->ID, sprintf(_n('%d reaction', '%d reactions', $count, 'damncute'), $count));
        }

        return sprintf(
            '<section class="dc-related"><h2 class="dc-related__title">%s</h2><div class="dc-grid dc-grid--compact">%s</div></section>',
            esc_html__('Most loved this week', 'damncute'),
            $cards
        );
    }
}
add_shortcode('damncute_pet_most_loved', 'damncute_pet_most_loved_shortcode');

if (!function_exists('damncute_pet_related_shortcode')) {
    function damncute_pet_related_shortcode(): string
    {
        if (!is_singular('pets')) {
            return '';
        }

        $post_id = get_the_ID();
        $sections = '';

        $tax_query = [];
        $species_ids = wp_get_post_terms($post_id, 'species', ['fields' => 'ids']);
        $vibe_ids = wp_get_post_terms($post_id, 'vibe', ['fields' => 'ids']);
        if (!empty($species_ids)) {
            $tax_query[] = [
                'taxonomy' => 'species',
                'field' => 'term_id',
                'terms' => $species_ids,
            ];
        }
        if (!empty($vibe_ids)) {
            $tax_query[] = [
                'taxonomy' => 'vibe',
                'field' => 'term_id',
                'terms' => $vibe_ids,
            ];
        }

        if (!empty($tax_query)) {
            $related_posts = get_posts([
                'post_type' => 'pets',
                'post_status' => 'publish',
                'posts_per_page' => 4,
                'post__not_in' => [$post_id],
                'tax_query' => array_merge(['relation' => 'OR'], $tax_query),
            ]);

            if (!empty($related_posts)) {
                $cards = '';
                foreach ($related_posts as $post) {
                    $cards .= damncute_render_pet_card($post->ID);
                }
                $sections .= sprintf(
                    '<section class="dc-related"><h2 class="dc-related__title">%s</h2><div class="dc-grid dc-grid--compact">%s</div></section>',
                    esc_html__('Related by vibe or species', 'damncute'),
                    $cards
                );
            }
        }

        $owner_social = trim((string) get_post_meta($post_id, 'owner_social', true));
        if ($owner_social !== '') {
            $submitter_posts = get_posts([
                'post_type' => 'pets',
                'post_status' => 'publish',
                'posts_per_page' => 4,
                'post__not_in' => [$post_id],
                'meta_query' => [
                    [
                        'key' => 'owner_social',
                        'value' => $owner_social,
                        'compare' => '=',
                    ],
                ],
            ]);

            if (!empty($submitter_posts)) {
                $cards = '';
                foreach ($submitter_posts as $post) {
                    $cards .= damncute_render_pet_card($post->ID);
                }
                $sections .= sprintf(
                    '<section class="dc-related"><h2 class="dc-related__title">%s</h2><div class="dc-grid dc-grid--compact">%s</div></section>',
                    esc_html__('Also from this submitter', 'damncute'),
                    $cards
                );
            }
        }

        return $sections;
    }
}
add_shortcode('damncute_pet_related', 'damncute_pet_related_shortcode');

if (!function_exists('damncute_pet_sections_shortcode')) {
    function damncute_pet_sections_shortcode(): string
    {
        if (!is_singular('pets')) {
            return '';
        }

        $post_id = get_the_ID();
        $sections = [];
        $content = trim((string) get_post_field('post_content', $post_id));
        if ($content !== '') {
            $sections[] = [
                'title' => __('Why are they so cute?', 'damncute'),
                'body' => apply_filters('the_content', $content),
                'raw' => true,
            ];
        }

        $about = trim((string) get_post_meta($post_id, 'about', true));
        if ($about !== '') {
            $sections[] = [
                'title' => __('About', 'damncute'),
                'body' => wpautop(esc_html($about)),
                'raw' => false,
            ];
        }

        $favorite_snack = trim((string) get_post_meta($post_id, 'favorite_snack', true));
        if ($favorite_snack !== '') {
            $sections[] = [
                'title' => __('Favorite snack', 'damncute'),
                'body' => wpautop(esc_html($favorite_snack)),
                'raw' => false,
            ];
        }

        if (empty($sections)) {
            return '';
        }

        $output = '';
        foreach ($sections as $section) {
            $output .= '<section class="dc-pet-section">';
            $output .= sprintf('<h2 class="dc-pet-section__title">%s</h2>', esc_html($section['title']));
            $output .= '<div class="dc-pet-section__body">' . $section['body'] . '</div>';
            $output .= '</section>';
        }

        return $output;
    }
}
add_shortcode('damncute_pet_sections', 'damncute_pet_sections_shortcode');

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

if (!function_exists('damncute_term_import_taxonomies')) {
    function damncute_term_import_taxonomies(): array
    {
        return ['species', 'breed', 'vibe'];
    }
}

if (!function_exists('damncute_register_breed_manager_page')) {
    function damncute_register_breed_manager_page(): void
    {
        add_submenu_page(
            'edit.php?post_type=pets',
            __('Breed Manager', 'damncute'),
            __('Breed Manager', 'damncute'),
            'manage_categories',
            'damncute-breed-manager',
            'damncute_render_breed_manager_page'
        );
    }
}
add_action('admin_menu', 'damncute_register_breed_manager_page');

if (!function_exists('damncute_render_breed_manager_page')) {
    function damncute_render_breed_manager_page(): void
    {
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'manual';
        if (!in_array($tab, ['manual', 'import'], true)) {
            $tab = 'manual';
        }

        $species_terms = get_terms(['taxonomy' => 'species', 'hide_empty' => false]);
        if (is_wp_error($species_terms)) {
            $species_terms = [];
        }

        $base_url = admin_url('edit.php?post_type=pets&page=damncute-breed-manager');

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Breed Manager', 'damncute') . '</h1>';
        echo '<nav class="nav-tab-wrapper">';
        echo '<a class="nav-tab ' . ($tab === 'manual' ? 'nav-tab-active' : '') . '" href="' . esc_url(add_query_arg('tab', 'manual', $base_url)) . '">' . esc_html__('Manual', 'damncute') . '</a>';
        echo '<a class="nav-tab ' . ($tab === 'import' ? 'nav-tab-active' : '') . '" href="' . esc_url(add_query_arg('tab', 'import', $base_url)) . '">' . esc_html__('Import', 'damncute') . '</a>';
        echo '</nav>';

        if (isset($_GET['dc_breed_added'])) {
            $status = sanitize_key((string) $_GET['dc_breed_added']);
            if ($status === '1') {
                echo '<div class="notice notice-success"><p>' . esc_html__('Breed added.', 'damncute') . '</p></div>';
            } elseif ($status === 'exists') {
                echo '<div class="notice notice-warning"><p>' . esc_html__('Breed already exists.', 'damncute') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__('Could not add breed.', 'damncute') . '</p></div>';
            }
        }

        if (isset($_GET['dc_breeds_deleted'])) {
            $deleted = sanitize_key((string) $_GET['dc_breeds_deleted']);
            if ($deleted === '1') {
                echo '<div class="notice notice-success"><p>' . esc_html__('All breeds deleted.', 'damncute') . '</p></div>';
            } else {
                echo '<div class="notice notice-warning"><p>' . esc_html__('Delete cancelled. Confirmation text did not match.', 'damncute') . '</p></div>';
            }
        }

        if (isset($_GET['dc_import']) && isset($_GET['dc_imported'])) {
            $imported = isset($_GET['dc_imported']) ? absint($_GET['dc_imported']) : 0;
            $skipped = isset($_GET['dc_skipped']) ? absint($_GET['dc_skipped']) : 0;
            $failed = isset($_GET['dc_failed']) ? absint($_GET['dc_failed']) : 0;
            $message = sprintf(
                /* translators: 1: imported count, 2: skipped count, 3: failed count */
                esc_html__('Import complete: %1$d added, %2$d skipped, %3$d failed.', 'damncute'),
                $imported,
                $skipped,
                $failed
            );
            $class = $failed > 0 ? 'notice notice-warning' : 'notice notice-success';
            echo '<div class="' . esc_attr($class) . '"><p>' . $message . '</p></div>';
        }

        if ($tab === 'manual') {
            echo '<p>' . esc_html__('Add a single breed and map it to one or more species.', 'damncute') . '</p>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('damncute_add_breed', 'damncute_add_breed_nonce');
            echo '<input type="hidden" name="action" value="damncute_add_breed_term" />';
            echo '<table class="form-table" role="presentation"><tbody>';
            echo '<tr><th scope="row"><label for="damncute_breed_name">' . esc_html__('Breed name', 'damncute') . '</label></th><td>';
            echo '<input id="damncute_breed_name" name="damncute_breed_name" type="text" class="regular-text" required />';
            echo '</td></tr>';

            echo '<tr><th scope="row"><label for="damncute_breed_type_manual">' . esc_html__('Breed type', 'damncute') . '</label></th><td>';
            echo '<select id="damncute_breed_type_manual" name="damncute_breed_type_manual" style="max-width:320px; width:100%;">';
            foreach (damncute_breed_type_labels() as $key => $label) {
                echo '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
            }
            echo '</select>';
            echo '<p class="description">' . esc_html__('Optional; for dogs/cats that have a specific type.', 'damncute') . '</p>';
            echo '</td></tr>';

            echo '<tr><th scope="row"><label for="damncute_breed_species_manual">' . esc_html__('Species', 'damncute') . '</label></th><td>';
            if (!empty($species_terms)) {
                echo '<select id="damncute_breed_species_manual" name="damncute_breed_species_manual[]" multiple size="6" style="max-width:360px; width:100%;">';
                foreach ($species_terms as $species) {
                    echo '<option value="' . esc_attr((string) $species->term_id) . '">' . esc_html($species->name) . '</option>';
                }
                echo '</select>';
                echo '<p class="description">' . esc_html__('Select one or more species for this breed.', 'damncute') . '</p>';
            } else {
                echo '<p>' . esc_html__('Create species terms first to enable mapping.', 'damncute') . '</p>';
            }
            echo '</td></tr>';
            echo '</tbody></table>';
            submit_button(__('Add Breed', 'damncute'));
            echo '</form>';
            damncute_render_breed_list();
        } else {
            echo '<p>' . esc_html__('Pick a species, choose a CSV or JSON file, and import. Duplicates are skipped.', 'damncute') . '</p>';
            echo '<form method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('damncute_import_terms', 'damncute_import_terms_nonce');
            echo '<input type="hidden" name="action" value="damncute_import_terms" />';
            echo '<input type="hidden" name="taxonomy" value="breed" />';
            echo '<table class="form-table" role="presentation"><tbody>';
            echo '<tr><th scope="row"><label for="damncute_breed_type_import">' . esc_html__('Breed type', 'damncute') . '</label></th><td>';
            echo '<select id="damncute_breed_type_import" name="damncute_breed_type_import" style="max-width:320px; width:100%;">';
            foreach (damncute_breed_type_labels() as $key => $label) {
                echo '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
            }
            echo '</select>';
            echo '<p class="description">' . esc_html__('Optional; applies to all imported breeds.', 'damncute') . '</p>';
            echo '</td></tr>';

            echo '<tr><th scope="row"><label for="damncute_breed_species_import">' . esc_html__('Species', 'damncute') . '</label></th><td>';
            if (!empty($species_terms)) {
                echo '<select id="damncute_breed_species_import" name="damncute_breed_species_import[]" multiple size="6" style="max-width:360px; width:100%;">';
                foreach ($species_terms as $species) {
                    echo '<option value="' . esc_attr((string) $species->term_id) . '">' . esc_html($species->name) . '</option>';
                }
                echo '</select>';
                echo '<p class="description">' . esc_html__('Select one or more species for these breeds.', 'damncute') . '</p>';
            } else {
                echo '<p>' . esc_html__('Create species terms first to enable mapping.', 'damncute') . '</p>';
            }
            echo '</td></tr>';
            echo '<tr><th scope="row"><label for="damncute_import_file">' . esc_html__('File', 'damncute') . '</label></th><td>';
            echo '<input id="damncute_import_file" type="file" name="damncute_import_file" accept=".csv,.json" required />';
            echo '<p class="description">' . esc_html__('CSV: one term per line, or a column named "name". JSON: array of strings or objects with "name".', 'damncute') . '</p>';
            echo '</td></tr>';
            echo '</tbody></table>';
            submit_button(__('Import', 'damncute'));
            echo '</form>';
            damncute_render_breed_list();
        }

        echo '<hr style="margin:32px 0;">';
        echo '<h2 style="color:#b32d2e;">' . esc_html__('Danger Zone', 'damncute') . '</h2>';
        echo '<p>' . esc_html__('This permanently deletes all breed terms and removes them from pets.', 'damncute') . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('damncute_delete_all_breeds', 'damncute_delete_all_breeds_nonce');
        echo '<input type="hidden" name="action" value="damncute_delete_all_breeds" />';
        echo '<p><label for="damncute_delete_breeds_confirm"><strong>' . esc_html__('Type DELETE ALL to confirm', 'damncute') . '</strong></label></p>';
        echo '<input id="damncute_delete_breeds_confirm" name="damncute_delete_breeds_confirm" type="text" class="regular-text" />';
        echo '<p>';
        echo '<button type="submit" class="button" style="background:#b32d2e; border-color:#b32d2e; color:#fff;">' . esc_html__('Delete All Breeds', 'damncute') . '</button>';
        echo '</p>';
        echo '</form>';

        echo '</div>';
    }
}

if (!function_exists('damncute_render_breed_list')) {
    function damncute_render_breed_list(): void
    {
        $breeds = get_terms([
            'taxonomy' => 'breed',
            'hide_empty' => false,
        ]);

        if (is_wp_error($breeds) || empty($breeds)) {
            echo '<h2>' . esc_html__('Current breeds', 'damncute') . '</h2>';
            echo '<p>' . esc_html__('No breeds yet.', 'damncute') . '</p>';
            return;
        }

        $species_lookup = [];
        $species_slug_lookup = [];
        $species_terms = get_terms(['taxonomy' => 'species', 'hide_empty' => false]);
        if (is_wp_error($species_terms)) {
            $species_terms = [];
        }
        foreach ($species_terms as $species) {
            $species_lookup[(int) $species->term_id] = $species->name;
            $species_slug_lookup[(int) $species->term_id] = $species->slug;
        }

        echo '<h2 style="margin-top:32px;">' . esc_html__('Current breeds', 'damncute') . '</h2>';
        echo '<div class="damncute-breed-filters">';
        echo '<input type="search" data-breed-filter="search" placeholder="' . esc_attr__('Search breeds...', 'damncute') . '" />';
        echo '<select data-breed-filter="species">';
        echo '<option value="">' . esc_html__('All species', 'damncute') . '</option>';
        foreach ($species_terms as $species) {
            echo '<option value="' . esc_attr($species->slug) . '">' . esc_html($species->name) . '</option>';
        }
        echo '</select>';
        echo '<select data-breed-filter="type">';
        echo '<option value="">' . esc_html__('All types', 'damncute') . '</option>';
        foreach (damncute_breed_type_labels() as $key => $label) {
            if ($key === '') {
                continue;
            }
            echo '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '</div>';

        echo '<table id="damncute-breed-table" class="widefat striped" style="max-width:860px;">';
        echo '<thead><tr><th data-breed-sort="breed">' . esc_html__('Breed', 'damncute') . '</th><th data-breed-sort="type">' . esc_html__('Breed type', 'damncute') . '</th><th data-breed-sort="species">' . esc_html__('Species', 'damncute') . '</th></tr></thead>';
        echo '<tbody>';
        foreach ($breeds as $breed) {
            $mapped = get_term_meta($breed->term_id, '_damncute_breed_species', true);
            $mapped = is_array($mapped) ? array_map('absint', $mapped) : [];
            $labels = [];
            $species_slugs = [];
            foreach ($mapped as $species_id) {
                if (isset($species_lookup[$species_id])) {
                    $labels[] = $species_lookup[$species_id];
                    $species_slugs[] = $species_slug_lookup[$species_id] ?? '';
                }
            }

            $species_label = !empty($labels) ? implode(', ', array_map('esc_html', $labels)) : esc_html__('All/Unmapped', 'damncute');
            $breed_type = (string) get_term_meta($breed->term_id, '_damncute_breed_type', true);
            $breed_type_label = damncute_breed_type_labels()[$breed_type] ?? esc_html__('Unmapped', 'damncute');
            $species_slug_list = !empty($species_slugs) ? implode(',', array_filter($species_slugs)) : '';
            echo '<tr data-breed="' . esc_attr($breed->name) . '" data-type="' . esc_attr($breed_type) . '" data-species="' . esc_attr($species_slug_list) . '">';
            echo '<td>' . esc_html($breed->name) . '</td>';
            echo '<td>' . esc_html($breed_type_label) . '</td>';
            echo '<td>' . $species_label . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
}

if (!function_exists('damncute_breed_type_labels')) {
    function damncute_breed_type_labels(): array
    {
        return [
            '' => __('— None —', 'damncute'),
            'akc' => __('AKC Registered', 'damncute'),
            'designer' => __('Designer', 'damncute'),
            'just-cute' => __('Just Cute', 'damncute'),
            'purebred' => __('Purebred', 'damncute'),
            'mixed' => __('Mixed', 'damncute'),
        ];
    }
}

if (!function_exists('damncute_parse_term_names_from_json')) {
    function damncute_parse_term_names_from_json(array $data): array
    {
        $items = $data;
        if (isset($data['terms']) && is_array($data['terms'])) {
            $items = $data['terms'];
        }
        if (isset($data['items']) && is_array($data['items'])) {
            $items = $data['items'];
        }

        $names = [];
        foreach ($items as $item) {
            if (is_string($item)) {
                $names[] = $item;
                continue;
            }
            if (is_array($item)) {
                if (isset($item['name']) && is_string($item['name'])) {
                    $names[] = $item['name'];
                } elseif (isset($item['term']) && is_string($item['term'])) {
                    $names[] = $item['term'];
                } elseif (isset($item['label']) && is_string($item['label'])) {
                    $names[] = $item['label'];
                }
            }
        }

        return $names;
    }
}

if (!function_exists('damncute_parse_term_names_from_csv')) {
    function damncute_parse_term_names_from_csv(string $content): array
    {
        $handle = fopen('php://temp', 'r+');
        if (!$handle) {
            return [];
        }

        fwrite($handle, $content);
        rewind($handle);

        $first_line = fgets($handle);
        if ($first_line === false) {
            fclose($handle);
            return [];
        }

        $comma_count = substr_count($first_line, ',');
        $semicolon_count = substr_count($first_line, ';');
        $delimiter = $semicolon_count > $comma_count ? ';' : ',';

        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            return [];
        }

        $header_lower = array_map('strtolower', $header);
        $name_index = array_search('name', $header_lower, true);
        if ($name_index === false) {
            $name_index = array_search('term', $header_lower, true);
        }
        if ($name_index === false) {
            $name_index = array_search('label', $header_lower, true);
        }
        $type_index = array_search('type', $header_lower, true);

        $rows = [];
        if ($name_index === false) {
            $name_index = 0;
            $rows[] = [
                'name' => $header[$name_index] ?? '',
                'type' => $header[$type_index] ?? ($header[1] ?? ''),
            ];
        }

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = [
                'name' => $row[$name_index] ?? '',
                'type' => $type_index !== false ? ($row[$type_index] ?? '') : ($row[1] ?? ''),
            ];
        }

        fclose($handle);
        return $rows;
    }
}

if (!function_exists('damncute_handle_term_import')) {
    function damncute_handle_term_import(): void
    {
        if (!isset($_POST['damncute_import_terms_nonce']) || !wp_verify_nonce($_POST['damncute_import_terms_nonce'], 'damncute_import_terms')) {
            wp_die(esc_html__('Invalid nonce.', 'damncute'));
        }

        $taxonomy = isset($_POST['taxonomy']) ? sanitize_key($_POST['taxonomy']) : '';
        if (!in_array($taxonomy, damncute_term_import_taxonomies(), true)) {
            wp_die(esc_html__('Invalid taxonomy.', 'damncute'));
        }

        $tax = get_taxonomy($taxonomy);
        $cap = $tax && isset($tax->cap->manage_terms) ? $tax->cap->manage_terms : 'manage_categories';
        if (!current_user_can($cap)) {
            wp_die(esc_html__('Permission denied.', 'damncute'));
        }

        if (!isset($_FILES['damncute_import_file']) || !is_uploaded_file($_FILES['damncute_import_file']['tmp_name'])) {
            wp_die(esc_html__('No file uploaded.', 'damncute'));
        }

        $content = file_get_contents($_FILES['damncute_import_file']['tmp_name']);
        if ($content === false) {
            wp_die(esc_html__('Could not read file.', 'damncute'));
        }

        $trimmed = ltrim($content);
        $names = [];
        if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
            $data = json_decode($content, true);
            if (!is_array($data)) {
                wp_die(esc_html__('Invalid JSON.', 'damncute'));
            }
            $names = damncute_parse_term_names_from_json($data);
        } else {
            $names = damncute_parse_term_names_from_csv($content);
        }

        $species_ids = [];
        if ($taxonomy === 'breed' && isset($_POST['damncute_breed_species_import'])) {
            $species_ids = array_values(array_filter(array_map('absint', (array) $_POST['damncute_breed_species_import'])));
        }
        $breed_type = '';
        if ($taxonomy === 'breed' && isset($_POST['damncute_breed_type_import'])) {
            $breed_type = sanitize_key((string) $_POST['damncute_breed_type_import']);
        }
        $breed_type_labels = damncute_breed_type_labels();
        if (!isset($breed_type_labels[$breed_type])) {
            $breed_type = '';
        }

        $imported = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($names as $row) {
            $name = '';
            $row_type = '';
            if (is_array($row)) {
                $name = sanitize_text_field((string) ($row['name'] ?? ''));
                $row_type = sanitize_text_field((string) ($row['type'] ?? ''));
            } else {
                $name = sanitize_text_field((string) $row);
            }

            if ($name === '') {
                continue;
            }

            if (term_exists($name, $taxonomy)) {
                $skipped++;
                continue;
            }

            $result = wp_insert_term($name, $taxonomy);
            if (is_wp_error($result)) {
                $failed++;
                continue;
            }

            if ($taxonomy === 'breed') {
                if (!empty($species_ids)) {
                    update_term_meta((int) $result['term_id'], '_damncute_breed_species', $species_ids);
                }
                $final_type = '';
                if ($row_type !== '') {
                    $row_key = sanitize_key($row_type);
                    if (isset($breed_type_labels[$row_key])) {
                        $final_type = $row_key;
                    } else {
                        foreach ($breed_type_labels as $key => $label) {
                            if (strtolower((string) $label) === strtolower($row_type)) {
                                $final_type = $key;
                                break;
                            }
                        }
                    }
                }
                if ($final_type === '' && $breed_type !== '') {
                    $final_type = $breed_type;
                }
                if ($final_type !== '') {
                    update_term_meta((int) $result['term_id'], '_damncute_breed_type', $final_type);
                }
            }

            $imported++;
        }

        $redirect = wp_get_referer();
        if (!$redirect) {
            $redirect = admin_url('edit-tags.php?taxonomy=' . $taxonomy);
        }

        $redirect = add_query_arg([
            'dc_import' => 1,
            'dc_imported' => $imported,
            'dc_skipped' => $skipped,
            'dc_failed' => $failed,
        ], $redirect);

        wp_safe_redirect($redirect);
        exit;
    }
}
add_action('admin_post_damncute_import_terms', 'damncute_handle_term_import');

if (!function_exists('damncute_handle_add_breed_term')) {
    function damncute_handle_add_breed_term(): void
    {
        if (!isset($_POST['damncute_add_breed_nonce']) || !wp_verify_nonce($_POST['damncute_add_breed_nonce'], 'damncute_add_breed')) {
            wp_die(esc_html__('Invalid nonce.', 'damncute'));
        }

        if (!current_user_can('manage_categories')) {
            wp_die(esc_html__('Permission denied.', 'damncute'));
        }

        $name = isset($_POST['damncute_breed_name']) ? sanitize_text_field($_POST['damncute_breed_name']) : '';
        if ($name === '') {
            wp_safe_redirect(add_query_arg('dc_breed_added', '0', admin_url('edit.php?post_type=pets&page=damncute-breed-manager')));
            exit;
        }

        if (term_exists($name, 'breed')) {
            wp_safe_redirect(add_query_arg('dc_breed_added', 'exists', admin_url('edit.php?post_type=pets&page=damncute-breed-manager')));
            exit;
        }

        $result = wp_insert_term($name, 'breed');
        if (is_wp_error($result)) {
            wp_safe_redirect(add_query_arg('dc_breed_added', '0', admin_url('edit.php?post_type=pets&page=damncute-breed-manager')));
            exit;
        }

        $breed_type = isset($_POST['damncute_breed_type_manual']) ? sanitize_key((string) $_POST['damncute_breed_type_manual']) : '';
        $breed_type_labels = damncute_breed_type_labels();
        if (!isset($breed_type_labels[$breed_type])) {
            $breed_type = '';
        }

        $species_ids = isset($_POST['damncute_breed_species_manual']) ? (array) $_POST['damncute_breed_species_manual'] : [];
        $species_ids = array_values(array_filter(array_map('absint', $species_ids)));
        if (!empty($species_ids)) {
            update_term_meta((int) $result['term_id'], '_damncute_breed_species', $species_ids);
        }
        if ($breed_type !== '') {
            update_term_meta((int) $result['term_id'], '_damncute_breed_type', $breed_type);
        }

        wp_safe_redirect(add_query_arg('dc_breed_added', '1', admin_url('edit.php?post_type=pets&page=damncute-breed-manager')));
        exit;
    }
}
add_action('admin_post_damncute_add_breed_term', 'damncute_handle_add_breed_term');

if (!function_exists('damncute_handle_delete_all_breeds')) {
    function damncute_handle_delete_all_breeds(): void
    {
        if (!isset($_POST['damncute_delete_all_breeds_nonce']) || !wp_verify_nonce($_POST['damncute_delete_all_breeds_nonce'], 'damncute_delete_all_breeds')) {
            wp_die(esc_html__('Invalid nonce.', 'damncute'));
        }

        if (!current_user_can('manage_categories')) {
            wp_die(esc_html__('Permission denied.', 'damncute'));
        }

        $confirm = isset($_POST['damncute_delete_breeds_confirm']) ? trim((string) $_POST['damncute_delete_breeds_confirm']) : '';
        if ($confirm !== 'DELETE ALL') {
            wp_safe_redirect(add_query_arg('dc_breeds_deleted', '0', admin_url('edit.php?post_type=pets&page=damncute-breed-manager')));
            exit;
        }

        $breeds = get_terms([
            'taxonomy' => 'breed',
            'hide_empty' => false,
            'fields' => 'ids',
        ]);

        if (!is_wp_error($breeds)) {
            foreach ($breeds as $term_id) {
                wp_delete_term((int) $term_id, 'breed');
            }
        }

        wp_safe_redirect(add_query_arg('dc_breeds_deleted', '1', admin_url('edit.php?post_type=pets&page=damncute-breed-manager')));
        exit;
    }
}
add_action('admin_post_damncute_delete_all_breeds', 'damncute_handle_delete_all_breeds');

if (!function_exists('damncute_term_import_notice')) {
    function damncute_term_import_notice(): void
    {
        if (!isset($_GET['dc_import']) || !isset($_GET['dc_imported'])) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || !in_array($screen->taxonomy ?? '', damncute_term_import_taxonomies(), true)) {
            return;
        }

        $imported = isset($_GET['dc_imported']) ? absint($_GET['dc_imported']) : 0;
        $skipped = isset($_GET['dc_skipped']) ? absint($_GET['dc_skipped']) : 0;
        $failed = isset($_GET['dc_failed']) ? absint($_GET['dc_failed']) : 0;

        $message = sprintf(
            /* translators: 1: imported count, 2: skipped count, 3: failed count */
            esc_html__('Import complete: %1$d added, %2$d skipped, %3$d failed.', 'damncute'),
            $imported,
            $skipped,
            $failed
        );

        $class = $failed > 0 ? 'notice notice-warning' : 'notice notice-success';
        echo '<div class="' . esc_attr($class) . '"><p>' . $message . '</p></div>';
    }
}
add_action('admin_notices', 'damncute_term_import_notice');

if (!function_exists('damncute_pet_of_day_metabox')) {
    function damncute_pet_of_day_metabox(): void
    {
        add_meta_box(
            'damncute-pet-of-day',
            __('Pet of the Day', 'damncute'),
            'damncute_pet_of_day_metabox_render',
            'pets',
            'side',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'damncute_pet_of_day_metabox');

if (!function_exists('damncute_breed_type_metabox')) {
    function damncute_breed_type_metabox(): void
    {
        add_meta_box(
            'damncute-breed-type',
            __('Breed Type', 'damncute'),
            'damncute_breed_type_metabox_render',
            'pets',
            'side',
            'default'
        );
    }
}
add_action('add_meta_boxes', 'damncute_breed_type_metabox');

if (!function_exists('damncute_pet_details_metabox')) {
    function damncute_pet_details_metabox(): void
    {
        add_meta_box(
            'damncute-pet-details',
            __('Pet Details', 'damncute'),
            'damncute_pet_details_metabox_render',
            'pets',
            'normal',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'damncute_pet_details_metabox');

if (!function_exists('damncute_pet_details_metabox_render')) {
    function damncute_pet_details_metabox_render(WP_Post $post): void
    {
        wp_nonce_field('damncute_pet_details_save', 'damncute_pet_details_nonce');

        $pet_name = (string) get_post_meta($post->ID, 'pet_name', true);
        $cute_description = (string) get_post_meta($post->ID, 'cute_description', true);
        $age = (string) get_post_meta($post->ID, 'age', true);
        $owner_social = (string) get_post_meta($post->ID, 'owner_social', true);
        $about = (string) get_post_meta($post->ID, 'about', true);
        $favorite_snack = (string) get_post_meta($post->ID, 'favorite_snack', true);
        $adoption_status = (string) get_post_meta($post->ID, 'adoption_status', true);

        $adoption_options = [
            '' => __('— Select —', 'damncute'),
            'Not listed' => __('Not listed', 'damncute'),
            'Already adopted' => __('Already adopted', 'damncute'),
            'Looking for a home' => __('Looking for a home', 'damncute'),
            'In foster care' => __('In foster care', 'damncute'),
        ];

        echo '<div class="damncute-pet-details-metabox">';
        echo '<p class="description">' . esc_html__('Use the main title and body for Pet Name and Cute Description, or edit them here to sync.', 'damncute') . '</p>';

        echo '<p><label for="damncute_pet_name"><strong>' . esc_html__('Pet Name', 'damncute') . '</strong></label><br />';
        echo '<input type="text" id="damncute_pet_name" name="damncute_pet_name" class="widefat" value="' . esc_attr($pet_name !== '' ? $pet_name : $post->post_title) . '" /></p>';

        echo '<p><label for="damncute_cute_description"><strong>' . esc_html__('Cute Description', 'damncute') . '</strong></label>';
        echo '<textarea id="damncute_cute_description" name="damncute_cute_description" rows="4" class="widefat">' . esc_textarea($cute_description !== '' ? $cute_description : $post->post_content) . '</textarea></p>';

        echo '<p><label for="damncute_age"><strong>' . esc_html__('Age', 'damncute') . '</strong></label><br />';
        echo '<input type="text" id="damncute_age" name="damncute_age" class="widefat" value="' . esc_attr($age) . '" /></p>';

        echo '<p><label for="damncute_owner_social"><strong>' . esc_html__('Your Instagram/TikTok', 'damncute') . '</strong></label><br />';
        echo '<input type="text" id="damncute_owner_social" name="damncute_owner_social" class="widefat" value="' . esc_attr($owner_social) . '" /></p>';

        echo '<p><label for="damncute_about"><strong>' . esc_html__('About', 'damncute') . '</strong></label>';
        echo '<textarea id="damncute_about" name="damncute_about" rows="4" class="widefat">' . esc_textarea($about) . '</textarea></p>';

        echo '<p><label for="damncute_favorite_snack"><strong>' . esc_html__('Favorite Snack', 'damncute') . '</strong></label>';
        echo '<textarea id="damncute_favorite_snack" name="damncute_favorite_snack" rows="2" class="widefat">' . esc_textarea($favorite_snack) . '</textarea></p>';

        echo '<p><label for="damncute_adoption_status"><strong>' . esc_html__('Adoption Status', 'damncute') . '</strong></label><br />';
        echo '<select id="damncute_adoption_status" name="damncute_adoption_status" class="widefat">';
        foreach ($adoption_options as $value => $label) {
            $selected = selected($adoption_status, $value, false);
            echo '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select></p>';
        echo '</div>';
    }
}

if (!function_exists('damncute_breed_type_options')) {
    function damncute_breed_type_options(array $species_slugs): array
    {
        $options = ['' => __('— Select —', 'damncute')];

        if (in_array('dog', $species_slugs, true)) {
            $options['akc'] = __('AKC Registered', 'damncute');
            $options['designer'] = __('Designer', 'damncute');
            $options['just-cute'] = __('Just Cute', 'damncute');
        }

        if (in_array('cat', $species_slugs, true)) {
            $options['purebred'] = __('Purebred', 'damncute');
            $options['mixed'] = __('Mixed', 'damncute');
            $options['just-cute'] = __('Just Cute', 'damncute');
        }

        return $options;
    }
}

if (!function_exists('damncute_normalize_breed_type')) {
    function damncute_normalize_breed_type(string $value): string
    {
        $value = sanitize_key($value);
        $map = [
            'akc-registered' => 'akc',
            'akc' => 'akc',
            'designer' => 'designer',
            'purebred' => 'purebred',
            'mixed' => 'mixed',
            'just-cute' => 'just-cute',
        ];

        return $map[$value] ?? '';
    }
}

if (!function_exists('damncute_breed_type_metabox_render')) {
    function damncute_breed_type_metabox_render(WP_Post $post): void
    {
        wp_nonce_field('damncute_breed_type_save', 'damncute_breed_type_nonce');
        $raw_value = (string) get_post_meta($post->ID, 'breed_type', true);
        $value = damncute_normalize_breed_type($raw_value);

        $species_slugs = wp_get_post_terms($post->ID, 'species', ['fields' => 'slugs']);
        if (is_wp_error($species_slugs)) {
            $species_slugs = [];
        }

        $options = damncute_breed_type_options($species_slugs);
        $has_choices = count($options) > 1;

        echo '<p>' . esc_html__('Only applies when Species includes Dog or Cat.', 'damncute') . '</p>';
        echo '<select name="damncute_breed_type" style="width:100%;"' . ($has_choices ? '' : ' disabled') . '>';
        foreach ($options as $key => $label) {
            $selected = selected($value, $key, false);
            echo '<option value="' . esc_attr($key) . '"' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        if (!$has_choices) {
            echo '<p class="description">' . esc_html__('Select Dog or Cat in Species to enable options.', 'damncute') . '</p>';
        }
    }
}

if (!function_exists('damncute_save_breed_type')) {
    function damncute_save_breed_type(int $post_id, WP_Post $post): void
    {
        if ($post->post_type !== 'pets') {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!isset($_POST['damncute_breed_type_nonce']) || !wp_verify_nonce($_POST['damncute_breed_type_nonce'], 'damncute_breed_type_save')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $species_slugs = wp_get_post_terms($post_id, 'species', ['fields' => 'slugs']);
        if (is_wp_error($species_slugs) || empty($species_slugs)) {
            delete_post_meta($post_id, 'breed_type');
            return;
        }

        $allowed = array_keys(damncute_breed_type_options($species_slugs));
        $value = isset($_POST['damncute_breed_type']) ? damncute_normalize_breed_type((string) $_POST['damncute_breed_type']) : '';
        if ($value === '' || !in_array($value, $allowed, true)) {
            delete_post_meta($post_id, 'breed_type');
            return;
        }

        update_post_meta($post_id, 'breed_type', $value);
    }
}
add_action('save_post_pets', 'damncute_save_breed_type', 20, 2);

if (!function_exists('damncute_save_pet_details')) {
    function damncute_save_pet_details(int $post_id, WP_Post $post): void
    {
        if ($post->post_type !== 'pets') {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!isset($_POST['damncute_pet_details_nonce']) || !wp_verify_nonce($_POST['damncute_pet_details_nonce'], 'damncute_pet_details_save')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $pet_name = isset($_POST['damncute_pet_name']) ? sanitize_text_field((string) $_POST['damncute_pet_name']) : '';
        $cute_description = isset($_POST['damncute_cute_description']) ? sanitize_textarea_field((string) $_POST['damncute_cute_description']) : '';

        if ($pet_name !== '' && $pet_name !== $post->post_title) {
            remove_action('save_post_pets', 'damncute_save_pet_details', 10);
            wp_update_post([
                'ID' => $post_id,
                'post_title' => $pet_name,
            ]);
            add_action('save_post_pets', 'damncute_save_pet_details', 10, 2);
        }

        if ($cute_description !== $post->post_content) {
            remove_action('save_post_pets', 'damncute_save_pet_details', 10);
            wp_update_post([
                'ID' => $post_id,
                'post_content' => $cute_description,
            ]);
            add_action('save_post_pets', 'damncute_save_pet_details', 10, 2);
        }

        $meta_updates = [
            'pet_name' => $pet_name,
            'cute_description' => $cute_description,
            'age' => isset($_POST['damncute_age']) ? sanitize_text_field((string) $_POST['damncute_age']) : '',
            'owner_social' => isset($_POST['damncute_owner_social']) ? sanitize_text_field((string) $_POST['damncute_owner_social']) : '',
            'about' => isset($_POST['damncute_about']) ? sanitize_textarea_field((string) $_POST['damncute_about']) : '',
            'favorite_snack' => isset($_POST['damncute_favorite_snack']) ? sanitize_textarea_field((string) $_POST['damncute_favorite_snack']) : '',
            'adoption_status' => isset($_POST['damncute_adoption_status']) ? sanitize_text_field((string) $_POST['damncute_adoption_status']) : '',
        ];

        foreach ($meta_updates as $key => $value) {
            if ($value === '') {
                delete_post_meta($post_id, $key);
                continue;
            }
            update_post_meta($post_id, $key, $value);
        }
    }
}
add_action('save_post_pets', 'damncute_save_pet_details', 10, 2);

if (!function_exists('damncute_pet_of_day_metabox_render')) {
    function damncute_pet_of_day_metabox_render(WP_Post $post): void
    {
        wp_nonce_field('damncute_pet_of_day_save', 'damncute_pet_of_day_nonce');
        $scheduled = (string) get_post_meta($post->ID, '_damncute_pet_of_day_at', true);
        $selected_id = absint(get_option('damncute_pet_of_day_id', 0));
        $is_selected = $selected_id === (int) $post->ID;

        if ($scheduled !== '') {
            $tz = wp_timezone();
            try {
                $dt = new DateTimeImmutable('@' . (int) $scheduled);
                $scheduled = $dt->setTimezone($tz)->format('Y-m-d\TH:i');
            } catch (Exception $e) {
                $scheduled = '';
            }
        }

        $feature_url = wp_nonce_url(
            admin_url('admin-post.php?action=damncute_feature_pet_of_day&post_id=' . (int) $post->ID),
            'damncute_feature_pet_of_day'
        );

        echo '<p>' . esc_html__('Feature this pet now or schedule it for later.', 'damncute') . '</p>';

        if ($is_selected) {
            echo '<p><strong>' . esc_html__('Currently featured.', 'damncute') . '</strong></p>';
        }

        echo '<p><a class="button button-primary" href="' . esc_url($feature_url) . '">' . esc_html__('Feature Now', 'damncute') . '</a></p>';

        echo '<label for="damncute_pet_of_day_at"><strong>' . esc_html__('Schedule time', 'damncute') . '</strong></label>';
        echo '<p><input type="datetime-local" id="damncute_pet_of_day_at" name="damncute_pet_of_day_at" value="' . esc_attr($scheduled) . '" /></p>';
        echo '<p class="description">' . esc_html__('Leave empty to clear the schedule.', 'damncute') . '</p>';
    }
}

if (!function_exists('damncute_pet_of_day_row_action')) {
    function damncute_pet_of_day_row_action(array $actions, WP_Post $post): array
    {
        if ($post->post_type !== 'pets' || !current_user_can('edit_post', $post->ID)) {
            return $actions;
        }

        $url = wp_nonce_url(
            admin_url('admin-post.php?action=damncute_feature_pet_of_day&post_id=' . (int) $post->ID),
            'damncute_feature_pet_of_day'
        );

        $actions['damncute_feature_pet'] = '<a href="' . esc_url($url) . '">' . esc_html__('Feature Today', 'damncute') . '</a>';
        return $actions;
    }
}
add_filter('post_row_actions', 'damncute_pet_of_day_row_action', 10, 2);

if (!function_exists('damncute_handle_feature_pet_of_day')) {
    function damncute_handle_feature_pet_of_day(): void
    {
        if (!current_user_can('edit_posts')) {
            wp_die(esc_html__('Permission denied.', 'damncute'));
        }

        check_admin_referer('damncute_feature_pet_of_day');

        $post_id = isset($_GET['post_id']) ? absint($_GET['post_id']) : 0;
        if (!$post_id || get_post_type($post_id) !== 'pets') {
            wp_die(esc_html__('Invalid pet.', 'damncute'));
        }

        damncute_set_pet_of_day($post_id);
        delete_post_meta($post_id, '_damncute_pet_of_day_at');
        wp_clear_scheduled_hook('damncute_pet_of_day_schedule', [$post_id]);

        wp_safe_redirect(wp_get_referer() ?: admin_url('edit.php?post_type=pets'));
        exit;
    }
}
add_action('admin_post_damncute_feature_pet_of_day', 'damncute_handle_feature_pet_of_day');

if (!function_exists('damncute_save_pet_of_day_schedule')) {
    function damncute_save_pet_of_day_schedule(int $post_id, WP_Post $post): void
    {
        if ($post->post_type !== 'pets' || !current_user_can('edit_post', $post_id)) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!isset($_POST['damncute_pet_of_day_nonce']) || !wp_verify_nonce($_POST['damncute_pet_of_day_nonce'], 'damncute_pet_of_day_save')) {
            return;
        }

        $raw = isset($_POST['damncute_pet_of_day_at']) ? sanitize_text_field($_POST['damncute_pet_of_day_at']) : '';
        if ($raw === '') {
            delete_post_meta($post_id, '_damncute_pet_of_day_at');
            wp_clear_scheduled_hook('damncute_pet_of_day_schedule', [$post_id]);
            return;
        }

        $tz = wp_timezone();
        $dt = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $raw, $tz);
        if (!$dt) {
            return;
        }

        $timestamp = $dt->getTimestamp();
        update_post_meta($post_id, '_damncute_pet_of_day_at', (string) $timestamp);

        if ($timestamp <= time()) {
            damncute_set_pet_of_day($post_id);
            wp_clear_scheduled_hook('damncute_pet_of_day_schedule', [$post_id]);
            return;
        }

        wp_clear_scheduled_hook('damncute_pet_of_day_schedule', [$post_id]);
        wp_schedule_single_event($timestamp, 'damncute_pet_of_day_schedule', [$post_id]);
    }
}
add_action('save_post_pets', 'damncute_save_pet_of_day_schedule', 10, 2);

if (!function_exists('damncute_set_pet_of_day')) {
    function damncute_set_pet_of_day(int $post_id): void
    {
        if (get_post_status($post_id) !== 'publish') {
            return;
        }

        update_option('damncute_pet_of_day_id', $post_id);
        set_transient('damncute_pet_of_day_v2', $post_id, HOUR_IN_SECONDS * 12);
    }
}

add_action('damncute_pet_of_day_schedule', 'damncute_set_pet_of_day');

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

// Initialize Autoloader
// In a real Composer project, you'd require_once __DIR__ . '/vendor/autoload.php';
// For now, we manually include our classes since we haven't run `composer install` yet.
if (file_exists(__DIR__ . '/inc/class-pet-submission-handler.php')) {
    require_once __DIR__ . '/inc/class-pet-submission-handler.php';
}

if (!function_exists('damncute_init_submission_handler')) {
    function damncute_init_submission_handler($entry, $form_id, $field_data_array): void
    {
        try {
            if (class_exists('DamnCute\Pet_Submission_Handler')) {
                $handler = new DamnCute\Pet_Submission_Handler();
                $handler->handle_submission($entry, $form_id, $field_data_array);
            }
        } catch (Throwable $e) {
            error_log('DamnCute Submission Error: ' . $e->getMessage());
        }
    }
}
add_action('forminator_custom_form_submit_before_set_fields', 'damncute_init_submission_handler', 10, 3);

if (!function_exists('damncute_filter_forminator_submit_errors')) {
    function damncute_filter_forminator_submit_errors(array $errors, int $form_id, array $field_data_array): array
    {
        $target_form_id = (int) get_option('damncute_forminator_id', 39);
        if ($form_id !== $target_form_id || empty($errors)) {
            return $errors;
        }

        $dynamic_breed_fields = ['select-6', 'select-7', 'select-8', 'select-9'];
        $filtered = [];
        foreach ($errors as $error) {
            if (!is_array($error)) {
                $filtered[] = $error;
                continue;
            }
            $field_id = (string) array_key_first($error);
            $message = (string) ($error[$field_id] ?? '');
            if (in_array($field_id, $dynamic_breed_fields, true) && $message === 'Selected value does not exist.') {
                continue;
            }
            $filtered[] = $error;
        }

        return $filtered;
    }
}
add_filter('forminator_custom_form_submit_errors', 'damncute_filter_forminator_submit_errors', 10, 3);

if (!function_exists('damncute_get_breed_options_for_forminator')) {
    function damncute_get_breed_options_for_forminator(string $breed_type, string $species_slug): array
    {
        static $breeds_cache = null;
        static $species_ids = [];

        if ($breeds_cache === null) {
            $breeds_cache = get_terms([
                'taxonomy' => 'breed',
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC',
            ]);
        }

        if (is_wp_error($breeds_cache) || empty($breeds_cache)) {
            return [];
        }

        if ($species_slug !== '' && !array_key_exists($species_slug, $species_ids)) {
            $term = get_term_by('slug', $species_slug, 'species');
            $species_ids[$species_slug] = $term && !is_wp_error($term) ? (int) $term->term_id : 0;
        }

        $species_id = $species_ids[$species_slug] ?? 0;
        $options = [];

        foreach ($breeds_cache as $breed) {
            $term_id = (int) $breed->term_id;
            $type_raw = (string) get_term_meta($term_id, '_damncute_breed_type', true);
            $type = function_exists('damncute_normalize_breed_type')
                ? damncute_normalize_breed_type($type_raw)
                : sanitize_key($type_raw);
            if ($type !== $breed_type) {
                continue;
            }

            if ($species_id) {
                $allowed = (array) get_term_meta($term_id, '_damncute_breed_species', true);
                $allowed_ids = array_map('intval', $allowed);
                if (!empty($allowed_ids) && !in_array($species_id, $allowed_ids, true)) {
                    continue;
                }
            }

            $key = function_exists('forminator_unique_key') ? forminator_unique_key() : uniqid('dc_breed_', true);
            $options[] = [
                'label' => (string) $breed->name,
                'value' => (string) $breed->slug,
                'limit' => '',
                'key' => $key,
            ];
        }

        return $options;
    }
}

if (!function_exists('damncute_populate_breed_forminator_fields')) {
    function damncute_populate_breed_forminator_fields(array $wrappers, int $form_id): array
    {
        $target_form_id = (int) get_option('damncute_forminator_id', 39);
        if ($form_id !== $target_form_id) {
            return $wrappers;
        }

        $field_map = [
            'select-6' => ['type' => 'akc', 'species' => 'dog'],
            'select-7' => ['type' => 'designer', 'species' => 'dog'],
            'select-8' => ['type' => 'purebred', 'species' => 'cat'],
            'select-9' => ['type' => 'mixed', 'species' => 'cat'],
        ];

        foreach ($wrappers as $wrapper_index => $wrapper) {
            if (empty($wrapper['fields']) || !is_array($wrapper['fields'])) {
                continue;
            }

            foreach ($wrapper['fields'] as $field_index => $field) {
                if (!is_array($field)) {
                    continue;
                }

                $element_id = isset($field['element_id']) ? (string) $field['element_id'] : '';
                if (!isset($field_map[$element_id])) {
                    continue;
                }

                $map = $field_map[$element_id];
                $options = damncute_get_breed_options_for_forminator($map['type'], $map['species']);
                $wrappers[$wrapper_index]['fields'][$field_index]['options'] = $options;
            }
        }

        return $wrappers;
    }
}
add_filter('forminator_cform_render_fields', 'damncute_populate_breed_forminator_fields', 10, 2);

if (!function_exists('damncute_register_proxy_route')) {
    function damncute_register_proxy_route(): void {
        register_rest_route('damncute/v1', '/proxy-image', [
            'methods' => 'GET',
            'callback' => 'damncute_handle_proxy_image',
            'permission_callback' => '__return_true', // Public endpoint
        ]);
    }
}
add_action('rest_api_init', 'damncute_register_proxy_route');

if (!function_exists('damncute_handle_proxy_image')) {
    function damncute_handle_proxy_image(WP_REST_Request $request) {
        $image_id = absint($request->get_param('id'));
        if (!$image_id) {
            return new WP_Error('missing_id', 'Image ID required', ['status' => 400]);
        }

        $url = wp_get_attachment_image_url($image_id, 'large'); // Fetch official URL
        if (!$url) {
             return new WP_Error('invalid_image', 'Image not found', ['status' => 404]);
        }

        $parsed = wp_parse_url($url);
        $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
        $uploads = wp_get_upload_dir();
        $uploads_path = $uploads['baseurl'] ? wp_parse_url($uploads['baseurl'], PHP_URL_PATH) : '';
        $url_path = $parsed['path'] ?? '';

        if (($parsed['host'] ?? '') !== $site_host || ($uploads_path && strpos($url_path, $uploads_path) !== 0)) {
            return new WP_Error('invalid_image_url', 'Image URL not allowed', ['status' => 400]);
        }

        if (!wp_attachment_is_image($image_id)) {
            return new WP_Error('invalid_image_type', 'Image must be an image', ['status' => 400]);
        }

        // Fetch image content safely
        $response = wp_safe_remote_get($url, ['timeout' => 10]);
        if (is_wp_error($response)) {
            return $response;
        }

        $content_type = wp_remote_retrieve_header($response, 'content-type');
        $body = wp_remote_retrieve_body($response);
        if ($content_type && strpos($content_type, 'image/') !== 0) {
            return new WP_Error('invalid_content_type', 'Unexpected content type', ['status' => 400]);
        }

        return new WP_REST_Response($body, 200, [
            'Access-Control-Allow-Origin' => '*',
            'Content-Type' => $content_type ?: 'image/*',
        ]);
    }
}

if (!function_exists('damncute_register_reaction_routes')) {
    function damncute_register_reaction_routes(): void
    {
        register_rest_route('damncute/v1', '/reaction/(?P<post_id>\d+)', [
            'methods' => 'POST',
            'callback' => 'damncute_handle_reaction',
            'permission_callback' => 'damncute_reaction_permission',
        ]);
    }
}
add_action('rest_api_init', 'damncute_register_reaction_routes');

if (!function_exists('damncute_reaction_permission')) {
    function damncute_reaction_permission(WP_REST_Request $request): bool
    {
        $nonce = (string) $request->get_header('X-WP-Nonce');
        if ($nonce === '') {
            return false;
        }

        return (bool) wp_verify_nonce($nonce, 'wp_rest');
    }
}

if (!function_exists('damncute_handle_reaction')) {
    function damncute_handle_reaction(WP_REST_Request $request)
    {
        $post_id = absint((string) $request['post_id']);
        if (!$post_id || get_post_type($post_id) !== 'pets') {
            return new WP_Error('damncute_invalid_post', __('Invalid pet.', 'damncute'), ['status' => 404]);
        }

        $reaction = sanitize_key((string) $request->get_param('reaction'));
        $map = damncute_reaction_map();
        if (!isset($map[$reaction])) {
            return new WP_Error('damncute_invalid_reaction', __('Invalid reaction.', 'damncute'), ['status' => 400]);
        }

        $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($client_ip !== '') {
            $rate_key = sprintf('dc_react_%d_%s', $post_id, md5($client_ip));
            $count = (int) get_transient($rate_key);
            if ($count >= 10) {
                return new WP_Error('damncute_rate_limited', __('Slow down.', 'damncute'), ['status' => 429]);
            }
            set_transient($rate_key, $count + 1, MINUTE_IN_SECONDS);
        }

        $meta_key = 'reaction_' . $reaction;
        $count = (int) get_post_meta($post_id, $meta_key, true);
        $count++;
        update_post_meta($post_id, $meta_key, $count);

        $counts = damncute_reaction_counts($post_id);
        update_post_meta($post_id, 'reaction_total', $counts['total']);

        return rest_ensure_response([
            'counts' => $counts,
        ]);
    }
}

if (!function_exists('damncute_pet_of_day_shortcode')) {
    function damncute_pet_of_day_shortcode(): string
    {
        $transient_key = 'damncute_pet_of_day_v2';
        $selected_id = absint(get_option('damncute_pet_of_day_id', 0));
        $post_id = $selected_id && get_post_status($selected_id) === 'publish'
            ? $selected_id
            : get_transient($transient_key);

        if (false === $post_id) {
            $query = new WP_Query([
                'post_type' => 'pets',
                'posts_per_page' => 1,
                'post_status' => 'publish',
                'meta_key' => 'reaction_total',
                'orderby' => 'meta_value_num',
                'order' => 'DESC',
                'date_query' => [
                    [
                        'after' => '1 week ago',
                    ],
                ],
            ]);

            if ($query->have_posts()) {
                $post_id = $query->posts[0]->ID;
                set_transient($transient_key, $post_id, HOUR_IN_SECONDS * 12);
            } else {
                // Fallback to random if no votes yet
                $fallback = get_posts(['post_type' => 'pets', 'numberposts' => 1, 'orderby' => 'rand']);
                $post_id = !empty($fallback) ? $fallback[0]->ID : 0;
            }
        }

        if (!$post_id) {
            return '';
        }

        // Render Card with "Hero" modifier
        $title = get_the_title($post_id);
        $permalink = get_permalink($post_id);
        $image = get_the_post_thumbnail($post_id, 'large', ['class' => 'dc-card__media']);
        $excerpt = get_the_excerpt($post_id);
        
        // Manual HTML construction to match the "Feature Card" look
        $html = sprintf(
            '<div class="dc-card dc-card--feature">
                <div class="dc-card__media"><a href="%s">%s</a></div>
                <div class="dc-card__body">
                    <div class="dc-card__meta" style="color:var(--dc-accent); margin-bottom:0.5rem; font-weight:700;">PET OF THE DAY</div>
                    <h3 class="dc-card__title" style="font-size:2rem;"><a href="%s">%s</a></h3>
                    <div class="dc-card__excerpt">%s</div>
                </div>
            </div>',
            esc_url($permalink),
            $image,
            esc_url($permalink),
            esc_html($title),
            esc_html($excerpt)
        );

        return $html;
    }
}
add_shortcode('damncute_pet_of_day', 'damncute_pet_of_day_shortcode');

if (!function_exists('damncute_register_infinite_scroll')) {
    function damncute_register_infinite_scroll(): void {
        register_rest_route('damncute/v1', '/page/(?P<page>\d+)', [
            'methods' => 'GET',
            'callback' => 'damncute_get_page_html',
            'permission_callback' => '__return_true',
        ]);
    }
}
add_action('rest_api_init', 'damncute_register_infinite_scroll');

if (!function_exists('damncute_get_page_html')) {
    function damncute_get_page_html(WP_REST_Request $request) {
        $page = absint($request['page']);
        if ($page < 1) { $page = 1; }

        $args = [
            'post_type' => 'pets',
            'post_status' => 'publish',
            'paged' => $page,
            'posts_per_page' => 12,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        // Handle filters (passed as query params ?species=X&vibe=Y)
        $tax_query = [];
        $species = sanitize_text_field($request->get_param('species') ?? '');
        $vibe = sanitize_text_field($request->get_param('vibe') ?? '');

        if ($species) {
            $tax_query[] = ['taxonomy' => 'species', 'field' => 'slug', 'terms' => $species];
        }
        if ($vibe) {
            $tax_query[] = ['taxonomy' => 'vibe', 'field' => 'slug', 'terms' => $vibe];
        }
        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $args['tax_query'] = $tax_query;
        }

        $query = new WP_Query($args);
        $html = '';

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $permalink = get_permalink();
                $title = get_the_title();
                $image = get_the_post_thumbnail($post_id, 'large', ['class' => 'dc-card__media']);
                
                // Replicate the standard card markup
                $html .= sprintf(
                    '<div class="dc-card dc-card--anim"><div class="dc-card__media"><a href="%s">%s</a></div><div class="dc-card__body"><h3 class="dc-card__title"><a href="%s">%s</a></h3></div></div>',
                    esc_url($permalink),
                    $image,
                    esc_url($permalink),
                    esc_html($title)
                );
            }
            wp_reset_postdata();
        }

        return rest_ensure_response([
            'html' => $html,
            'has_next' => $page < $query->max_num_pages,
        ]);
    }
}

if (!function_exists('damncute_pet_of_day_shortcode')) {
    function damncute_pet_of_day_shortcode(): string
    {
        $transient_key = 'damncute_pet_of_day_v2';
        $selected_id = absint(get_option('damncute_pet_of_day_id', 0));
        $post_id = $selected_id && get_post_status($selected_id) === 'publish'
            ? $selected_id
            : get_transient($transient_key);

        if (false === $post_id) {
            $query = new WP_Query([
                'post_type' => 'pets',
                'posts_per_page' => 1,
                'post_status' => 'publish',
                'meta_key' => 'reaction_total',
                'orderby' => 'meta_value_num',
                'order' => 'DESC',
                'date_query' => [
                    [
                        'after' => '1 week ago',
                    ],
                ],
            ]);

            if ($query->have_posts()) {
                $post_id = $query->posts[0]->ID;
                set_transient($transient_key, $post_id, HOUR_IN_SECONDS * 12);
            } else {
                // Fallback to random if no votes yet
                $fallback = get_posts(['post_type' => 'pets', 'numberposts' => 1, 'orderby' => 'rand']);
                $post_id = !empty($fallback) ? $fallback[0]->ID : 0;
            }
        }

        if (!$post_id) {
            return '';
        }

        // Render Card with "Hero" modifier
        $title = get_the_title($post_id);
        $permalink = get_permalink($post_id);
        $image = get_the_post_thumbnail($post_id, 'large', ['class' => 'dc-card__media']);
        $excerpt = get_the_excerpt($post_id);
        
        // Manual HTML construction to match the "Feature Card" look
        $html = sprintf(
            '<div class="dc-card dc-card--feature">
                <div class="dc-card__media"><a href="%s">%s</a></div>
                <div class="dc-card__body">
                    <div class="dc-card__meta" style="color:var(--dc-accent); margin-bottom:0.5rem; font-weight:700;">PET OF THE DAY</div>
                    <h3 class="dc-card__title" style="font-size:2rem;"><a href="%s">%s</a></h3>
                    <div class="dc-card__excerpt">%s</div>
                </div>
            </div>',
            esc_url($permalink),
            $image,
            esc_url($permalink),
            esc_html($title),
            esc_html($excerpt)
        );

        return $html;
    }
}
add_shortcode('damncute_pet_of_day', 'damncute_pet_of_day_shortcode');
