<?php

declare(strict_types=1);

$buildDirectory = __DIR__ . '/../build';
$files = glob($buildDirectory . '/*.php') ?: [];
sort($files);

if ($files === []) {
    fwrite(STDERR, "Scoped build is missing. Run composer scope first.\n");
    exit(1);
}

$forbidden = [
    'ComposePressScoped\\add_action',
    'ComposePressScoped\\add_filter',
    'ComposePressScoped\\register_activation_hook',
    'ComposePressScoped\\register_deactivation_hook',
    'ComposePressScoped\\register_uninstall_hook',
    'ComposePressScoped\\add_option',
    'ComposePressScoped\\delete_option',
    'ComposePressScoped\\get_option',
    'ComposePressScoped\\update_option',
    'ComposePressScoped\\wp_cache_delete',
    'ComposePressScoped\\plugin_dir_path',
    'ComposePressScoped\\plugins_url',
];

foreach ($files as $file) {
    $contents = file_get_contents($file);
    if ($contents === false) {
        fwrite(STDERR, "Unable to read {$file}.\n");
        exit(1);
    }

    if (!str_contains($contents, 'namespace ComposePressScoped\\ComposePress\\Core;')) {
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
