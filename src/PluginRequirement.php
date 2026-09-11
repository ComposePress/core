<?php

declare(strict_types=1);

namespace ComposePress\Core;

interface PluginRequirement
{
    public function check(): RequirementResult;
}
