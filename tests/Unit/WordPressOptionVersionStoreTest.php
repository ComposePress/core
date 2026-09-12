<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit;

use ComposePress\Core\WordPressOptionVersionStore;
use PHPUnit\Framework\TestCase;

final class WordPressOptionVersionStoreTest extends TestCase
{
    private \wpdb $wpdb;

    protected function setUp(): void
    {
        $this->wpdb = $GLOBALS['wpdb'];
        $GLOBALS['composepress_test_options'] = [];
        $GLOBALS['composepress_test_cache_deletes'] = [];
        $GLOBALS['composepress_test_update_option_result'] = true;
    }

    protected function tearDown(): void
    {
        $GLOBALS['wpdb'] = $this->wpdb;
    }

    public function testReadsAndStoresVersionInAnOption(): void
    {
        $store = new WordPressOptionVersionStore('example_version');

        self::assertNull($store->get());
        $store->set('2.0.0');

        self::assertSame('2.0.0', $store->get());
    }

    public function testEmptyOptionValuesAreNotVersions(): void
    {
        $GLOBALS['composepress_test_options']['example_version'] = '';
        $store = new WordPressOptionVersionStore('example_version');

        self::assertNull($store->get());
    }

    public function testFailedOptionWriteThrows(): void
    {
        $GLOBALS['composepress_test_update_option_result'] = false;
        $store = new WordPressOptionVersionStore('example_version');

        $this->expectException(\RuntimeException::class);
        $store->set('2.0.0');
    }

    public function testUnchangedOptionValueIsAcceptedWhenUpdateReturnsFalse(): void
    {
        $GLOBALS['composepress_test_options']['example_version'] = '2.0.0';
        $GLOBALS['composepress_test_update_option_result'] = false;
        $store = new WordPressOptionVersionStore('example_version');

        $store->set('2.0.0');

        self::assertSame('2.0.0', $store->get());
    }

    public function testOptionNameAndVersionAreRequired(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new WordPressOptionVersionStore('');
    }

    public function testCompareAndSetAdvancesMatchingVersionAndInvalidatesCache(): void
    {
        $GLOBALS['composepress_test_options']['example_version'] = '1.0.0';
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
                        "/UPDATE wp_options SET option_value = '([^']+)' WHERE option_name = '([^']+)' AND option_value = '([^']+)'/",
                        $query,
                        $matches,
                    ) !== 1
                ) {
                    return 1;
                }

                if (($GLOBALS['composepress_test_options'][$matches[2]] ?? null) !== $matches[3]) {
                    return 0;
                }

                $GLOBALS['composepress_test_options'][$matches[2]] = $matches[1];
                return 1;
            }
        };
        $store = new WordPressOptionVersionStore('example_version');

        self::assertTrue($store->compareAndSet('1.0.0', '2.0.0'));
        self::assertSame('2.0.0', $store->get());
        self::assertSame([
            ['example_version', 'options'],
            ['alloptions', 'options'],
            ['notoptions', 'options'],
        ], $GLOBALS['composepress_test_cache_deletes']);
    }

    public function testCompareAndSetReplacesAnEmptyStoredValue(): void
    {
        $GLOBALS['composepress_test_options']['example_version'] = '';
        $GLOBALS['wpdb'] = new class ('', '', '', '') extends \wpdb {
            public function prepare(mixed $query, mixed ...$args): string
            {
                $query = (string) $query;
                foreach ($args as $argument) {
                    $query = preg_replace('/%s/', "'" . addslashes((string) $argument) . "'", $query, 1) ?? $query;
                }

                return $query;
            }

            public function query(mixed $query): int
            {
                $query = (string) $query;
                if (
                    preg_match(
                        "/UPDATE wp_options SET option_value = '([^']+)' WHERE option_name = '([^']+)'/",
                        $query,
                        $matches,
                    ) !== 1
                ) {
                    return 1;
                }

                $GLOBALS['composepress_test_options'][$matches[2]] = $matches[1];
                return 1;
            }
        };
        $store = new WordPressOptionVersionStore('example_version');

        self::assertTrue($store->compareAndSet('', '2.0.0'));
        self::assertSame('2.0.0', $store->get());
    }

    public function testCompareAndSetRejectsAChangedStoredVersion(): void
    {
        $GLOBALS['composepress_test_options']['example_version'] = '3.0.0';
        $GLOBALS['wpdb'] = new class ('', '', '', '') extends \wpdb {
            public function prepare(mixed $query, mixed ...$args): string
            {
                $query = (string) $query;
                foreach ($args as $argument) {
                    $query = preg_replace('/%s/', "'" . addslashes((string) $argument) . "'", $query, 1) ?? $query;
                }

                return $query;
            }

            public function query(mixed $query): int
            {
                $query = (string) $query;
                if (
                    preg_match(
                        "/UPDATE wp_options SET option_value = '([^']+)' WHERE option_name = '([^']+)' AND option_value = '([^']+)'/",
                        $query,
                        $matches,
                    ) !== 1
                ) {
                    return 1;
                }

                return ($GLOBALS['composepress_test_options'][$matches[2]] ?? null) === $matches[3] ? 1 : 0;
            }
        };
        $store = new WordPressOptionVersionStore('example_version');

        self::assertFalse($store->compareAndSet('1.0.0', '2.0.0'));
        self::assertSame('3.0.0', $store->get());
        self::assertSame([], $GLOBALS['composepress_test_cache_deletes']);
    }
}
