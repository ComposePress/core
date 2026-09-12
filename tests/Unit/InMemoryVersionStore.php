<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit;

use ComposePress\Core\PluginVersionStore;

final class InMemoryVersionStore implements PluginVersionStore
{
    public function __construct(private ?string $version = null)
    {
    }

    public function get(): ?string
    {
        return $this->version;
    }

    public function set(string $version): void
    {
        $this->version = $version;
    }
}
