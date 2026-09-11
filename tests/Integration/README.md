# Integration tests

Integration tests require a configured WordPress test suite and a database.
Set `WP_TESTS_DIR` to the suite path, then run:

```sh
WP_TESTS_DIR=/tmp/wordpress-tests-lib composer test:integration
```

The standard WordPress test suite can be installed with WP-CLI's
`install-wp-tests.sh` helper. The repository's integration workflow provisions
MySQL and installs the suite automatically. Keep local test environments outside
this repository; Composer dependencies and test fixtures do not belong in the package.

Integration tests verify host-boundary assumptions, such as WordPress hook
execution and lifecycle registration. Keep business behavior in `tests/Unit`.
