<?php

declare(strict_types=1);

namespace ComposePress\Core;

final class PluginBootstrap
{
    public function __construct(
        private readonly Plugin $plugin,
        private readonly PluginVersionStore $versionStore,
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

        if ($installedVersion !== null && version_compare($installedVersion, $currentVersion, '<')) {
            $this->plugin->upgrade($installedVersion);
        }

        $this->versionStore->set($currentVersion);
        $this->plugin->boot();
    }
}
