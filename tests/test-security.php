<?php

declare(strict_types=1);

use DamnCute\Pet_Submission_Handler;

final class DamnCute_Security_Test extends WP_UnitTestCase {
    private int $image_id = 0;

    public function setUp(): void {
        parent::setUp();
        $this->image_id = $this->create_test_image_attachment();
    }

    public function tearDown(): void {
        if ($this->image_id) {
            wp_delete_attachment($this->image_id, true);
        }
        parent::tearDown();
    }

    public function test_proxy_allows_valid_upload_image(): void {
        $request = new WP_REST_Request('GET', '/damncute/v1/proxy-image');
        $request->set_param('id', $this->image_id);

        $response = damncute_handle_proxy_image($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $this->assertStringStartsWith('image/', $response->get_header('Content-Type'));
        $this->assertNotEmpty($response->get_data());
    }

    public function test_proxy_rejects_non_image_attachment(): void {
        $attachment_id = $this->create_test_text_attachment();

        $request = new WP_REST_Request('GET', '/damncute/v1/proxy-image');
        $request->set_param('id', $attachment_id);

        $response = damncute_handle_proxy_image($request);

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertSame('invalid_image_type', $response->get_error_code());
    }

    public function test_proxy_rejects_external_url_override(): void {
        $image_id = $this->image_id;
        $filter = static function ($url, $attachment_id) use ($image_id) {
            return $attachment_id === $image_id ? 'https://example.com/evil.png' : $url;
        };

        add_filter('wp_get_attachment_image_url', $filter, 10, 2);

        $request = new WP_REST_Request('GET', '/damncute/v1/proxy-image');
        $request->set_param('id', $this->image_id);

        $response = damncute_handle_proxy_image($request);

        remove_filter('wp_get_attachment_image_url', $filter, 10);

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertSame('invalid_image_url', $response->get_error_code());
    }

    public function test_uploads_reject_outside_uploads_dir(): void {
        $handler = new Pet_Submission_Handler();
        $tmp = tempnam(sys_get_temp_dir(), 'dc');
        file_put_contents($tmp, 'nope');

        $attachment_id = $this->call_private_method($handler, 'insert_attachment', [$tmp, '', 0]);

        $this->assertSame(0, $attachment_id);
        @unlink($tmp);
    }

    public function test_uploads_reject_invalid_mime(): void {
        $uploads = wp_get_upload_dir();
        $path = $uploads['basedir'] . '/dc-test.txt';
        file_put_contents($path, 'nope');

        $handler = new Pet_Submission_Handler();
        $attachment_id = $this->call_private_method($handler, 'insert_attachment', [$path, '', 0]);

        $this->assertSame(0, $attachment_id);
        @unlink($path);
    }

    public function test_uploads_accept_image_in_uploads_dir(): void {
        $uploads = wp_get_upload_dir();
        $path = $uploads['basedir'] . '/dc-test.png';
        file_put_contents($path, $this->small_png_bytes());

        $handler = new Pet_Submission_Handler();
        $attachment_id = $this->call_private_method($handler, 'insert_attachment', [$path, '', 0]);

        $this->assertGreaterThan(0, $attachment_id);
        wp_delete_attachment($attachment_id, true);
    }

    private function create_test_image_attachment(): int {
        $uploads = wp_upload_bits('dc-test.png', null, $this->small_png_bytes());

        $attachment_id = wp_insert_attachment([
            'post_mime_type' => 'image/png',
            'post_title' => 'dc-test',
            'post_content' => '',
            'post_status' => 'inherit',
        ], $uploads['file']);

        require_once ABSPATH . 'wp-admin/includes/image.php';
        $metadata = wp_generate_attachment_metadata($attachment_id, $uploads['file']);
        wp_update_attachment_metadata($attachment_id, $metadata);

        return (int) $attachment_id;
    }

    private function create_test_text_attachment(): int {
        $uploads = wp_upload_bits('dc-test.txt', null, 'nope');

        return (int) wp_insert_attachment([
            'post_mime_type' => 'text/plain',
            'post_title' => 'dc-test-text',
            'post_content' => '',
            'post_status' => 'inherit',
        ], $uploads['file']);
    }

    private function small_png_bytes(): string {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAAWgmWQ0AAAAASUVORK5CYII=',
            true
        ) ?: '';
    }

    private function call_private_method(object $instance, string $method, array $args) {
        $ref = new ReflectionMethod($instance, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($instance, $args);
    }
}
