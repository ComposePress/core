<?php

declare(strict_types=1);

namespace ComposePress\Core {
    function time(): int
    {
        if (($GLOBALS['composepress_test_time_ticks'] ?? []) !== []) {
            $tick = array_shift($GLOBALS['composepress_test_time_ticks']);
            if (is_int($tick)) {
                return $tick;
            }
        }

        return $GLOBALS['composepress_test_time'] ?? \time();
    }
}

namespace ComposePress\Core\Tests\Unit {
    use ComposePress\Core\WordPressOptionUpgradeLock;
    use PHPUnit\Framework\TestCase;

    final class WordPressOptionUpgradeLockTest extends TestCase
    {
        private \wpdb $wpdb;

        protected function setUp(): void
        {
            $this->wpdb = $GLOBALS['wpdb'];
            $GLOBALS['composepress_test_options'] = [];
            $GLOBALS['composepress_test_cache_deletes'] = [];
            $GLOBALS['composepress_test_time'] = 1_000;
        }

        protected function tearDown(): void
        {
            $GLOBALS['wpdb'] = $this->wpdb;
            unset($GLOBALS['composepress_test_time'], $GLOBALS['composepress_test_time_ticks']);
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

        public function testTakeoverStartsReplacementLeaseAtReclamationTime(): void
        {
            $GLOBALS['composepress_test_options']['example_upgrade_lock'] = 'crashed-owner|94';
            $GLOBALS['composepress_test_time_ticks'] = [1_000, 1_001, 1_001, 1_001, 1_001];
            $GLOBALS['wpdb'] = new class ('', '', '', '') extends \wpdb {
                public function prepare(mixed $query, mixed ...$args): string
                {
                    $query = (string) $query;
                    foreach ($args as $argument) {
                        $query = preg_replace(
                            '/%s/',
                            "'" . addslashes((string) $argument) . "'",
                            $query,
                            1,
                        ) ?? $query;
                    }

                    return $query;
                }

                public function query(mixed $query): int
                {
                    $query = (string) $query;
                    if (
                        preg_match(
                            "/DELETE FROM wp_options WHERE option_name = '([^']+)' AND option_value = '([^']+)'/",
                            $query,
                            $matches,
                        ) !== 1
                    ) {
                        return 1;
                    }

                    if (($GLOBALS['composepress_test_options'][$matches[1]] ?? null) !== $matches[2]) {
                        return 0;
                    }

                    unset($GLOBALS['composepress_test_options'][$matches[1]]);
                    return 1;
                }
            };

            $lock = new WordPressOptionUpgradeLock('example_upgrade_lock', 1);

            self::assertTrue($lock->acquire());
            self::assertStringEndsWith('|1001', $GLOBALS['composepress_test_options']['example_upgrade_lock']);

            $contender = new WordPressOptionUpgradeLock('example_upgrade_lock', 1);
            self::assertFalse($contender->acquire());
        }

        public function testAcquireTreatsNonNumericTimestampAsContention(): void
        {
            $GLOBALS['composepress_test_options']['example_upgrade_lock'] = 'owner|not-a-timestamp';

            $lock = new WordPressOptionUpgradeLock('example_upgrade_lock', 1);

            self::assertFalse($lock->acquire());
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
