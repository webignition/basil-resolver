<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownPageException;
use webignition\BasilModelProvider\Identifier\IdentifierProviderInterface;
use webignition\BasilModelProvider\Page\PageProviderInterface;
use webignition\BasilModels\Action\ActionInterface;
use webignition\BasilModels\Action\InputActionInterface;
use webignition\BasilModels\Action\InteractionActionInterface;

class ActionResolver
{
    private $elementResolver;

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
     * @param PageProviderInterface $pageProvider
     * @param IdentifierProviderInterface $identifierProvider
     *
     * @return ActionInterface
     *
     * @throws UnknownElementException
     * @throws UnknownPageElementException
     * @throws UnknownPageException
     */
    public function resolve(
        ActionInterface $action,
        PageProviderInterface $pageProvider,
        IdentifierProviderInterface $identifierProvider
    ): ActionInterface {
        if ($action instanceof InteractionActionInterface) {
            $identifier = $action->getIdentifier();
            $resolvedIdentifier = $this->elementResolver->resolve($identifier, $pageProvider, $identifierProvider);

            if ($resolvedIdentifier !== $identifier) {
                $action = $action->withIdentifier($resolvedIdentifier);
            }
        }

        if ($action instanceof InputActionInterface) {
            $value = $action->getValue();
            $resolvedValue = $this->elementResolver->resolve($value, $pageProvider, $identifierProvider);

            if ($resolvedValue !== $value) {
                $action = $action->withValue($resolvedValue);
            }
        }

        return $action;
    }
}
