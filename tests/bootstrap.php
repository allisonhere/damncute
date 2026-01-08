<?php

declare(strict_types=1);

$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

if (!file_exists($_tests_dir . '/includes/functions.php')) {
    fwrite(STDERR, "WP test suite not found in {$_tests_dir}\n");
    exit(1);
}

require_once $_tests_dir . '/includes/functions.php';

tests_add_filter('muplugins_loaded', static function (): void {
    require_once __DIR__ . '/../functions.php';
});

require_once $_tests_dir . '/includes/bootstrap.php';
