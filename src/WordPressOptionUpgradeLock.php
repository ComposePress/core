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
        if (!function_exists('add_option') || !function_exists('get_option') || !function_exists('delete_option')) {
            throw new \LogicException('WordPress must be loaded before acquiring plugin upgrade locks.');
        }

        $token = bin2hex(random_bytes(16));
        $value = ['token' => $token, 'created' => time()];
        if ($this->addOption($value)) {
            $this->token = $token;
            return true;
        }

        $existing = get_option($this->optionName, null);
        if (!is_array($existing) || !isset($existing['created']) || time() - (int) $existing['created'] < $this->ttl) {
            return false;
        }

        delete_option($this->optionName);
        if (!$this->addOption($value)) {
            return false;
        }

        $this->token = $token;
        return true;
    }

    /**
     * @phpstan-impure
     * @param array<string, mixed> $value
     */
    private function addOption(array $value): bool
    {
        return add_option($this->optionName, $value, '', false);
    }

    public function release(): void
    {
        if ($this->token === null || !function_exists('get_option') || !function_exists('delete_option')) {
            return;
        }

        $existing = get_option($this->optionName, null);
        if (is_array($existing) && ($existing['token'] ?? null) === $this->token) {
            delete_option($this->optionName);
        }

        $this->token = null;
    }
}
