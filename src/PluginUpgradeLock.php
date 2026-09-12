<?php

declare(strict_types=1);

namespace ComposePress\Core;

interface PluginUpgradeLock
{
    public function acquire(): bool;

    public function release(): void;
}
