<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilContextAwareException\ExceptionContext\ExceptionContextInterface;
use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\Identifier\EmptyIdentifierProvider;
use webignition\BasilModelProvider\Identifier\IdentifierProvider;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\Action\ActionInterface;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\Step\StepInterface;

class StepResolver
{
    public function __construct(
        private StatementResolver $statementResolver,
        private ElementResolver $elementResolver
    ) {
    }

    public static function createResolver(): StepResolver
    {
        return new StepResolver(
            StatementResolver::createResolver(),
            ElementResolver::createResolver()
        );
    }

    /**
     * @throws UnknownElementException
     * @throws UnknownPageElementException
     * @throws UnknownItemException
     */
    public function resolve(StepInterface $step, ProviderInterface $pageProvider): StepInterface
    {
        if ($step->requiresImportResolution()) {
            return $step;
        }

        $step = $this->resolveIdentifiers($step, $pageProvider);
        $step = $this->resolveActions($step, $pageProvider);

        return $this->resolveAssertions($step, $pageProvider);
    }

    /**
     * @throws UnknownElementException
     * @throws UnknownPageElementException
     * @throws UnknownItemException
     */
    private function resolveIdentifiers(StepInterface $step, ProviderInterface $pageProvider): StepInterface
    {
        $resolvedIdentifiers = [];
        $identifierProvider = new EmptyIdentifierProvider();

        foreach ($step->getIdentifiers() as $name => $identifier) {
            $resolvedIdentifiers[$name] = $this->elementResolver->resolve(
                $identifier,
                $pageProvider,
                $identifierProvider
            );
        }

        return $step->withIdentifiers($resolvedIdentifiers);
    }

    /**
     * @throws UnknownElementException
     * @throws UnknownPageElementException
     * @throws UnknownItemException
     */
    private function resolveActions(StepInterface $step, ProviderInterface $pageProvider): StepInterface
    {
        $resolvedActions = [];
        $identifierProvider = new IdentifierProvider($step->getIdentifiers());
        $action = null;

        try {
            foreach ($step->getActions() as $action) {
                $resolvedActions[] = $this->statementResolver->resolve($action, $pageProvider, $identifierProvider);
            }
        } catch (
            UnknownElementException |
            UnknownPageElementException |
            UnknownItemException $contextAwareException
        ) {
            if ($action instanceof ActionInterface) {
                $contextAwareException->applyExceptionContext([
                    ExceptionContextInterface::KEY_CONTENT => $action->getSource(),
                ]);
            }

            throw $contextAwareException;
        }

        $resolvedActions = array_filter($resolvedActions, function ($item) {
            return $item instanceof ActionInterface;
        });

        return $step->withActions($resolvedActions);
    }

    /**
     * @throws UnknownElementException
     * @throws UnknownPageElementException
     * @throws UnknownItemException
     */
    private function resolveAssertions(StepInterface $step, ProviderInterface $pageProvider): StepInterface
    {
        $resolvedAssertions = [];
        $identifierProvider = new IdentifierProvider($step->getIdentifiers());
        $assertion = null;

        try {
            foreach ($step->getAssertions() as $assertion) {
                $resolvedAssertions[] = $this->statementResolver->resolve(
                    $assertion,
                    $pageProvider,
                    $identifierProvider
                );
            }
        } catch (
            UnknownElementException |
            UnknownPageElementException |
            UnknownItemException $contextAwareException
        ) {
            if ($assertion instanceof AssertionInterface) {
                $contextAwareException->applyExceptionContext([
                    ExceptionContextInterface::KEY_CONTENT => $assertion->getSource(),
                ]);
            }

            throw $contextAwareException;
        }

        $resolvedAssertions = array_filter($resolvedAssertions, function ($item) {
            return $item instanceof AssertionInterface;
        });

        return $step->withAssertions($resolvedAssertions);
    }
}
