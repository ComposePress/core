<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Fixtures;

final class ScopedUninstallProvider
{
    public static function className(): string
    {
        return ScopedUninstall::class;
    }
}
