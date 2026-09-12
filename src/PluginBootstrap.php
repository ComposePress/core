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

        if ($installedVersion === null || version_compare($installedVersion, $currentVersion, '<')) {
            $lock = $this->upgradeLock ?? new WordPressOptionUpgradeLock(
                $this->plugin->context->slug . '_upgrade_lock',
            );
            if (!$lock->acquire()) {
                throw new UpgradeInProgress(sprintf(
                    'Plugin upgrade to %s is already in progress.',
                    $currentVersion,
                ));
            }

            try {
                $installedVersion = $this->versionStore->get();
                if ($installedVersion !== null && version_compare($installedVersion, $currentVersion, '>')) {
                    throw new \LogicException(sprintf(
                        'Installed plugin version %s is newer than %s.',
                        $installedVersion,
                        $currentVersion,
                    ));
                }

                if ($installedVersion === null) {
                    $this->versionStore->set($currentVersion);
                } elseif (version_compare($installedVersion, $currentVersion, '<')) {
                    $lease = $lock instanceof PluginUpgradeLease ? $lock : null;
                    $this->plugin->upgrade($installedVersion, $lease);
                    $this->versionStore->set($currentVersion);
                }
            } finally {
                $lock->release();
            }
        }

        $this->plugin->boot();
    }
}
