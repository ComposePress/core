# Dependency Isolation

ComposePress Core is published as a normal Composer library. Its source namespace remains
`ComposePress\Core`; generated scoped files are not committed to this repository.

WordPress plugins should scope bundled PHP dependencies when producing a standalone
production artifact. WordPress loads each plugin into the same PHP process, so two plugins
shipping incompatible copies of a Composer package can otherwise define the same class first.

## PHP-Scoper

The repository includes a PHP-Scoper configuration for validating that the core source can
be prefixed without changing WordPress symbols:

```sh
composer install
composer scope
```

The command writes generated files to `build/`, which is ignored by Git. The fixed prefix is
only a local smoke-test value. A consuming plugin must use a prefix unique to that plugin,
for example `ExampleVendor\\ExamplePlugin\\Dependencies`.

A plugin release build must:

1. install production dependencies from the lock file;
2. scope the selected private dependencies and the ComposePress source;
3. regenerate the scoped Composer autoloader;
4. load that autoloader from the plugin entry file;
5. omit the unscoped private dependency tree from the production artifact.

WordPress-provided symbols must remain global. The configuration explicitly protects the
functions, classes, and constants used by the core boundary, including hook registration,
plugin path helpers, `WP_Error`, `WP_Post`, `ABSPATH`, and WordPress directory constants.
A real plugin must extend this list for every host symbol it uses.

## Release requirements

A production plugin using ComposePress is not ready for release until its artifact verifies:

- two differently scoped dependency trees load in one PHP process;
- WordPress actions, filters, and lifecycle hooks execute normally;
- static uninstall callbacks remain valid after scoping;
- no unscoped private dependency classes remain reachable;
- development files and Composer aliases are absent from the artifact.

The core package does not run PHP-Scoper during normal installation and does not own the
consuming plugin's release prefix.

References:

- https://github.com/humbug/php-scoper/blob/main/docs/configuration.md
- https://github.com/humbug/php-scoper/blob/main/docs/further-reading.md
