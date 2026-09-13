<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Integration;

use ComposePress\Core\Tests\Fixtures\ExamplePluginFixture;
use ComposePress\Core\Testing\RecordingHooks;
use PHPUnit\Framework\TestCase;

/**
 * Pins RecordingHooks to the real WordPress plugin API: each scenario runs the
 * identical call sequence through WP functions and through the fake and then
 * compares both outcomes.
 */
final class RecordingHooksParityTest extends TestCase
{
    private RecordingHooks $hooks;

    protected function setUp(): void
    {
        $this->hooks = new RecordingHooks();
    }

    protected function tearDown(): void
    {
        remove_all_actions('composepress_parity_action');
        remove_all_filters('composepress_parity_filter');
    }

    public function testDuplicateRegistrationReplacesAndSingleRemovalClears(): void
    {
        $callback = function (): void {
        };

        $this->bothAdd('composepress_parity_action', $callback, 20);
        $this->bothAdd('composepress_parity_action', $callback, 20);

        $this->bothRemove('composepress_parity_action', $callback, 20, true);
        $this->bothRemove('composepress_parity_action', $callback, 20, false);
        $this->bothHas('composepress_parity_action', $callback, false);
    }

    public function testRemovalRequiresTheExactPriority(): void
    {
        $callback = function (): void {
        };

        $this->bothAdd('composepress_parity_action', $callback, 30);

        $this->bothRemove('composepress_parity_action', $callback, 10, false);
        $this->bothRemove('composepress_parity_action', $callback, 30, true);
        $this->bothHas('composepress_parity_action', $callback, false);
    }

    public function testStaticMethodCallbacksShareTheirUniqueId(): void
    {
        $arrayCallback = [ExamplePluginFixture::class, 'bootstrap'];
        $stringCallback = ExamplePluginFixture::class . '::bootstrap';

        $this->bothAdd('composepress_parity_action', $arrayCallback, 15);

        $this->bothHas('composepress_parity_action', $stringCallback, 15);
        $this->bothRemove('composepress_parity_action', $stringCallback, 15, true);
        $this->bothHas('composepress_parity_action', $arrayCallback, false);
    }

    public function testHasActionReturnsTheLowestPriorityBucket(): void
    {
        $callback = function (): void {
        };

        $this->bothAdd('composepress_parity_action', $callback, 30);
        $this->bothAdd('composepress_parity_action', $callback, 20);

        $this->bothHas('composepress_parity_action', $callback, 20);
        $this->bothRemove('composepress_parity_action', $callback, 20, true);
        $this->bothHas('composepress_parity_action', $callback, 30);
    }

    public function testPriorityZeroComparesStrictly(): void
    {
        $callback = function (): void {
        };

        $this->bothAdd('composepress_parity_action', $callback, 0);

        self::assertSame(0, has_action('composepress_parity_action', $callback));
        self::assertSame(0, $this->hooks->hasAction('composepress_parity_action', $callback));
    }

    public function testFiltersBehaveTheSameOnFilterHooks(): void
    {
        $callback = function (string $value): string {
            return $value;
        };

        $this->bothAdd('composepress_parity_filter', $callback, 12, true);
        $this->bothHas('composepress_parity_filter', $callback, 12);
        $this->bothRemove('composepress_parity_filter', $callback, 12, true);
        $this->bothHas('composepress_parity_filter', $callback, false);
    }

    private function bothAdd(string $hook, callable $callback, int $priority, bool $filter = false): void
    {
        if ($filter) {
            add_filter($hook, $callback, $priority);
            $this->hooks->filter($hook, $callback, $priority);

            return;
        }

        add_action($hook, $callback, $priority);
        $this->hooks->action($hook, $callback, $priority);
    }

    private function bothRemove(string $hook, callable $callback, int $priority, bool $expected, bool $filter = false): void
    {
        if ($filter) {
            self::assertSame($expected, remove_filter($hook, $callback, $priority));
            self::assertSame($expected, $this->hooks->removeFilter($hook, $callback, $priority));

            return;
        }

        self::assertSame($expected, remove_action($hook, $callback, $priority));
        self::assertSame($expected, $this->hooks->removeAction($hook, $callback, $priority));
    }

    private function bothHas(string $hook, callable $callback, int|false $expected, bool $filter = false): void
    {
        if ($filter) {
            self::assertSame($expected, has_filter($hook, $callback));
            self::assertSame($expected, $this->hooks->hasFilter($hook, $callback));

            return;
        }

        self::assertSame($expected, has_action($hook, $callback));
        self::assertSame($expected, $this->hooks->hasAction($hook, $callback));
    }
}
