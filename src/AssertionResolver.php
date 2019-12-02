<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownIdentifierException;
use webignition\BasilModelProvider\Exception\UnknownPageException;
use webignition\BasilModelProvider\Identifier\IdentifierProviderInterface;
use webignition\BasilModelProvider\Page\PageProviderInterface;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\Assertion\ComparisonAssertionInterface;

class AssertionResolver
{
    private $elementResolver;

    public function __construct(ElementResolver $elementResolver)
    {
        $this->elementResolver = $elementResolver;
    }

    public static function createResolver(): AssertionResolver
    {
        return new AssertionResolver(
            ElementResolver::createResolver()
        );
    }

    /**
     * @param AssertionInterface $assertion
     * @param PageProviderInterface $pageProvider
     * @param IdentifierProviderInterface $identifierProvider
     *
     * @return AssertionInterface
     *
     * @throws UnknownPageElementException
     * @throws UnknownPageException
     * @throws UnknownIdentifierException
     */
    public function resolve(
        AssertionInterface $assertion,
        PageProviderInterface $pageProvider,
        IdentifierProviderInterface $identifierProvider
    ): AssertionInterface {
        $identifier = $assertion->getIdentifier();
        $resolvedIdentifier = $this->elementResolver->resolve($identifier, $pageProvider, $identifierProvider);

        if ($resolvedIdentifier !== $identifier) {
            $assertion = $assertion->withIdentifier($resolvedIdentifier);
        }

        if ($assertion instanceof ComparisonAssertionInterface) {
            $value = $assertion->getValue();
            $resolvedValue = $this->elementResolver->resolve($value, $pageProvider, $identifierProvider);

            if ($resolvedValue !== $value) {
                $assertion = $assertion->withValue($resolvedValue);
            }
        }

        return $assertion;
    }
}
