<?php

declare(strict_types=1);

use In2code\In2publishCore\Features\LogsIntegration\Controller\LogController;
use In2code\In2publishCore\Utility\ExtensionUtility;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator) {
    if (ExtensionUtility::isLoaded('logs')) {
        $services = $configurator->services();
        $defaults = $services->defaults();
        $defaults->autowire(true);
        $defaults->autoconfigure(true);
        $defaults->private();

        $services->load(
            'In2code\\In2publishCore\\Features\\LogsIntegration\\',
            __DIR__ . '/../../../Classes/Features/LogsIntegration/*',
        );

        $services->set(LogController::class)
                 ->tag(
                     'in2publish_core.admin_tool',
                     [
                         'title' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.logs',
                         'description' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.logs.description',
                         'actions' => 'filter,delete,deleteAlike',
                     ],
                 );
    }
};
