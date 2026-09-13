<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Integration;

use ComposePress\Core\Plugin;
use ComposePress\Core\PluginContext;
use ComposePress\Core\Testing\RecordingActivator;
use ComposePress\Core\Testing\RecordingDeactivator;
use ComposePress\Core\Testing\SpyingUninstall;
use PHPUnit\Framework\TestCase;

final class LifecycleIntegrationTest extends TestCase
{
    private string $pluginFile;

    protected function setUp(): void
    {
        $this->pluginFile = WP_PLUGIN_DIR . '/composepress-test/composepress-test.php';
        delete_option('uninstall_plugins');
        SpyingUninstall::reset();
    }

    public function testLifecycleCallbacksExecuteThroughWordPress(): void
    {
        $plugin = new Plugin(
            new PluginContext($this->pluginFile, 'composepress-test', '1.0.0'),
            activator: $activator = new RecordingActivator(),
            deactivator: $deactivator = new RecordingDeactivator(),
            uninstaller: SpyingUninstall::class,
        );

        $plugin->boot();
        do_action('activate_composepress-test/composepress-test.php', false);
        do_action('deactivate_composepress-test/composepress-test.php', true);

        self::assertSame([false], $activator->invocations());
        self::assertSame([true], $deactivator->invocations());

        $uninstall = get_option('uninstall_plugins')['composepress-test/composepress-test.php'];
        self::assertSame([SpyingUninstall::class, 'uninstall'], $uninstall);
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
