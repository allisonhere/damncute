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
            'featured_image'   => 'upload-2', // New dedicated field
            'gallery'          => 'upload-1', // Existing multi-upload field
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
        $this->set_terms($post_id, 'breed', $field_data_array, $map['breed']);
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
            // Check if already attached (Forminator might return URL for existing media)
            $attachment_id = $url !== '' ? attachment_url_to_postid($url) : 0;
            
            if (!$attachment_id) {
                $path = (string) ($file_paths[$index] ?? '');
                $attachment_id = $this->insert_attachment($path, $url, $post_id);
            }
            
            if ($attachment_id) {
                $ids[] = (int) $attachment_id;
            }
        }

        return $ids;
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
        if ($mime === '' || !wp_match_mime_types('image|video', $mime)) {
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
