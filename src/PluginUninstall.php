<?php

declare(strict_types=1);

namespace ComposePress\Core;

interface PluginUninstall
{
    public static function uninstall(): void;
}
