<?php

declare(strict_types=1);

$prefix = getenv('COMPOSEPRESS_SCOPE_PREFIX') ?: 'ComposePressScoped';
if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$/', $prefix)) {
    fwrite(STDERR, "COMPOSEPRESS_SCOPE_PREFIX must be a valid namespace prefix.\n");
    exit(1);
}

$command = implode(' ', [
    escapeshellarg(PHP_BINARY),
    escapeshellarg(__DIR__ . '/../vendor/bin/php-scoper'),
    'add-prefix',
    '--config=scoper.inc.php',
    '--prefix=' . escapeshellarg($prefix),
    '--output-dir=build',
    '--force',
]);

passthru($command, $exitCode);
exit($exitCode);
