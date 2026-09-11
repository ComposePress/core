<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Integration;

use ComposePress\Core\WordPressHooks;
use PHPUnit\Framework\TestCase;

final class WordPressHooksIntegrationTest extends TestCase
{
    public function testRemovedActionDoesNotReachCallback(): void
    {
        $received = false;
        $callback = static function () use (&$received): void {
            $received = true;
        };
        $hooks = new WordPressHooks();
        $hooks->action('composepress_core_removal_test', $callback);

        self::assertTrue($hooks->removeAction('composepress_core_removal_test', $callback));
        do_action('composepress_core_removal_test');

        self::assertFalse($received);
    }

    public function testActionReachesRegisteredCallback(): void
    {
        $received = null;
        $hooks = new WordPressHooks();
        $hooks->action('composepress_core_test', static function (string $value) use (&$received): void {
            $received = $value;
        }, arguments: 1);

        do_action('composepress_core_test', 'verified');

        self::assertSame('verified', $received);
    }
}
