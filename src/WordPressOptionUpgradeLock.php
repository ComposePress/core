<?php

declare(strict_types=1);

namespace ComposePress\Core;

final class WordPressOptionUpgradeLock implements PluginUpgradeLock
{
    private ?string $token = null;

    public function __construct(
        private readonly string $optionName,
        private readonly int $ttl = 300,
    ) {
        if ($this->optionName === '') {
            throw new \InvalidArgumentException('Upgrade lock option name is required.');
        }
        if ($this->ttl < 1) {
            throw new \InvalidArgumentException('Upgrade lock TTL must be positive.');
        }
    }

    public function acquire(): bool
    {
        if (!function_exists('add_option') || !function_exists('get_option')) {
            throw new \LogicException('WordPress must be loaded before acquiring plugin upgrade locks.');
        }

        $token = bin2hex(random_bytes(16));
        $value = $token . '|' . time();
        if ($this->addOption($value)) {
            $this->token = $token;
            return true;
        }

        $existing = get_option($this->optionName, null);
        if (!is_string($existing)) {
            return false;
        }

        [$existingToken, $created] = array_pad(explode('|', $existing, 2), 2, null);
        if ($existingToken === null || $created === null || !ctype_digit($created)) {
            return false;
        }
        if (time() - (int) $created < $this->ttl) {
            return false;
        }

        // Delete only the value observed above so a concurrent claimant remains protected.
        if (!$this->deleteOptionValue($existing)) {
            return false;
        }
        if (!$this->addOption($value)) {
            return false;
        }

        $this->token = $token;
        return true;
    }

    /**
     * @phpstan-impure
     */
    private function addOption(string $value): bool
    {
        return add_option($this->optionName, $value, '', false);
    }

    private function deleteOptionValue(string $value): bool
    {
        global $wpdb;

        if (!$wpdb instanceof \wpdb) {
            throw new \LogicException('WordPress database must be loaded before reclaiming upgrade locks.');
        }

        // The table name is supplied by WordPress; values remain parameterized.
        $query = $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", // @phpstan-ignore argument.type
            $this->optionName,
            $value,
        );
        if ($query === null) {
            return false;
        }

        $result = $wpdb->query($query);

        return $result === 1;
    }

    public function release(): void
    {
        if ($this->token === null || !function_exists('get_option')) {
            return;
        }

        $existing = get_option($this->optionName, null);
        if (is_string($existing) && str_starts_with($existing, $this->token . '|')) {
            $this->deleteOptionValue($existing);
        }

        $this->token = null;
    }
}
