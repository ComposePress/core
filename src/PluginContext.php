<?php

declare(strict_types=1);

namespace ComposePress\Core;

final readonly class PluginContext
{
    public function __construct(
        public string $file,
        public string $slug,
        public string $version,
    ) {
        if ($this->file === '' || $this->slug === '' || $this->version === '') {
            throw new \InvalidArgumentException('Plugin file, slug, and version are required.');
        }
    }

    public function directory(): string
    {
        if (!function_exists('plugin_dir_path')) {
            throw new \LogicException('WordPress must be loaded before resolving the plugin directory.');
        }

        return plugin_dir_path($this->file);
    }

    public function url(string $path = ''): string
    {
        if (!function_exists('plugins_url')) {
            throw new \LogicException('WordPress must be loaded before resolving the plugin URL.');
        }

        return plugins_url($path, $this->file);
    }
}
