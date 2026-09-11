<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

$config = require __DIR__ . '/scoper.inc.php';
$config['finders'][] = Finder::create()->files()->in(__DIR__ . '/tests/Fixtures');

return $config;
