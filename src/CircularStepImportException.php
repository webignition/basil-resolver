<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

class CircularStepImportException extends \Exception
{
    public function __construct(
        private string $importName
    ) {
        parent::__construct('Circular step import "' . $importName . '"');
    }

    public function getImportName(): string
    {
        return $this->importName;
    }
}
