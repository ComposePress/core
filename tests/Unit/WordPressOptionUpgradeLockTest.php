<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit;

use ComposePress\Core\WordPressOptionUpgradeLock;
use PHPUnit\Framework\TestCase;

final class WordPressOptionUpgradeLockTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['composepress_test_options'] = [];
        $GLOBALS['composepress_test_cache_deletes'] = [];
    }

    public function testRenewInvalidatesOptionCachesAfterConditionalUpdate(): void
    {
        $lock = new WordPressOptionUpgradeLock('example_upgrade_lock');

        self::assertTrue($lock->acquire());
        $GLOBALS['composepress_test_cache_deletes'] = [];

        self::assertTrue($lock->renew());

        self::assertSame([
            ['example_upgrade_lock', 'options'],
            ['alloptions', 'options'],
            ['notoptions', 'options'],
        ], $GLOBALS['composepress_test_cache_deletes']);
    }

    public function testReleaseInvalidatesOptionCachesAfterConditionalDelete(): void
    {
        $lock = new WordPressOptionUpgradeLock('example_upgrade_lock');

        self::assertTrue($lock->acquire());
        $lock->release();

        self::assertSame([
            ['example_upgrade_lock', 'options'],
            ['alloptions', 'options'],
            ['notoptions', 'options'],
        ], $GLOBALS['composepress_test_cache_deletes']);
    }
}
