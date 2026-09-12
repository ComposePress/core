<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit;

use ComposePress\Core\WordPressOptionVersionStore;
use PHPUnit\Framework\TestCase;

final class WordPressOptionVersionStoreTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['composepress_test_options'] = [];
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

    public function testOptionNameAndVersionAreRequired(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new WordPressOptionVersionStore('');
    }
}
