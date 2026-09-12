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

                $lease = $lock instanceof PluginUpgradeLease ? $lock : null;
                if ($installedVersion === null) {
                    $this->persistVersion(null, $currentVersion, $lease);
                } elseif (version_compare($installedVersion, $currentVersion, '<')) {
                    $this->plugin->upgrade($installedVersion, $lease);
                    $this->persistVersion($installedVersion, $currentVersion, $lease);
                }
            } finally {
                $lock->release();
            }
        }

        $this->plugin->boot();
    }

    private function persistVersion(?string $expected, string $currentVersion, ?PluginUpgradeLease $lease): void
    {
        if ($lease !== null && !$lease->renew()) {
            throw new UpgradeInProgress(sprintf(
                'Plugin upgrade to %s no longer owns its lock.',
                $currentVersion,
            ));
        }

        if ($this->versionStore instanceof AtomicPluginVersionStore) {
            $this->versionStore->compareAndSet($expected, $currentVersion);
        } else {
            $storedVersion = $this->versionStore->get();
            if ($storedVersion === null || version_compare($storedVersion, $currentVersion, '<')) {
                $this->versionStore->set($currentVersion);
            }
        }

        $storedVersion = $this->versionStore->get();
        if ($storedVersion !== $currentVersion) {
            throw new \RuntimeException(sprintf(
                'Plugin version %s was not persisted; stored version is %s.',
                $currentVersion,
                $storedVersion ?? 'none',
            ));
        }
    }
}
