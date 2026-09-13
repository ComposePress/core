<?php

declare(strict_types=1);

namespace ComposePress\Core\Testing;

use ComposePress\Core\PluginActivator;

/**
 * PluginActivator fake that records every activate() invocation with its
 * networkWide flag.
 */
final class RecordingActivator implements PluginActivator
{
    /** @var list<bool> networkWide flag for each activation, in call order */
    private array $invocations = [];

    public function activate(bool $networkWide): void
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
