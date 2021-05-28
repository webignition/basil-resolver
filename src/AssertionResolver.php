<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\Assertion\ResolvedAssertion;

class AssertionResolver
{
    public function __construct(
        private ElementResolver $elementResolver
    ) {
    }

    public static function createResolver(): AssertionResolver
    {
        return new AssertionResolver(
            ElementResolver::createResolver()
        );
    }

    /**
     * @throws UnknownElementException
     * @throws UnknownPageElementException
     * @throws UnknownItemException
     */
    public function resolve(
        AssertionInterface $assertion,
        ProviderInterface $pageProvider,
        ProviderInterface $identifierProvider
    ): AssertionInterface {
        $isValueResolved = false;

        $resolvedIdentifier = null;
        $resolvedValue = null;

        $identifier = $assertion->getIdentifier();
        $resolvedIdentifier = $this->elementResolver->resolve($identifier, $pageProvider, $identifierProvider);
        $isIdentifierResolved = $resolvedIdentifier !== $identifier;

        if ($assertion->isComparison()) {
            $value = (string) $assertion->getValue();
            $resolvedValue = $this->elementResolver->resolve($value, $pageProvider, $identifierProvider);

            $isValueResolved = $resolvedValue !== $value;
        }

        if ($isIdentifierResolved || $isValueResolved) {
            $identifier = $isIdentifierResolved ? $resolvedIdentifier : $assertion->getIdentifier();
            $value = $isValueResolved ? $resolvedValue : $assertion->getValue();

            $assertion = new ResolvedAssertion($assertion, $identifier, $value);
        }

        return $assertion;
    }
}
