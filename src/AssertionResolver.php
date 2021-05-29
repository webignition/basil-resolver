<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\Assertion\ResolvedAssertion;

class AssertionResolver
{
    /**
     * @param StatementComponentResolverInterface[] $componentResolvers
     */
    public function __construct(
        private array $componentResolvers
    ) {
    }

    public static function createResolver(): AssertionResolver
    {
        return new AssertionResolver([
            StatementIdentifierElementResolver::createResolver(),
            StatementValueElementResolver::createResolver(),
            StatementValueUrlResolver::createResolver(),
            StatementIdentifierUrlResolver::createResolver(),
        ]);
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
        $isIdentifierResolved = false;
        $isValueResolved = false;

        $resolvedIdentifier = null;
        $resolvedValue = null;

        foreach ($this->componentResolvers as $componentResolver) {
            $resolvedComponent = $componentResolver->resolve($assertion, $pageProvider, $identifierProvider);

            if ($resolvedComponent instanceof ResolvedComponentInterface && $resolvedComponent->isResolved()) {
                if (ResolvedComponentInterface::TYPE_IDENTIFIER === $resolvedComponent->getType()) {
                    $resolvedIdentifier = $resolvedComponent->getResolved();
                    $isIdentifierResolved = true;
                }

                if (ResolvedComponentInterface::TYPE_VALUE === $resolvedComponent->getType()) {
                    $resolvedValue = $resolvedComponent->getResolved();
                    $isValueResolved = true;
                }
            }
        }

        if ($isIdentifierResolved || $isValueResolved) {
            $identifier = $isIdentifierResolved ? $resolvedIdentifier : $assertion->getIdentifier();
            $value = $isValueResolved ? $resolvedValue : $assertion->getValue();

            $assertion = new ResolvedAssertion($assertion, (string) $identifier, $value);
        }

        return $assertion;
    }
}
