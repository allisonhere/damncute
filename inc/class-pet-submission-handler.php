<?php

namespace DamnCute;

class Pet_Submission_Handler {

    /**
     * Map of internal meta keys to Forminator field IDs.
     * Defaults can be overridden via the 'damncute_submission_field_map' filter.
     *
     * @return array
     */
    private function get_field_map(): array {
        $defaults = [
            'pet_name'         => 'text-1',
            'cute_description' => 'textarea-1',
            'age'              => 'text-2',
            'owner_social'     => 'text-3',
            'about'            => 'textarea-2',
            'favorite_snack'   => 'textarea-3',
            'adoption_status'  => 'select-4',
            'species'          => 'select-1',
            'breed'            => 'select-2',
            'vibe'             => 'select-3',
            'breed_type'       => 'select-5',
            'breed_akc'        => 'select-6',
            'breed_designer'   => 'select-7',
            'breed_purebred'   => 'select-8',
            'breed_mixed'      => 'select-9',
            'featured_image'   => 'upload-1', // Required featured image field
            'gallery'          => 'upload-2', // Additional photos field
        ];

        return apply_filters('damncute_submission_field_map', $defaults);
    }

    /**
     * Handle the form submission.
     *
     * @param mixed $entry
     * @param int $form_id
     * @param array $field_data_array
     */
    public function handle_submission($entry, $form_id, array $field_data_array): void {
        $form_id = (int) $form_id;
        $target_form_id = (int) get_option('damncute_forminator_id', 39);
        if ($form_id !== $target_form_id) {
            return;
        }

        $map = $this->get_field_map();
        $pet_name = sanitize_text_field($this->get_text_value($field_data_array, $map['pet_name']));
        $cute_description = sanitize_textarea_field($this->get_text_value($field_data_array, $map['cute_description']));

        if ($pet_name === '' || $cute_description === '') {
            return;
        }

        $post_id = wp_insert_post([
            'post_type'      => 'pets',
            'post_title'     => $pet_name,
            'post_content'   => $cute_description,
            'post_status'    => 'pending',
            'comment_status' => 'open',
        ], true);

        if (is_wp_error($post_id)) {
            return;
        }

        // Meta Fields
        $meta_data = [
            'pet_name'         => $pet_name,
            'cute_description' => $cute_description,
            'age'              => sanitize_text_field($this->get_text_value($field_data_array, $map['age'])),
            'owner_social'     => sanitize_text_field($this->get_text_value($field_data_array, $map['owner_social'])),
            'about'            => sanitize_textarea_field($this->get_text_value($field_data_array, $map['about'])),
            'favorite_snack'   => sanitize_textarea_field($this->get_text_value($field_data_array, $map['favorite_snack'])),
        ];

        $species_labels = $this->get_select_labels($field_data_array, $map['species']);
        $species_slugs = array_map([$this, 'normalize_label'], $species_labels);
        $species_slugs = array_values(array_filter($species_slugs));

        $breed_field_id = $map['breed'];
        if (!empty($species_slugs)) {
            $allowed = $this->allowed_breed_types($species_slugs);
            $breed_field_map = [
                'akc' => $map['breed_akc'],
                'designer' => $map['breed_designer'],
                'purebred' => $map['breed_purebred'],
                'mixed' => $map['breed_mixed'],
            ];

            $breed_type = '';
            foreach ($breed_field_map as $type => $field_id) {
                if (in_array($type, $allowed, true) && !empty($this->get_select_labels($field_data_array, $field_id))) {
                    $breed_type = $type;
                    $breed_field_id = $field_id;
                    break;
                }
            }

            if ($breed_type === '') {
                $breed_type_labels = $this->get_select_labels($field_data_array, $map['breed_type']);
                $breed_type = $this->normalize_breed_type($breed_type_labels[0] ?? '');
            }

            if ($breed_type !== '' && in_array($breed_type, $allowed, true)) {
                $meta_data['breed_type'] = $breed_type;
            }
        }

        // Adoption Status (Single Select)
        $adoption_labels = $this->get_select_labels($field_data_array, $map['adoption_status']);
        $meta_data['adoption_status'] = $adoption_labels[0] ?? '';

        foreach ($meta_data as $key => $value) {
            if ($value !== '') {
                update_post_meta($post_id, $key, $value);
            }
        }

        // Taxonomies
        $this->set_terms($post_id, 'species', $field_data_array, $map['species']);
        $this->set_terms($post_id, 'breed', $field_data_array, $breed_field_id);
        $this->set_terms($post_id, 'vibe', $field_data_array, $map['vibe']);

        // Handle Uploads
        $featured_ids = $this->handle_uploads($field_data_array, $map['featured_image'], $post_id);
        $gallery_ids  = $this->handle_uploads($field_data_array, $map['gallery'], $post_id);
        
        $all_ids = array_unique(array_merge($featured_ids, $gallery_ids));

        if (!empty($all_ids)) {
            update_post_meta($post_id, 'gallery', $all_ids);
            
            // Set Featured Image: Try dedicated field first, fallback to first gallery image
            $thumbnail_id = !empty($featured_ids) ? $featured_ids[0] : (!empty($gallery_ids) ? $gallery_ids[0] : 0);
            
            if ($thumbnail_id && wp_attachment_is_image($thumbnail_id)) {
                set_post_thumbnail($post_id, $thumbnail_id);
            }
        }
    }

    private function find_field(array $field_data_array, string $field_id): ?array {
        foreach ($field_data_array as $field) {
            if (!empty($field['name']) && $field['name'] === $field_id) {
                return $field;
            }
        }
        return null;
    }

    private function get_text_value(array $field_data_array, string $field_id): string {
        $field = $this->find_field($field_data_array, $field_id);
        if (!$field || !isset($field['value']) || is_array($field['value'])) {
            return '';
        }
        return trim((string) $field['value']);
    }

    private function get_select_labels(array $field_data_array, string $field_id): array {
        $field = $this->find_field($field_data_array, $field_id);
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

        // Fallback if labels not found in options
        if (empty($labels)) {
            foreach ($selected as $value) {
                if ($value !== '') {
                    $labels[] = (string) $value;
                }
            }
        }

        return $labels;
    }

    private function allowed_breed_types(array $species_slugs): array {
        $allowed = [];
        if (in_array('dog', $species_slugs, true)) {
            $allowed[] = 'akc';
            $allowed[] = 'designer';
            $allowed[] = 'just-cute';
        }
        if (in_array('cat', $species_slugs, true)) {
            $allowed[] = 'purebred';
            $allowed[] = 'mixed';
            $allowed[] = 'just-cute';
        }
        return array_values(array_unique($allowed));
    }

    private function normalize_label(string $value): string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        return sanitize_title($value);
    }

    private function normalize_breed_type(string $value): string {
        $value = $this->normalize_label($value);
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

    private function set_terms(int $post_id, string $taxonomy, array $field_data_array, string $field_id): void {
        $labels = $this->get_select_labels($field_data_array, $field_id);
        if (empty($labels)) {
            return;
        }

        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
        if (is_wp_error($terms) || empty($terms)) {
            return;
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

        if (!empty($term_ids)) {
            wp_set_object_terms($post_id, array_values(array_unique($term_ids)), $taxonomy, false);
        }
    }

    private function handle_uploads(array $field_data_array, string $field_id, int $post_id): array {
        $field = $this->find_field($field_data_array, $field_id);
        if (!$field || !isset($field['value'])) {
            return [];
        }

        $files = $this->normalize_upload_items($field['value']);
        if (empty($files)) {
            return [];
        }

        $ids = [];
        foreach ($files as $file) {
            $url = isset($file['url']) ? (string) $file['url'] : '';
            // Check if already attached (Forminator might return URL for existing media)
            $attachment_id = $url !== '' ? attachment_url_to_postid($url) : 0;
            
            if (!$attachment_id) {
                $path = isset($file['path']) ? (string) $file['path'] : '';
                if ($path === '' && $url !== '') {
                    $path = $this->infer_upload_path($url);
                }
                $attachment_id = $this->insert_attachment($path, $url, $post_id);
            }
            
            if ($attachment_id) {
                $ids[] = (int) $attachment_id;
            }
        }

        return $ids;
    }

    private function normalize_upload_items($value): array {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        if (!is_array($value)) {
            return [];
        }

        if (isset($value['file'])) {
            $value = $value['file'];
        }

        if (isset($value['files'])) {
            $value = $value['files'];
        }

        if (!is_array($value)) {
            return [];
        }

        if ($this->is_list_array($value)) {
            $items = [];
            foreach ($value as $item) {
                if (is_string($item)) {
                    $items[] = ['url' => $item, 'path' => ''];
                    continue;
                }
                if (!is_array($item)) {
                    continue;
                }
                $items[] = [
                    'url' => (string) ($item['file_url'] ?? $item['url'] ?? ''),
                    'path' => (string) ($item['file_path'] ?? $item['path'] ?? ''),
                ];
            }
            return $items;
        }

        $urls = $value['file_url'] ?? $value['url'] ?? [];
        $paths = $value['file_path'] ?? $value['path'] ?? [];

        if (!is_array($urls)) {
            $urls = $urls !== '' ? [$urls] : [];
        }
        if (!is_array($paths)) {
            $paths = $paths !== '' ? [$paths] : [];
        }

        $max = max(count($urls), count($paths));
        $items = [];
        for ($index = 0; $index < $max; $index++) {
            $items[] = [
                'url' => isset($urls[$index]) ? (string) $urls[$index] : '',
                'path' => isset($paths[$index]) ? (string) $paths[$index] : '',
            ];
        }

        return array_values(array_filter($items, static function ($item): bool {
            return $item['url'] !== '' || $item['path'] !== '';
        }));
    }

    private function infer_upload_path(string $url): string {
        $uploads = wp_get_upload_dir();
        $baseurl = isset($uploads['baseurl']) ? (string) $uploads['baseurl'] : '';
        $basedir = isset($uploads['basedir']) ? (string) $uploads['basedir'] : '';

        if ($baseurl === '' || $basedir === '') {
            return '';
        }

        if (strpos($url, $baseurl) !== 0) {
            return '';
        }

        $relative = ltrim(substr($url, strlen($baseurl)), '/');
        if ($relative === '') {
            return '';
        }

        return rtrim($basedir, '/\\') . DIRECTORY_SEPARATOR . $relative;
    }

    private function is_list_array(array $value): bool {
        if ($value === []) {
            return true;
        }
        return array_keys($value) === range(0, count($value) - 1);
    }

    private function insert_attachment(string $path, string $url, int $post_id): int {
        if ($path === '' || !file_exists($path)) {
            return 0;
        }

        $real_path = realpath($path);
        $uploads = wp_get_upload_dir();
        $uploads_base = isset($uploads['basedir']) ? realpath($uploads['basedir']) : false;

        if (!$real_path || !$uploads_base) {
            return 0;
        }

        $uploads_base = rtrim($uploads_base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($real_path, $uploads_base) !== 0) {
            return 0;
        }

        $filetype = wp_check_filetype(basename($real_path), null);
        $mime = $filetype['type'] ?? '';
        $is_media = $mime !== '' && (strpos($mime, 'image/') === 0 || strpos($mime, 'video/') === 0);
        if (!$is_media) {
            return 0;
        }

        $attachment_id = wp_insert_attachment([
            'guid'           => $url !== '' ? $url : $path,
            'post_mime_type' => $mime,
            'post_title'     => preg_replace('/\.[^.]+$/', '', basename($real_path)),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ], $real_path, $post_id);

        if (is_wp_error($attachment_id)) {
            return 0;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        $metadata = wp_generate_attachment_metadata($attachment_id, $real_path);
        if ($metadata) {
            wp_update_attachment_metadata($attachment_id, $metadata);
        }

        return (int) $attachment_id;
    }
}
