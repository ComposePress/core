<?php

declare(strict_types=1);

require_once __DIR__ . '/../tests/bootstrap.php';

$prefixes = ['ComposePressCompatA', 'ComposePressCompatB'];
foreach ($prefixes as $prefix) {
    $directory = strtolower(str_replace('ComposePressCompat', 'compat-', $prefix));
    spl_autoload_register(static function (string $class) use ($prefix, $directory): void {
        $prefixWithNamespace = $prefix . '\\';
        if (!str_starts_with($class, $prefixWithNamespace)) {
            return;
        }

        $relativeClass = substr($class, strlen($prefixWithNamespace));
        if (str_starts_with($relativeClass, 'ComposePress\\Core\\Tests\\')) {
            $relativePath = 'tests/' . str_replace('\\', '/', substr($relativeClass, 24));
        } elseif (str_starts_with($relativeClass, 'ComposePress\\Core\\')) {
            $relativePath = 'src/' . str_replace('\\', '/', substr($relativeClass, 18));
        } else {
            return;
        }

        $file = __DIR__ . '/../build/' . $directory . '/' . $relativePath . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    });
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
$hooksA->removeAction('composepress_scope_a', $callback);
$hooksB->removeFilter('composepress_scope_b', $callback);

if ($GLOBALS['composepress_test_hooks'] !== [
    ['action', 'composepress_scope_a', 10, 1],
    ['filter', 'composepress_scope_b', 10, 1],
    ['remove_action', 'composepress_scope_a', 10],
    ['remove_filter', 'composepress_scope_b', 10],
]) {
    throw new RuntimeException('Scoped hook adapters did not call global WordPress functions.');
}

$providerA = 'ComposePressCompatA\\ComposePress\\Core\\Tests\\Fixtures\\ScopedUninstallProvider';
$providerB = 'ComposePressCompatB\\ComposePress\\Core\\Tests\\Fixtures\\ScopedUninstallProvider';
if (!class_exists($providerA) || !class_exists($providerB)) {
    throw new RuntimeException('Scoped uninstall providers were not loaded.');
}
$uninstallerA = $providerA::className();
$uninstallerB = $providerB::className();
if ($uninstallerA !== 'ComposePressCompatA\\ComposePress\\Core\\Tests\\Fixtures\\ScopedUninstall'
    || $uninstallerB !== 'ComposePressCompatB\\ComposePress\\Core\\Tests\\Fixtures\\ScopedUninstall') {
    throw new RuntimeException('Scoped uninstall class constants were not rewritten.');
}

$pluginA = new ComposePressCompatA\ComposePress\Core\Plugin(
    new ComposePressCompatA\ComposePress\Core\PluginContext('/plugins/a/a.php', 'a', '1.0.0'),
    hooks: $hooksA,
    uninstaller: $uninstallerA,
);
$pluginB = new ComposePressCompatB\ComposePress\Core\Plugin(
    new ComposePressCompatB\ComposePress\Core\PluginContext('/plugins/b/b.php', 'b', '1.0.0'),
    hooks: $hooksB,
    uninstaller: $uninstallerB,
);
$pluginA->boot();
$pluginB->boot();

$lifecycle = $GLOBALS['composepress_test_lifecycle'];
if ($lifecycle[0][2] !== [$uninstallerA, 'uninstall']
    || $lifecycle[1][2] !== [$uninstallerB, 'uninstall']) {
    throw new RuntimeException('Scoped uninstall callbacks were not preserved.');
}

printf("Verified isolated prefixes for %d scoped runtimes.\n", count($prefixes));
