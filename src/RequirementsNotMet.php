<?php

declare(strict_types=1);

namespace ComposePress\Core;

final class RequirementsNotMet extends \RuntimeException
{
    /**
     * @param list<RequirementResult> $results
     */
    public function __construct(public readonly array $results)
    {
        parent::__construct(implode(' ', array_map(
            static fn (RequirementResult $result): string => $result->message,
            $results,
        )));
    }
}
