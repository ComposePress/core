<?php

declare(strict_types=1);

namespace ComposePress\Core;

interface AtomicPluginVersionStore extends PluginVersionStore
{
    /**
     * Puts $version when the stored version still matches $expected.
     *
     * A null $expected targets a first installation with no stored version.
     * Returns false when another worker already advanced the stored version,
     * leaving the store unchanged.
     */
    public function compareAndSet(?string $expected, string $version): bool;
}
