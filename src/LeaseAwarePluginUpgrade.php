<?php

declare(strict_types=1);

namespace ComposePress\Core;

interface LeaseAwarePluginUpgrade extends PluginUpgrade
{
    /**
     * Runs an upgrade and renews $lease before it can expire during long-running work.
     */
    public function upgradeWithLease(string $fromVersion, string $toVersion, PluginUpgradeLease $lease): void;
}
