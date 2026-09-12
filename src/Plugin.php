<?php

declare(strict_types=1);

namespace ComposePress\Core;

final class Plugin
{
    private bool $booted = false;
    private bool $bootAttempted = false;

    /** @var list<RequirementResult>|null */
    private ?array $requirementResults = null;

    /**
     * @param iterable<HookSubscriber> $subscribers
     * @param iterable<PluginRequirement> $requirements
     */
    public function __construct(
        public readonly PluginContext $context,
        private readonly iterable $subscribers = [],
        private readonly ?PluginActivator $activator = null,
        private readonly ?PluginDeactivator $deactivator = null,
        private readonly ?Hooks $hooks = null,
        private readonly ?string $uninstaller = null,
        private readonly iterable $requirements = [],
        private readonly ?PluginUpgrade $upgrader = null,
    ) {
    }

    /**
     * @return list<RequirementResult>
     */
    public function checkRequirements(): array
    {
        if ($this->requirementResults !== null) {
            return $this->requirementResults;
        }

        $results = [];
        foreach ($this->requirements as $requirement) {
            if (!$requirement instanceof PluginRequirement) {
                throw new \InvalidArgumentException(sprintf(
                    'Requirement %s must implement %s.',
                    get_debug_type($requirement),
                    PluginRequirement::class,
                ));
            }

            $results[] = $requirement->check();
        }

        $this->requirementResults = $results;

        return $results;
    }

    public function ensureRequirementsMet(): void
    {
        $failedRequirements = array_values(array_filter(
            $this->checkRequirements(),
            static fn (RequirementResult $result): bool => !$result->satisfied,
        ));
        if ($failedRequirements !== []) {
            throw new RequirementsNotMet($failedRequirements);
        }
    }

    public function boot(): void
    {
        if ($this->bootAttempted) {
            throw new \LogicException('Plugin boot has already been attempted.');
        }

        $this->bootAttempted = true;

        $this->ensureRequirementsMet();

        if ($this->activator !== null || $this->deactivator !== null || $this->uninstaller !== null) {
            if (!function_exists('register_activation_hook')) {
                throw new \LogicException('WordPress must be loaded before booting lifecycle handlers.');
            }
        }

        if ($this->uninstaller !== null && !is_a($this->uninstaller, PluginUninstall::class, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Uninstaller %s must implement %s.',
                $this->uninstaller,
                PluginUninstall::class,
            ));
        }

        $hooks = $this->hooks ?? new WordPressHooks();
        foreach ($this->subscribers as $subscriber) {
            if (!$subscriber instanceof HookSubscriber) {
                throw new \InvalidArgumentException(sprintf(
                    'Subscriber %s must implement %s.',
                    get_debug_type($subscriber),
                    HookSubscriber::class,
                ));
            }

            $subscriber->subscribe($hooks);
        }

        if ($this->activator !== null) {
            register_activation_hook($this->context->file, [$this->activator, 'activate']);
        }

        if ($this->deactivator !== null) {
            register_deactivation_hook($this->context->file, [$this->deactivator, 'deactivate']);
        }

        if ($this->uninstaller !== null) {
            register_uninstall_hook($this->context->file, [$this->uninstaller, 'uninstall']);
        }

        $this->booted = true;
    }

    public function upgrade(string $fromVersion): void
    {
        if ($fromVersion === '') {
            throw new \InvalidArgumentException('Previous plugin version is required.');
        }

        if ($this->upgrader === null || version_compare($fromVersion, $this->context->version, '>=')) {
            return;
        }

        $this->upgrader->upgrade($fromVersion, $this->context->version);
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }
}
