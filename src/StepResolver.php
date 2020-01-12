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
    private $actionResolver;
    private $assertionResolver;
    private $elementResolver;

    public function __construct(
        ActionResolver $actionResolver,
        AssertionResolver $assertionResolver,
        ElementResolver $elementResolver
    ) {
        $this->actionResolver = $actionResolver;
        $this->assertionResolver = $assertionResolver;
        $this->elementResolver = $elementResolver;
    }

    public static function createResolver(): StepResolver
    {
        return new StepResolver(
            ActionResolver::createResolver(),
            AssertionResolver::createResolver(),
            ElementResolver::createResolver()
        );
    }

    /**
     * @param StepInterface $step
     * @param ProviderInterface $pageProvider
     *
     * @return StepInterface
     *
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
        $step = $this->resolveAssertions($step, $pageProvider);

        return $step;
    }

    /**
     * @param StepInterface $step
     * @param ProviderInterface $pageProvider
     *
     * @return StepInterface
     *
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
     * @param StepInterface $step
     * @param ProviderInterface $pageProvider
     *
     * @return StepInterface
     *
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
                $resolvedActions[] = $this->actionResolver->resolve($action, $pageProvider, $identifierProvider);
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

        return $step->withActions($resolvedActions);
    }

    /**
     * @param StepInterface $step
     * @param ProviderInterface $pageProvider
     *
     * @return StepInterface
     *
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
                $resolvedAssertions[] = $this->assertionResolver->resolve(
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

        return $step->withAssertions($resolvedAssertions);
    }
}
