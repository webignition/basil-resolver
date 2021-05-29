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

abstract class AbstractStatementResolver
{
    /**
     * @param StatementComponentResolverInterface[] $componentResolvers
     */
    public function __construct(
        private array $componentResolvers
    ) {
    }

    /**
     * @throws UnknownElementException
     * @throws UnknownPageElementException
     * @throws UnknownItemException
     */
    protected function doResolve(
        StatementInterface $statement,
        ProviderInterface $pageProvider,
        ProviderInterface $identifierProvider
    ): ?StatementInterface {
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

        return $this->createResolvedStatement($statement, $resolvedIdentifier, $resolvedValue);
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
