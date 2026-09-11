<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit;

use ComposePress\Core\Plugin;
use ComposePress\Core\PluginContext;
use ComposePress\Core\WordPressHooks;
use PHPUnit\Framework\TestCase;

final class WordPressHooksTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['composepress_test_hooks'] = [];
        $GLOBALS['composepress_test_lifecycle'] = [];
    }

    public function testDelegatesActionsAndFiltersToWordPress(): void
    {
        $hooks = new WordPressHooks();
        $callback = static function (): void {
        };

        $hooks->action('save_post', $callback, 20, 2);
        $hooks->filter('the_title', $callback, 5, 1);

        self::assertSame([
            ['action', 'save_post', 20, 2],
            ['filter', 'the_title', 5, 1],
        ], $GLOBALS['composepress_test_hooks']);
    }

    public function testRegistersInstanceLifecycleAndStaticUninstall(): void
    {
        $plugin = new Plugin(
            new PluginContext('/plugins/example/example.php', 'example', '1.0.0'),
            [],
            new RecordingLifecycle(),
            null,
            RecordingUninstall::class,
        );

        $plugin->boot();

        self::assertSame(
            ['activate', 'deactivate', 'uninstall'],
            array_column($GLOBALS['composepress_test_lifecycle'], 0),
        );
        self::assertSame(
            [RecordingUninstall::class, 'uninstall'],
            $GLOBALS['composepress_test_lifecycle'][2][2],
        );
    }

    public function testRejectsInvalidUninstallerClass(): void
    {
        $plugin = new Plugin(
            new PluginContext('/plugins/example/example.php', 'example', '1.0.0'),
            [],
            null,
            null,
            self::class,
        );

        $this->expectException(\InvalidArgumentException::class);
        $plugin->boot();
    }
}
