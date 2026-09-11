<?php

declare(strict_types=1);

namespace ComposePress\Core;

final readonly class RequirementResult
{
    public function __construct(
        public string $name,
        public bool $satisfied,
        public string $message,
    ) {
        if ($this->name === '' || $this->message === '') {
            throw new \InvalidArgumentException('Requirement name and message are required.');
        }
    }
}
