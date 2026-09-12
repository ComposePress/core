<?php

declare(strict_types=1);

namespace ComposePress\Core;

interface PluginUpgradeLease
{
    /**
     * Extends exclusive ownership while a long-running upgrade is still active.
     */
    public function renew(): bool;
}
