<?php

declare(strict_types=1);

$prefix = getenv('COMPOSEPRESS_SCOPE_PREFIX') ?: 'ComposePressScoped';
$buildDirectory = __DIR__ . '/../build';
$files = glob($buildDirectory . '/*.php') ?: [];
sort($files);

if ($files === []) {
    fwrite(STDERR, "Scoped build is missing. Run composer scope first.\n");
    exit(1);
}

$forbidden = [
    $prefix . '\\add_action',
    $prefix . '\\add_filter',
    $prefix . '\\register_activation_hook',
    $prefix . '\\register_deactivation_hook',
    $prefix . '\\register_uninstall_hook',
    $prefix . '\\plugin_dir_path',
    $prefix . '\\plugins_url',
];

foreach ($files as $file) {
    $contents = file_get_contents($file);
    if ($contents === false) {
        fwrite(STDERR, "Unable to read {$file}.\n");
        exit(1);
    }

    if (!str_contains($contents, 'namespace ' . $prefix . '\\ComposePress\\Core;')) {
        fwrite(STDERR, "Missing scoped namespace in {$file}.\n");
        exit(1);
    }

    foreach ($forbidden as $symbol) {
        if (str_contains($contents, $symbol)) {
            fwrite(STDERR, "WordPress symbol was scoped in {$file}: {$symbol}.\n");
            exit(1);
        }
    }
}

printf("Verified %d scoped files.\n", count($files));
