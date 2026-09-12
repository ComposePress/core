<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit;

use ComposePress\Core\AtomicPluginVersionStore;

final class InMemoryVersionStore implements AtomicPluginVersionStore
{
    /** @var list<string> */
    public array $writes = [];

    public function __construct(private ?string $version = null)
    {
    }

    public function get(): ?string
    {
        return $this->version;
    }

    public function set(string $version): void
    {
        $this->writes[] = $version;
        $this->version = $version;
    }

    public function compareAndSet(?string $expected, string $version): bool
    {
        if ($this->version !== $expected) {
            return false;
        }

        $this->writes[] = $version;
        $this->version = $version;
        return true;
    }
}
