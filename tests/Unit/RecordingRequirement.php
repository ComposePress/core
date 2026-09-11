<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit;

use ComposePress\Core\PluginRequirement;
use ComposePress\Core\RequirementResult;

final class RecordingRequirement implements PluginRequirement
{
    public function __construct(
        private readonly string $name,
        private readonly bool $satisfied,
        private readonly string $message,
    ) {
    }

    public function check(): RequirementResult
    {
        return new RequirementResult($this->name, $this->satisfied, $this->message);
    }
}
