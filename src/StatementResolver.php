<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\Action\ActionInterface;
use webignition\BasilModels\Action\ResolvedAction;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\Assertion\ResolvedAssertion;
use webignition\BasilModels\EncapsulatingStatementInterface;
use webignition\BasilModels\StatementInterface;
use webignition\BasilResolver\StatementComponentResolver\StatementComponentResolverInterface;
use webignition\BasilResolver\StatementComponentResolver\StatementIdentifierElementResolver;
use webignition\BasilResolver\StatementComponentResolver\StatementIdentifierUrlResolver;
use webignition\BasilResolver\StatementComponentResolver\StatementValueElementResolver;
use webignition\BasilResolver\StatementComponentResolver\StatementValueUrlResolver;

class StatementResolver
{
    /**
     * @param StatementComponentResolverInterface[] $componentResolvers
     */
    final public function __construct(
        private array $componentResolvers
    ) {
    }

    public static function createResolver(): static
    {
        return new static([
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
        StatementInterface $statement,
        ProviderInterface $pageProvider,
        ProviderInterface $identifierProvider
    ): StatementInterface {
        $resolvedIdentifier = null;
        $resolvedValue = null;

        foreach ($this->componentResolvers as $componentResolver) {
            $resolvedComponent = $componentResolver->resolve($statement, $pageProvider, $identifierProvider);

            if ($resolvedComponent instanceof ResolvedComponentInterface && $resolvedComponent->isResolved()) {
                if (ResolvedComponentInterface::TYPE_IDENTIFIER === $resolvedComponent->getType()) {
                    $resolvedIdentifier = $resolvedComponent->getResolved();
                }

                if (ResolvedComponentInterface::TYPE_VALUE === $resolvedComponent->getType()) {
                    $resolvedValue = $resolvedComponent->getResolved();
                }
            }
        }

        $resolvedStatement = $this->createResolvedStatement($statement, $resolvedIdentifier, $resolvedValue);

        return $resolvedStatement ?? $statement;
    }

    private function createResolvedStatement(
        StatementInterface $statement,
        ?string $identifier,
        ?string $value
    ): ?EncapsulatingStatementInterface {
        $identifierIsResolved = is_string($identifier);
        $valueIsResolved = is_string($value);
        $isResolved = $identifierIsResolved || $valueIsResolved;

        if (false === $isResolved) {
            return null;
        }

        $newIdentifier = $identifier ?? $statement->getIdentifier();
        $newValue = $value ?? $statement->getValue();

        if ($statement instanceof ActionInterface) {
            return new ResolvedAction($statement, $newIdentifier, $newValue);
        }

        if ($statement instanceof AssertionInterface) {
            return new ResolvedAssertion($statement, (string) $newIdentifier, $newValue);
        }

        return null;
    }
}
