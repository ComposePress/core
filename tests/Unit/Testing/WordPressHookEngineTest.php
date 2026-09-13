<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit\Testing;

use ComposePress\Core\Testing\WordPressHookEngine;
use PHPUnit\Framework\TestCase;
use WP_Hook;

final class WordPressHookEngineTest extends TestCase
{
    /** WP_Hook loaded through the vendored copy discovered at var/wordpress. */
    public function testEnsureLoadedProvidesRealEngine(): void
    {
        WordPressHookEngine::ensureLoaded();

        self::assertTrue(class_exists(WP_Hook::class));
    }

    /** A prior test bootstrap that preloaded only class-wp-hook.php must still receive the global shim. */
    public function testEnsureLoadedProvidesUniqueIdHelperWhenClassAlreadyLoaded(): void
    {
        WordPressHookEngine::ensureLoaded();

        self::assertTrue(function_exists('_wp_filter_build_unique_id'));
    }

    /** Without the installer plugin, composer's fallback installer keeps roots/wordpress-no-content inside the vendor directory. */
    public function testLocatesWordPressInVendorFallbackPath(): void
    {
        self::withFixture(static function (string $root): void {
            $testingDir = $root . '/vendor/composepress/core/src/Testing';
            mkdir($testingDir, 0777, true);
            mkdir($root . '/vendor/roots/wordpress-no-content/wp-includes', 0777, true);

            self::assertDiscovers($root . '/vendor/roots/wordpress-no-content', $testingDir);
        });
    }

    /** Composer's installed manifest lists the actual install path, whatever installer plugin or vendor-dir layout put it there. */
    public function testLocatesWordPressFromInstalledManifest(): void
    {
        self::withFixture(static function (string $root): void {
            $testingDir = $root . '/shared/vendor/composepress/core/src/Testing';
            mkdir($testingDir, 0777, true);
            mkdir($root . '/shared/vendor/composer', 0777, true);
            mkdir($root . '/wordpress/wp-includes', 0777, true);
            file_put_contents(
                $root . '/shared/vendor/composer/installed.json',
                '{"packages":[{"name":"roots/wordpress-no-content","install-path":"../../../wordpress"}]}',
            );

            self::assertDiscovers($root . '/wordpress', $testingDir);
        });
    }

    /** Composer 1 manifests record no install paths; the package defaults to vendor/<name>. */
    public function testLocatesWordPressFromComposerOneManifest(): void
    {
        self::withFixture(static function (string $root): void {
            $testingDir = $root . '/shared/vendor/composepress/core/src/Testing';
            mkdir($testingDir, 0777, true);
            mkdir($root . '/shared/vendor/composer', 0777, true);
            $package = $root . '/shared/vendor/roots/wordpress-no-content';
            mkdir($package . '/wp-includes', 0777, true);
            file_put_contents(
                $root . '/shared/vendor/composer/installed.json',
                '{"packages":[{"name":"roots/wordpress-no-content","version":"7.1.0"}]}',
            );

            self::assertDiscovers($package, $testingDir);
        });
    }

    /** The consumer's root manifest may configure the installer destination. */
    public function testLocatesWordPressFromConfiguredInstallDir(): void
    {
        self::withFixture(static function (string $root): void {
            $testingDir = $root . '/app/vendor/composepress/core/src/Testing';
            mkdir($testingDir, 0777, true);
            mkdir($root . '/web/wp/wp-includes', 0777, true);
            file_put_contents(
                $root . '/composer.json',
                '{"extra":{"wordpress-install-dir":"web/wp"}}',
            );

            self::assertDiscovers($root . '/web/wp', $testingDir);
        });
    }

    /**
     * A consumer with a relocated vendor directory (composer config.vendor-dir)
     * and a nameless manifest — both valid for Composer root projects — must
     * still resolve its project root for the installer-default path.
     */
    public function testLocatesProjectRootThroughRelocatedVendorDirectory(): void
    {
        self::withFixture(static function (string $root): void {
            $testingDir = $root . '/app/vendor/composepress/core/src/Testing';
            mkdir($testingDir, 0777, true);
            mkdir($root . '/wordpress/wp-includes', 0777, true);
            file_put_contents($root . '/composer.json', '{}');

            self::assertDiscovers($root . '/wordpress', $testingDir);
        });
    }

    /**
     * A vendor directory outside the project is invisible to the
     * package-relative walk, so discovery must also anchor on the process
     * working directory, the directory the test runner starts in.
     */
    public function testLocatesRunnerRootFromProcessWorkingDirectory(): void
    {
        self::withFixture(static function (string $root): void {
            mkdir($root . '/wordpress/wp-includes', 0777, true);
            file_put_contents($root . '/composer.json', '{}');

            $previousCwd = getcwd();

            try {
                chdir($root);

                $locate = new \ReflectionMethod(WordPressHookEngine::class, 'locateRootByManifest');

                self::assertSame($root, $locate->invoke(null, (string) getcwd(), true));
            } finally {
                if ($previousCwd !== false) {
                    chdir($previousCwd);
                }
            }
        });
    }

    /** A destination on another Windows drive is recorded as an absolute path without a leading slash. */
    public function testWindowsDrivePathsCountAsAbsolute(): void
    {
        $locate = new \ReflectionMethod(WordPressHookEngine::class, 'isAbsolutePath');

        self::assertTrue($locate->invoke(null, '/usr/share/wordpress'));
        self::assertTrue($locate->invoke(null, 'D:/wordpress'));
        self::assertTrue($locate->invoke(null, 'E:\\wordpress'));
        self::assertFalse($locate->invoke(null, 'wordpress'));
        self::assertFalse($locate->invoke(null, 'C:wordpress'));
    }

    /** Runs a test against a throwaway fixture root that is always removed. */
    private static function withFixture(callable $run): void
    {
        $root = sys_get_temp_dir() . '/composepress-engine-' . uniqid();

        try {
            $run($root);
        } finally {
            self::destroyDirectory($root);
        }
    }

    private static function assertDiscovers(string $expected, string $testingDir): void
    {
        $locate = new \ReflectionMethod(WordPressHookEngine::class, 'locateWordPressCore');

        self::assertSame($expected, $locate->invoke(null, $testingDir));
    }

    private static function destroyDirectory(string $root): void
    {
        foreach (
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            ) as $item
        ) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($root);
    }
}
