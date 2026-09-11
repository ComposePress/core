<?php

declare(strict_types=1);

namespace ComposePress\Core;

final class Plugin
{
    private bool $booted = false;

    /**
     * @param iterable<HookSubscriber> $subscribers
     */
    public function __construct(
        public readonly PluginContext $context,
        private readonly iterable $subscribers = [],
        private readonly ?PluginLifecycle $lifecycle = null,
        private readonly ?Hooks $hooks = null,
        private readonly ?string $uninstaller = null,
    ) {
    }

    public function boot(): void
    {
        if ($this->booted) {
            throw new \LogicException('Plugin has already been booted.');
        }

        if ($this->lifecycle !== null || $this->uninstaller !== null) {
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

        if ($this->lifecycle !== null) {
            register_activation_hook($this->context->file, [$this->lifecycle, 'activate']);
            register_deactivation_hook($this->context->file, [$this->lifecycle, 'deactivate']);
        }

        if ($this->uninstaller !== null) {
            register_uninstall_hook($this->context->file, [$this->uninstaller, 'uninstall']);
        }

        $this->booted = true;
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }
}
