<?php

declare(strict_types=1);

namespace ComposePress\Core;

interface PluginDeactivator
{
    public function deactivate(bool $networkWide): void;
}
