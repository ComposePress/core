<?php

declare(strict_types=1);

namespace ComposePress\Core;

interface HookSubscriber
{
    public function subscribe(Hooks $hooks): void;
}
