<?php

declare(strict_types=1);

namespace ComposePress\Core {
    function time(): int
    {
        return $GLOBALS['composepress_test_time'] ?? \time();
    }
}

namespace ComposePress\Core\Tests\Unit {
    use ComposePress\Core\WordPressOptionUpgradeLock;
    use PHPUnit\Framework\TestCase;

    final class WordPressOptionUpgradeLockTest extends TestCase
    {
        protected function setUp(): void
        {
            $GLOBALS['composepress_test_options'] = [];
            $GLOBALS['composepress_test_cache_deletes'] = [];
            $GLOBALS['composepress_test_time'] = 1_000;
        }

        protected function tearDown(): void
        {
            unset($GLOBALS['composepress_test_time']);
        }

        public function testSameSecondRenewalKeepsCurrentLeaseWithoutUpdate(): void
        {
            $lock = new WordPressOptionUpgradeLock('example_upgrade_lock');

            self::assertTrue($lock->acquire());
            $GLOBALS['composepress_test_cache_deletes'] = [];

            self::assertTrue($lock->renew());
            self::assertTrue($lock->renew());
            self::assertSame([], $GLOBALS['composepress_test_cache_deletes']);
        }

        public function testRenewalFailsWhenAnotherOwnerReplacesTheLock(): void
        {
            $lock = new WordPressOptionUpgradeLock('example_upgrade_lock');

            self::assertTrue($lock->acquire());
            $GLOBALS['composepress_test_options']['example_upgrade_lock'] = 'another-owner|1000';

            self::assertFalse($lock->renew());
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
}
