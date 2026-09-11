<?php

declare(strict_types=1);

$wordpressTests = getenv('WP_TESTS_DIR');
if ($wordpressTests === false || !is_file($wordpressTests . '/includes/functions.php')) {
    throw new RuntimeException('Set WP_TESTS_DIR to a configured WordPress test suite.');
}

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once $wordpressTests . '/includes/functions.php';
require_once $wordpressTests . '/includes/bootstrap.php';
