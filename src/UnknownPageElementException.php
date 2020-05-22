<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilContextAwareException\ContextAwareExceptionInterface;

class UnknownPageElementException extends UnknownElementException implements ContextAwareExceptionInterface
{
    private string $importName;

    public function __construct(string $importName, string $elementName)
    {
        parent::__construct($elementName, 'Unknown page element "' . $elementName . '" in page "' . $importName . '"');

        $this->importName = $importName;
    }

    public function getImportName(): string
    {
        return $this->importName;
    }
}
