<?php

declare(strict_types=1);

namespace ComposePress\Core;

interface PluginVersionStore
{
    public function get(): ?string;

    public function set(string $version): void;
}
