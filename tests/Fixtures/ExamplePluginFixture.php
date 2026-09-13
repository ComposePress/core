<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Fixtures;

/** Test fixture exercising WordPress-style callback ids. */
final class ExamplePluginFixture
{
    public static function bootstrap(): void
    {
    }

    public static function render(string $value): string
    {
        return $value;
    }
}
