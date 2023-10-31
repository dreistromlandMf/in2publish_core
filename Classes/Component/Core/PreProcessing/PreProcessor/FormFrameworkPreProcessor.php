<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\Resolver\FormFrameworkResolver;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;

class FormFrameworkPreProcessor extends AbstractProcessor
{
    protected string $type = 'select';

    public function getTable(): string
    {
        return 'tt_content/pi_flexform/*,form_formframework';
    }

    public function getColumn(): string
    {
        return 'settings.persistenceIdentifier';
    }

    protected function buildResolver(string $table, string $column, array $processedTca): ?Resolver
    {
        return new FormFrameworkResolver();
    }
}
