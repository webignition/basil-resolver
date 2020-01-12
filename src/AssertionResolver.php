<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
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
     * @param ProviderInterface $pageProvider
     * @param ProviderInterface $identifierProvider
     *
     * @return AssertionInterface
     *
     * @throws UnknownElementException
     * @throws UnknownPageElementException
     * @throws UnknownItemException
     */
    public function resolve(
        AssertionInterface $assertion,
        ProviderInterface $pageProvider,
        ProviderInterface $identifierProvider
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
