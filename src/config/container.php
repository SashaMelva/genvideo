<?php

declare(strict_types=1);

use DI\ContainerBuilder;

$builder = new ContainerBuilder();

$builder->addDefinitions(require __DIR__ . '/dependencies_console.php');

try {
    return $builder->build();
} catch (Exception $e) {
}