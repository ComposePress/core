<?php

declare(strict_types=1);

namespace ComposePress\Core;

final class PluginBootstrap
{
    public function __construct(
        private readonly Plugin $plugin,
        private readonly PluginVersionStore $versionStore,
        private readonly ?PluginUpgradeLock $upgradeLock = null,
    ) {
    }

    public function run(): void
    {
        $installedVersion = $this->versionStore->get();
        $currentVersion = $this->plugin->context->version;

        if ($installedVersion !== null && version_compare($installedVersion, $currentVersion, '>')) {
            throw new \LogicException(sprintf(
                'Installed plugin version %s is newer than %s.',
                $installedVersion,
                $currentVersion,
            ));
        }

        $this->plugin->ensureRequirementsMet();

        $lock = $this->upgradeLock;
        if ($installedVersion !== null && version_compare($installedVersion, $currentVersion, '<')) {
            $lock ??= new WordPressOptionUpgradeLock($this->plugin->context->slug . '_upgrade_lock');
            if (!$lock->acquire()) {
                throw new UpgradeInProgress(sprintf(
                    'Plugin upgrade to %s is already in progress.',
                    $currentVersion,
                ));
            }
            $upgradeLockAcquired = true;

            try {
                $installedVersion = $this->versionStore->get();
                if ($installedVersion !== null && version_compare($installedVersion, $currentVersion, '<')) {
                    $this->plugin->upgrade($installedVersion);
                }

                $this->versionStore->set($currentVersion);
            } finally {
                $lock->release();
            }
        } else {
            $this->versionStore->set($currentVersion);
        }

        $this->plugin->boot();
    }
}
