<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

return [
    'finders' => [
        Finder::create()->files()->in(__DIR__ . '/src'),
    ],
    'exclude-functions' => [
        'add_action',
        'add_filter',
        'register_activation_hook',
        'register_deactivation_hook',
        'register_uninstall_hook',
        'plugin_dir_path',
        'plugins_url',
    ],
    'exclude-classes' => [
        'WP_Error',
        'WP_Post',
    ],
    'exclude-constants' => [
        'ABSPATH',
        'WP_PLUGIN_DIR',
        'WP_CONTENT_DIR',
        'WP_DEBUG',
    ],
];
