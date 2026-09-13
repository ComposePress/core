<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit\Testing;

use ComposePress\Core\Hooks;
use ComposePress\Core\Testing\RecordingHooks;
use ComposePress\Core\Tests\Fixtures\ExamplePluginFixture;
use PHPUnit\Framework\TestCase;

final class RecordingHooksTest extends TestCase
{
    public function testRecordsActionsWithPriorityAndArguments(): void
    {
        $hooks = new RecordingHooks();
        $callback = static function (): void {
        };

        $hooks->action('save_post', $callback, 20, 2);

        self::assertCount(1, $hooks->recordedActions());
        $registration = $hooks->recordedActions()[0];
        self::assertSame('save_post', $registration->hook);
        self::assertSame($callback, $registration->callback);
        self::assertSame(20, $registration->priority);
        self::assertSame(2, $registration->arguments);
        self::assertSame(['save_post'], $hooks->actionNames());
        self::assertSame(20, $hooks->hasAction('save_post', $callback));
    }

    public function testRecordsFiltersSeparatelyFromActions(): void
    {
        $hooks = new RecordingHooks();
        $callback = static function (): void {
        };

        $hooks->action('init', $callback);
        $hooks->filter('the_title', $callback, 5);

        self::assertSame(['init'], $hooks->actionNames());
        self::assertSame(['the_title'], $hooks->filterNames());
        self::assertSame([], $hooks->actionsFor('the_title'));
        self::assertCount(1, $hooks->filtersFor('the_title'));
        self::assertSame(5, $hooks->filtersFor('the_title')[0]->priority);
        self::assertSame(5, $hooks->hasFilter('the_title', $callback));
    }

    public function testActionAndFilterRegistrationsShareOneEnginePerHook(): void
    {
        $hooks = new RecordingHooks();
        $callback = static function (): void {
        };

        $hooks->action('init', $callback, 20);
        $hooks->filter('init', $callback, 20);

        self::assertCount(1, $hooks->recordedActions());
        self::assertCount(1, $hooks->recordedFilters());
        self::assertSame(20, $hooks->hasAction('init', $callback));
        self::assertSame(20, $hooks->hasFilter('init', $callback));
        self::assertTrue($hooks->removeAction('init', $callback, 20));
        self::assertFalse($hooks->removeFilter('init', $callback, 20));
    }

    public function testRemovalRequiresTheExactPriority(): void
    {
        $hooks = new RecordingHooks();
        $callback = static function (): void {
        };
        $hooks->action('init', $callback, 15);
        $hooks->filter('the_title', $callback, 5);

        self::assertTrue($hooks->removeAction('init', $callback, 15));
        self::assertTrue($hooks->removeFilter('the_title', $callback, 5));
        self::assertFalse($hooks->removeAction('missing', $callback));
        self::assertFalse($hooks->removeAction('init', $callback, 10));

        self::assertCount(1, $hooks->removedActionsFor('init'));
        self::assertSame('init', $hooks->removedActionsFor('init')[0]->hook);
        self::assertSame(15, $hooks->removedActionsFor('init')[0]->priority);
        self::assertCount(1, $hooks->removedFiltersFor('the_title'));
        self::assertCount(0, $hooks->removedActionsFor('the_title'));
        self::assertCount(2, $hooks->recordedRemovals());
    }

    public function testSecondRemovalOfSameRegistrationFails(): void
    {
        $hooks = new RecordingHooks();
        $callback = static function (): void {
        };
        $hooks->action('init', $callback, 15);
        $hooks->filter('the_title', $callback, 5);

        self::assertTrue($hooks->removeAction('init', $callback, 15));
        self::assertTrue($hooks->removeFilter('the_title', $callback, 5));
        self::assertFalse($hooks->removeAction('init', $callback, 15));
        self::assertFalse($hooks->removeFilter('the_title', $callback, 5));

        self::assertFalse($hooks->hasAction('init', $callback));
        self::assertFalse($hooks->hasFilter('the_title', $callback));
        self::assertCount(1, $hooks->removedActionsFor('init'));
        self::assertCount(1, $hooks->removedFiltersFor('the_title'));
        self::assertSame(['init'], $hooks->actionNames());
    }

    public function testDuplicateRegistrationReplacesActiveEntry(): void
    {
        $hooks = new RecordingHooks();
        $callback = static function (): void {
        };

        $hooks->action('init', $callback, 15, 1);
        $hooks->action('init', $callback, 15, 2);

        self::assertCount(2, $hooks->recordedActions());
        self::assertSame(2, $hooks->actionsFor('init')[1]->arguments);
        self::assertSame(15, $hooks->hasAction('init', $callback));

        self::assertTrue($hooks->removeAction('init', $callback, 15));
        self::assertFalse($hooks->hasAction('init', $callback));
        self::assertFalse($hooks->removeAction('init', $callback, 15));

        $hooks->action('init', $callback, 15);
        self::assertSame(15, $hooks->hasAction('init', $callback));
    }

    public function testStaticMethodCallbacksShareTheirWordPressId(): void
    {
        $hooks = new RecordingHooks();

        $arrayCallback = [ExamplePluginFixture::class, 'bootstrap'];
        $stringCallback = ExamplePluginFixture::class . '::bootstrap';
        $filterCallback = ExamplePluginFixture::class . '::render';

        $hooks->action('init', $arrayCallback);
        $hooks->filter('the_content', $filterCallback);

        self::assertSame(10, $hooks->hasAction('init', $stringCallback));
        self::assertTrue($hooks->removeAction('init', $stringCallback));
        self::assertFalse($hooks->hasAction('init', $arrayCallback));

        self::assertSame(10, $hooks->hasFilter('the_content', [ExamplePluginFixture::class, 'render']));
        self::assertTrue($hooks->removeFilter('the_content', $filterCallback));
        self::assertFalse($hooks->hasFilter('the_content', $filterCallback));
    }

    public function testInstanceMethodCallbacksAreIdentifiedByTheirObject(): void
    {
        $hooks = new RecordingHooks();

        $subscriber = new class {
            public function register(): void
            {
            }
        };
        $other = new class {
            public function register(): void
            {
            }
        };

        $hooks->action('init', [$subscriber, 'register']);

        self::assertSame(10, $hooks->hasAction('init', [$subscriber, 'register']));
        self::assertFalse($hooks->hasAction('init', [$other, 'register']));
    }

    public function testHasActionReturnsLowestPriorityMatchingBucket(): void
    {
        $hooks = new RecordingHooks();
        $callback = static function (): void {
        };

        $hooks->action('init', $callback, 30);
        $hooks->action('init', $callback, 20);

        self::assertSame(20, $hooks->hasAction('init', $callback));

        self::assertTrue($hooks->removeAction('init', $callback, 20));
        self::assertFalse($hooks->removeAction('init', $callback, 20));
        self::assertSame(30, $hooks->hasAction('init', $callback));
        self::assertTrue($hooks->removeAction('init', $callback, 30));
        self::assertFalse($hooks->hasAction('init', $callback));
    }

    public function testPriorityZeroComparesWithStrictIdentity(): void
    {
        $hooks = new RecordingHooks();
        $callback = static function (): void {
        };

        $hooks->action('init', $callback, 0);

        self::assertSame(0, $hooks->hasAction('init', $callback));
        self::assertNotFalse($hooks->hasAction('init', $callback));
    }

    public function testRemovesAreScopedPerHook(): void
    {
        $hooks = new RecordingHooks();
        $callback = static function (): void {
        };

        $hooks->action('init', $callback, 10);
        $hooks->action('save_post', $callback, 10);

        self::assertTrue($hooks->removeAction('init', $callback, 10));
        self::assertSame(10, $hooks->hasAction('save_post', $callback));
        self::assertFalse($hooks->removeAction('init', $callback, 10));
    }

    public function testResetClearsAllRecordings(): void
    {
        $hooks = new RecordingHooks();
        $hooks->action('init', static function (): void {
        });
        $hooks->filter('the_title', static function (): void {
        });

        $hooks->reset();

        self::assertSame([], $hooks->recordedActions());
        self::assertSame([], $hooks->recordedFilters());
        self::assertSame([], $hooks->recordedRemovals());
    }

    public function testImplementsHooksContract(): void
    {
        self::assertInstanceOf(Hooks::class, new RecordingHooks());
    }
}
