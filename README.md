# ComposePress Core

ComposePress Core is a small runtime boundary for WordPress plugins. It coordinates explicit plugin metadata, hook subscribers, and plugin lifecycle handlers without imposing a domain model or dependency container.

## Requirements

- PHP 8.2 or newer
- WordPress loaded before WordPress-specific context or hook operations

## Bootstrap

```php
use ComposePress\Core\Plugin;
use ComposePress\Core\PluginContext;
use ComposePress\Core\WordPressHooks;

$plugin = new Plugin(
    context: new PluginContext(__FILE__, 'example-plugin', '1.0.0'),
    subscribers: [new RegisterContentTypes()],
    hooks: new WordPressHooks(),
);

$plugin->boot();
```

Subscribers receive their application dependencies through constructors and register only WordPress adapters. Business services do not need to depend on ComposePress.

## Development

```sh
composer install
composer test
composer analyse
```

This is a clean-slate rewrite. The historical API and implementation are retired.
