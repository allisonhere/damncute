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
        wp_localize_script('damncute-theme', 'damncuteData', [
            'restUrl' => esc_url_raw(rest_url('damncute/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
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
        $image = get_the_post_thumbnail($post_id, 'medium', ['class' => 'dc-card__media']);
        $meta_html = $meta ? sprintf('<div class="dc-card__meta">%s</div>', esc_html($meta)) : '';

        return sprintf(
            '<div class="dc-card dc-card--compact">%s<h3 class="dc-card__title"><a href="%s">%s</a></h3>%s</div>',
            $image !== '' ? sprintf('<a href="%s">%s</a>', esc_url($permalink), $image) : '',
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
            '<div class="dc-social" data-share-text="%s" data-share-url="%s"><div class="dc-social__row">%s<div class="dc-reactions" data-reaction-group data-post-id="%d">%s</div></div><div class="dc-share-row"><span class="dc-share__label">%s</span><button class="dc-share-button" type="button" data-share-platform="x">X</button><button class="dc-share-button" type="button" data-share-platform="facebook">Facebook</button><button class="dc-share-button" type="button" data-share-platform="instagram">IG</button><button class="dc-share-button" type="button" data-share-platform="tiktok">TikTok</button><button class="dc-share-button" type="button" data-share-platform="copy">%s</button></div></div>',
            esc_attr($share_text),
            esc_url($share_url),
            $submitter,
            $post_id,
            $reaction_buttons,
            esc_html__('Share', 'damncute'),
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