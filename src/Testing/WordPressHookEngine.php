<?php

declare(strict_types=1);

namespace ComposePress\Core\Testing;

use WP_Hook;

/**
 * Loads the real WP_Hook engine the Testing fakes delegate to.
 *
 * The engine is discovered, not wired: real WordPress when the test process
 * runs inside it (the class is already loaded, or WP_CORE_DIR points at an
 * installation), otherwise a vendored roots/wordpress-no-content install in
 * the container root (package root, or the consuming project's root under
 * its default or configured installer path — including a relocated vendor
 * directory). Consumers need no bootstrap code of their own.
 */
final class WordPressHookEngine
{
    public static function ensureLoaded(): void
    {
        if (!class_exists(WP_Hook::class)) {
            $classFile = self::locateWordPressCore() . '/wp-includes/class-wp-hook.php';

            if (is_file($classFile)) {
                require_once $classFile;
            }

            if (!class_exists(WP_Hook::class)) {
                throw new \LogicException(
                    'RecordingHooks delegates to WP_Hook. Load WordPress, or add roots/wordpress-no-content as a dev dependency.',
                );
            }
        }

        // The shim must load even when WP_Hook was already loaded: a test
        // bootstrap requiring class-wp-hook.php alone does not bring
        // plugin.php. The guard keeps a real WordPress definition
        // authoritative; loading here instead of composer's autoload files
        // avoids a fatal redeclaration when plugin.php loads later.
        if (!function_exists('_wp_filter_build_unique_id')) {
            require_once __DIR__ . '/wp_hook_globals.php';
        }
    }

    private static function locateWordPressCore(string $testingDir = __DIR__): string
    {
        $packageDir = dirname($testingDir, 2);

        $candidates = array_values(array_filter([
            (string) getenv('WP_CORE_DIR'),
            // Composer's own install manifest is authoritative and immune to
            // relocated vendor directories and working directories.
            self::locateFromInstalledMetadata($packageDir),
            // Package root when testing core itself.
            $packageDir . '/var/wordpress',
            // Vendored dependency when the installed manifest is unreadable:
            // the suggested roots/wordpress-no-content required without this
            // dev-only roots/wordpress-core-installer is kept inside the
            // vendor directory by composer's fallback installer.
            dirname($packageDir, 2) . '/roots/wordpress-no-content',
        ]));

        // The consuming project's root, then the process's working directory
        // as a final anchor: a vendor directory configured outside the
        // project (composer config.vendor-dir accepts relative-outside or
        // absolute paths) is invisible to the package-relative walk, while
        // test runners start inside the project.
        foreach (self::composerRoots($packageDir) as $root) {
            $candidates[] = $root . '/wordpress';
            $candidates[] = $root . '/var/wordpress';
        }

        foreach ($candidates as $candidate) {
            if (is_dir($candidate . '/wp-includes')) {
                return (string) realpath($candidate);
            }
        }

        return '';
    }

    /**
     * The consuming project's root from composer.json boundaries, then the
     * working directory the runner was launched from. Both feed the same
     * installer-path candidates (the Roots installer's default wordpress/,
     * or var/wordpress when consumers adopt this package's extra entry;
     * extras from dependencies are not inherited by the root, hence the
     * default first).
     *
     * @return list<string>
     */
    private static function composerRoots(string $packageDir): array
    {
        return array_values(array_unique(array_filter([
            self::locateRootByManifest($packageDir, false),
            self::locateRootByManifest((string) (getcwd() ?: ''), true),
        ])));
    }

    /**
     * The WordPress package's actual install location, read from composer's
     * installed manifest. The manifest records every package's install path,
     * so no fixed filesystem layout is assumed: a vendor directory inside or
     * outside the project, an installer-plugin destination, or the fallback
     * in-vendor install all resolve the same way.
     */
    private static function locateFromInstalledMetadata(string $packageDir): string
    {
        $vendorDir = dirname($packageDir, 2);
        $installedFile = $vendorDir . '/composer/installed.json';

        if (!is_file($installedFile)) {
            return '';
        }

        $decoded = json_decode((string) file_get_contents($installedFile), true);

        if (!is_array($decoded)) {
            return '';
        }

        $entries = $decoded['packages'] ?? $decoded;

        if (!is_array($entries)) {
            return '';
        }

        foreach ($entries as $entry) {
            if (!is_array($entry) || !is_string($entry['install-path'] ?? null)) {
                continue;
            }

            // The manifest lives in vendor/composer/ and its relative
            // install paths are anchored there. Interpreting them against
            // the vendor root or the process cwd could land on an unrelated
            // WordPress checkout that happens to sit nearby.
            $installPath = $entry['install-path'];
            $installDir = self::isAbsolutePath($installPath) ? $installPath : $vendorDir . '/composer/' . $installPath;

            if (is_dir($installDir . '/wp-includes')) {
                // Install paths are relative; consumers expect a real path.
                return (string) realpath($installDir);
            }
        }

        return '';
    }

    /** Windows installs record destinations on other drives as D:/path, which is absolute despite lacking a leading slash. */
    private static function isAbsolutePath(string $path): bool
    {
        return $path !== '' && ($path[0] === '/' || preg_match('#^[A-Za-z]:[\\\\/]#', $path) === 1);
    }

    /**
     * The first directory at or above $from carrying its own composer.json.
     * When running inside WordPress the scan yields nothing (the package
     * checkout's composer.json sits below the start), but that case never
     * reaches here because WP_Hook is already loaded. Anchoring on manifest
     * boundaries handles a relocated vendor directory (composer
     * config.vendor-dir), where a fixed number of directory levels above
     * vendor/ would break.
     */
    private static function locateRootByManifest(string $from, bool $includeFrom): string
    {
        $dir = $includeFrom ? $from : dirname($from);

        while ($dir !== '' && $dir !== '/') {
            if (is_file($dir . '/composer.json')) {
                return $dir;
            }

            $parent = dirname($dir);

            if ($parent === $dir) {
                return '';
            }

            $dir = $parent;
        }

        return '';
    }
}
