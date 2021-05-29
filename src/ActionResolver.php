<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\Action\ActionInterface;
use webignition\BasilModels\Action\ResolvedAction;

class ActionResolver
{
    /**
     * @param StatementComponentResolverInterface[] $componentResolvers
     */
    public function __construct(
        private array $componentResolvers
    ) {
    }

    public static function createResolver(): ActionResolver
    {
        return new ActionResolver([
            StatementIdentifierElementResolver::createResolver(),
            StatementValueElementResolver::createResolver(),
            StatementValueUrlResolver::createResolver(),
        ]);
    }

    /**
     * @throws UnknownElementException
     * @throws UnknownPageElementException
     * @throws UnknownItemException
     */
    public function resolve(
        ActionInterface $action,
        ProviderInterface $pageProvider,
        ProviderInterface $identifierProvider
    ): ActionInterface {
        $isIdentifierResolved = false;
        $isValueResolved = false;

        $resolvedIdentifier = null;
        $resolvedValue = null;

        foreach ($this->componentResolvers as $componentResolver) {
            $resolvedComponent = $componentResolver->resolve($action, $pageProvider, $identifierProvider);

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
            $identifier = $isIdentifierResolved ? $resolvedIdentifier : $action->getIdentifier();
            $value = $isValueResolved ? $resolvedValue : $action->getValue();

            $action = new ResolvedAction($action, $identifier, $value);
        }

        return $action;
    }
}
