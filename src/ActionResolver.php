<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\Action\ActionInterface;
use webignition\BasilModels\Action\ResolvedAction;

class ActionResolver
{
    private ElementResolver $elementResolver;

    public function __construct(ElementResolver $referencedElementResolver)
    {
        $this->elementResolver = $referencedElementResolver;
    }

    public static function createResolver(): ActionResolver
    {
        return new ActionResolver(
            ElementResolver::createResolver()
        );
    }

    /**
     * @param ActionInterface $action
     * @param ProviderInterface $pageProvider
     * @param ProviderInterface $identifierProvider
     *
     * @return ActionInterface
     *
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

        if ($action->isInteraction() || $action->isInput()) {
            $identifier = $action->getIdentifier();
            $resolvedIdentifier = $this->elementResolver->resolve($identifier, $pageProvider, $identifierProvider);

            $isIdentifierResolved = $resolvedIdentifier !== $identifier;
        }

        if ($action->isInput()) {
            $value = $action->getValue();
            $resolvedValue = $this->elementResolver->resolve($value, $pageProvider, $identifierProvider);

            $isValueResolved = $resolvedValue !== $value;
        }

        if ($isIdentifierResolved || $isValueResolved) {
            $identifier = $isIdentifierResolved ? $resolvedIdentifier : $action->getIdentifier();
            $value = $isValueResolved ? $resolvedValue : $action->getValue();

            $action = new ResolvedAction($action, $identifier, $value);
        }

        return $action;
    }
}
