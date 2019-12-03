<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

class CircularStepImportException extends \Exception
{
    private $importName = '';

    public function __construct(string $importName)
    {
        $this->importName = $importName;

        parent::__construct('Circular step import "' . $importName . '"');
    }

    public function getImportName(): string
    {
        return $this->importName;
    }
}
