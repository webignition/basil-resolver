<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilContextAwareException\ContextAwareExceptionInterface;
use webignition\BasilContextAwareException\ContextAwareExceptionTrait;
use webignition\BasilContextAwareException\ExceptionContext\ExceptionContext;

class UnknownElementException extends \Exception implements ContextAwareExceptionInterface
{
    use ContextAwareExceptionTrait;

    private $elementName;

    public function __construct(string $elementName, ?string $message = null)
    {
        parent::__construct($message ?? 'Unknown element "' . $elementName . '"');

        $this->elementName = $elementName;
        $this->exceptionContext = new ExceptionContext();
    }

    public function getElementName(): string
    {
        return $this->elementName;
    }
}
