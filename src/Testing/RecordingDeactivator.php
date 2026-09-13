<?php

declare(strict_types=1);

namespace ComposePress\Core\Testing;

use ComposePress\Core\PluginDeactivator;

/**
 * PluginDeactivator fake that records every deactivate() invocation with its
 * networkWide flag.
 */
final class RecordingDeactivator implements PluginDeactivator
{
    /** @var list<bool> networkWide flag for each deactivation, in call order */
    private array $invocations = [];

    public function deactivate(bool $networkWide): void
    {
        $this->invocations[] = $networkWide;
    }

    /** @return list<bool> */
    public function invocations(): array
    {
        return $this->invocations;
    }

    public function count(): int
    {
        return count($this->invocations);
    }
}
