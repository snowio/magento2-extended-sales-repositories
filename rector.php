<?php

use Rector\Core\Configuration\Option;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__ . '/Api',
        __DIR__ . '/Model',
        __DIR__ . '/Plugin',
        __DIR__ . '/Setup'
    ]);
    $parameters->set(Option::SETS, [
        SetList::PHPSTAN,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::CODE_QUALITY,
        SetList::PHP_73,
        SetList::PHP_74,
    ]);
    $services = $containerConfigurator->services();
    $services->set(TypedPropertyRector::class);
};

