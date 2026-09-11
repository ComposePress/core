# Contributing

## Requirements

- PHP 8.2 or newer
- Composer
- Docker, PHP MySQL support, and a configured WordPress test suite for integration tests

## Checks

Install dependencies and run the same checks used by CI:

```sh
composer install
composer validate --strict
composer test
composer analyse
composer style
```

Integration tests require `WP_TESTS_DIR` and a test database configured using the
WordPress test suite. The local command is:

```sh
WP_TESTS_DIR=/tmp/wordpress-tests-lib composer test:integration
```

Keep WordPress and database fixtures outside the repository. Unit tests must not
require WordPress.

## Design constraints

- Keep plugin metadata explicit at the composition root.
- Prefer constructor injection over containers or global registries.
- Keep WordPress calls in adapters and subscribers.
- Add framework abstractions only when a real plugin demonstrates their value.
