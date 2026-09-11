<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Integration;

use ComposePress\Core\Plugin;
use ComposePress\Core\PluginContext;
use PHPUnit\Framework\TestCase;

final class LifecycleIntegrationTest extends TestCase
{
    private string $pluginFile;

    protected function setUp(): void
    {
        $this->pluginFile = WP_PLUGIN_DIR . '/composepress-test/composepress-test.php';
        delete_option('uninstall_plugins');
        LifecycleRecorder::$events = [];
    }

    public function testLifecycleCallbacksExecuteThroughWordPress(): void
    {
        $plugin = new Plugin(
            new PluginContext($this->pluginFile, 'composepress-test', '1.0.0'),
            activator: new LifecycleRecorder(),
            deactivator: new LifecycleRecorder(),
            uninstaller: LifecycleRecorder::class,
        );

        $plugin->boot();
        do_action('activate_composepress-test/composepress-test.php', false);
        do_action('deactivate_composepress-test/composepress-test.php', true);

        self::assertSame(['activate:0', 'deactivate:1'], LifecycleRecorder::$events);
        self::assertSame(
            [LifecycleRecorder::class, 'uninstall'],
            get_option('uninstall_plugins')['composepress-test/composepress-test.php'],
        );
    }

    public function testContextResolvesWordPressPaths(): void
    {
        $context = new PluginContext($this->pluginFile, 'composepress-test', '1.0.0');

        self::assertSame(WP_PLUGIN_DIR . '/composepress-test/', $context->directory());
        self::assertSame(
            plugins_url('assets/app.js', $this->pluginFile),
            $context->url('assets/app.js'),
        );
    }
}
