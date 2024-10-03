<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToExclude(__DIR__ . '/tests')
    ->ignoreUnknownClasses([
        Deployer\Deployer::class,
        Deployer\Task\Context::class,
    ])
    ->ignoreUnknownFunctions([
        'Deployer\after',
        'Deployer\before',
        'Deployer\get',
        'Deployer\run',
        'Deployer\set',
        'Deployer\task',
        'Deployer\upload',
    ])
    ->ignoreErrorsOnPackage('deployer/deployer', [ErrorType::UNUSED_DEPENDENCY])
;
