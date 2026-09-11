<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit;

use ComposePress\Core\PluginContext;
use PHPUnit\Framework\TestCase;

final class PluginContextTest extends TestCase
{
    public function testContextExposesExplicitMetadata(): void
    {
        $context = new PluginContext('/plugins/example/example.php', 'example', '1.2.3');

        self::assertSame('/plugins/example/example.php', $context->file);
        self::assertSame('example', $context->slug);
        self::assertSame('1.2.3', $context->version);
    }

    public function testWordPressHelpersFailClearlyWhenWordPressIsNotLoaded(): void
    {
        $context = new PluginContext('/plugins/example/example.php', 'example', '1.2.3');

        $this->expectException(\LogicException::class);
        $context->directory();
    }
}
