<?php

declare(strict_types=1);

namespace ComposePress\Core;

interface PluginActivator
{
    public function activate(bool $networkWide): void;
}
