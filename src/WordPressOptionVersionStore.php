<?php

declare(strict_types=1);

namespace ComposePress\Core;

final class WordPressOptionVersionStore implements AtomicPluginVersionStore
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

    public function compareAndSet(?string $expected, string $version): bool
    {
        if ($version === '') {
            throw new \InvalidArgumentException('Plugin version is required.');
        }
        if (!function_exists('add_option') || !function_exists('get_option') || !function_exists('wp_cache_delete')) {
            throw new \LogicException('WordPress must be loaded before storing plugin versions.');
        }

        if ($expected === null) {
            return add_option($this->optionName, $version, '', $this->autoload);
        }

        if ($expected === '') {
            // An empty row is treated as absent by get(); replace it in place.
            return $this->updateOptionValue($version);
        }

        return $this->updateOptionValue($version, $expected);
    }

    private function updateOptionValue(string $version, ?string $expected = null): bool
    {
        global $wpdb;

        if (!$wpdb instanceof \wpdb) {
            throw new \LogicException('WordPress database must be loaded before storing plugin versions.');
        }

        if ($expected === null) {
            $query = $wpdb->prepare(
                "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s", // @phpstan-ignore argument.type
                $version,
                $this->optionName,
            );
        } else {
            $query = $wpdb->prepare(
                "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s", // @phpstan-ignore argument.type
                $version,
                $this->optionName,
                $expected,
            );
        }
        if ($query === null || $wpdb->query($query) !== 1) {
            return false;
        }

        wp_cache_delete($this->optionName, 'options');
        wp_cache_delete('alloptions', 'options');
        wp_cache_delete('notoptions', 'options');

        return true;
    }
}
