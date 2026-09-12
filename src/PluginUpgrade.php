<?php

declare(strict_types=1);

namespace ComposePress\Core;

interface PluginUpgrade
{
    public function upgrade(string $fromVersion, string $toVersion): void;
}
