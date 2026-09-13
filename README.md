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

Lifecycle capabilities are opt-in and independent. Implement `PluginActivator` for activation work and `PluginDeactivator` for deactivation work; uninstall remains a separate static `PluginUninstall` contract. Pass only the capabilities the plugin needs:

Requirements are explicit checks that run before subscribers are registered. Each `PluginRequirement` returns a `RequirementResult`; unmet results are reported through `RequirementsNotMet` instead of silently skipping boot.

```php
$plugin = new Plugin(
    context: new PluginContext(__FILE__, 'example-plugin', '1.0.0'),
    requirements: [new RequiresWooCommerce('8.0')],
);
```

```php
$plugin = new Plugin(
    context: new PluginContext(__FILE__, 'example-plugin', '1.0.0'),
    activator: new InstallPlugin($migrator),
    deactivator: new DisablePlugin($scheduler),
    uninstaller: UninstallPlugin::class,
);
```

## Testing

The `ComposePress\Core\Testing` namespace ships pure test doubles for the runtime boundary. They record calls as ordinary objects — no WordPress functions are invoked, so they work in unit tests, integration tests, and any test setup:

```php
use ComposePress\Core\Testing\RecordingActivator;
use ComposePress\Core\Testing\RecordingDeactivator;
use ComposePress\Core\Testing\RecordingHooks;
use ComposePress\Core\Testing\RecordingSubscriber;
use ComposePress\Core\Testing\SpyingUninstall;

$hooks = new RecordingHooks();
$subscriber = new RecordingSubscriber(['init', 'save_post']);

$plugin = new Plugin(
    context: new PluginContext(__FILE__, 'example-plugin', '1.0.0'),
    subscribers: [$subscriber],
    hooks: $hooks,
);

$plugin->boot();

self::assertSame(['init', 'save_post'], $hooks->actionNames());
self::assertCount(1, $hooks->actionsFor('save_post'));
self::assertSame(1, $subscriber->invocations());
```

- `RecordingHooks` (implements `Hooks`) records every action/filter registration and removal, including callback, priority, and argument count; asserts through `actionsFor()`, `filtersFor()`, `removedActionsFor()`, `removedFiltersFor()`, `actionNames()`, `filterNames()`, `hasAction()`, and `hasFilter()`.
- `RecordingSubscriber` (implements `HookSubscriber`) captures each `subscribe()` invocation and the `Hooks` instance it received.
- `RecordingActivator` and `RecordingDeactivator` record each activation/deactivation call with its `networkWide` flag.
- `SpyingUninstall` (implements `static PluginUninstall::uninstall()`) records uninstall invocations for use as an uninstaller class string.

`RecordingHooks` delegates its registration state to the real `WP_Hook` engine (replace-on-re-add, unique callback ids via `_wp_filter_build_unique_id()`, exact-priority removal, and `hasAction()`/`hasFilter()` returning the registered priority (`int`) or `false`, mirroring `has_action()`/`has_filter()`) and adds only a call-history recorder. The engine is discovered automatically by `WordPressHookEngine`: the running WordPress installation when integration testing (or via `WP_CORE_DIR`), or a `roots/wordpress-no-content` dev dependency for plain unit tests, whether it was installed into `var/wordpress` (this package's `extra.wordpress-install-dir`), the Roots installer's default `wordpress/` directory, or its vendor fallback location when the installer plugin is absent. No bootstrap wiring is required in consumer projects.

These fakes are part of the public surface and are versioned with core. Projects consuming this library can depend on them for their own tests and upgrade within normal SemVer guarantees.

## Development

```sh
composer install
composer test
composer analyse
```

This is a clean-slate rewrite. The historical API and implementation are retired.
