<?php

declare(strict_types=1);

namespace ComposePress\Core;

interface PluginLifecycle
{
    public function activate(bool $networkWide): void;

    public function deactivate(bool $networkWide): void;
}
