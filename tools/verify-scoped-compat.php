<?php

declare(strict_types=1);

require_once __DIR__ . '/../tests/bootstrap.php';

$prefixes = ['ComposePressCompatA', 'ComposePressCompatB'];
foreach ($prefixes as $prefix) {
    $directory = strtolower(str_replace('ComposePressCompat', 'compat-', $prefix));
    $files = glob(__DIR__ . '/../build/' . $directory . '/*.php') ?: [];
    sort($files);
    foreach ($files as $file) {
        require_once $file;
    }
}

$classA = 'ComposePressCompatA\\ComposePress\\Core\\Plugin';
$classB = 'ComposePressCompatB\\ComposePress\\Core\\Plugin';
if (!class_exists($classA) || !class_exists($classB) || $classA === $classB) {
    throw new RuntimeException('Scoped ComposePress classes did not remain isolated.');
}

$hooksA = new ComposePressCompatA\ComposePress\Core\WordPressHooks();
$hooksB = new ComposePressCompatB\ComposePress\Core\WordPressHooks();
$callback = static function (): void {
};
$hooksA->action('composepress_scope_a', $callback);
$hooksB->filter('composepress_scope_b', $callback);

if ($GLOBALS['composepress_test_hooks'] !== [
    ['action', 'composepress_scope_a', 10, 1],
    ['filter', 'composepress_scope_b', 10, 1],
]) {
    throw new RuntimeException('Scoped hook adapters did not call global WordPress functions.');
}

eval('class CompatAUninstaller implements \\ComposePressCompatA\\ComposePress\\Core\\PluginUninstall { public static function uninstall(): void {} }');
eval('class CompatBUninstaller implements \\ComposePressCompatB\\ComposePress\\Core\\PluginUninstall { public static function uninstall(): void {} }');

$pluginA = new ComposePressCompatA\ComposePress\Core\Plugin(
    new ComposePressCompatA\ComposePress\Core\PluginContext('/plugins/a/a.php', 'a', '1.0.0'),
    hooks: $hooksA,
    uninstaller: CompatAUninstaller::class,
);
$pluginB = new ComposePressCompatB\ComposePress\Core\Plugin(
    new ComposePressCompatB\ComposePress\Core\PluginContext('/plugins/b/b.php', 'b', '1.0.0'),
    hooks: $hooksB,
    uninstaller: CompatBUninstaller::class,
);
$pluginA->boot();
$pluginB->boot();

$lifecycle = $GLOBALS['composepress_test_lifecycle'];
if ($lifecycle[0][2] !== [CompatAUninstaller::class, 'uninstall']
    || $lifecycle[1][2] !== [CompatBUninstaller::class, 'uninstall']) {
    throw new RuntimeException('Scoped uninstall callbacks were not preserved.');
}

printf("Verified isolated prefixes for %d scoped runtimes.\n", count($prefixes));
