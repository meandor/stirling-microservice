<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/TestingConfig.php';
require __DIR__ . '/SubprocessRunner.php';

/**
 * Returns the bootstrap file the current PHPUnit process was started with.
 *
 * Subprocess-based tests must use it instead of vendor/autoload.php so that
 * Infection's mutated sources are loaded in the subprocess as well.
 */
function phpunit_bootstrap_path(): string
{
    $defaultBootstrap = __DIR__ . '/bootstrap.php';
    $configuration = phpunit_configuration_path();

    if ($configuration === null || !is_file($configuration)) {
        return $defaultBootstrap;
    }

    $contents = file_get_contents($configuration);
    if ($contents === false || preg_match('/bootstrap="([^"]+)"/', $contents, $matches) !== 1) {
        return $defaultBootstrap;
    }

    $bootstrap = $matches[1];
    if (str_starts_with($bootstrap, '/')) {
        return $bootstrap;
    }

    return dirname($configuration) . '/' . $bootstrap;
}

function phpunit_configuration_path(): ?string
{
    $arguments = $_SERVER['argv'] ?? [];
    foreach ($arguments as $index => $argument) {
        if ($argument === '--configuration' && isset($arguments[$index + 1])) {
            return $arguments[$index + 1];
        }
        if (str_starts_with($argument, '--configuration=')) {
            return substr($argument, strlen('--configuration='));
        }
    }

    return null;
}
