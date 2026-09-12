<?php

declare(strict_types=1);

namespace ComposePress\Core;

final class WordPressOptionVersionStore implements PluginVersionStore
{
    public function __construct(
        private readonly string $optionName,
        private readonly bool $autoload = false,
    ) {
        if ($this->optionName === '') {
            throw new \InvalidArgumentException('Version option name is required.');
        }
    }

    public function get(): ?string
    {
        if (!function_exists('get_option')) {
            throw new \LogicException('WordPress must be loaded before reading plugin versions.');
        }

        $version = get_option($this->optionName, null);

        return is_string($version) && $version !== '' ? $version : null;
    }

    public function set(string $version): void
    {
        if ($version === '') {
            throw new \InvalidArgumentException('Plugin version is required.');
        }
        if (!function_exists('update_option')) {
            throw new \LogicException('WordPress must be loaded before storing plugin versions.');
        }

        $updated = update_option($this->optionName, $version, $this->autoload);
        if (!$updated && get_option($this->optionName, null) !== $version) {
            throw new \RuntimeException(sprintf(
                'Unable to store plugin version %s in option %s.',
                $version,
                $this->optionName,
            ));
        }

        if (get_option($this->optionName, null) !== $version) {
            throw new \RuntimeException(sprintf(
                'Plugin version %s was not persisted in option %s.',
                $version,
                $this->optionName,
            ));
        }
    }
}
