<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownIdentifierException;
use webignition\BasilModelProvider\Exception\UnknownPageException;
use webignition\BasilModelProvider\Identifier\IdentifierProviderInterface;
use webignition\BasilModelProvider\Page\PageProviderInterface;
use webignition\BasilModels\Action\ActionInterface;
use webignition\BasilModels\Action\InputActionInterface;
use webignition\BasilModels\Action\InteractionActionInterface;
use webignition\BasilModels\ElementReference\ElementReference;
use webignition\BasilModels\PageElementReference\PageElementReference;

class ActionResolver
{
    private $pageElementReferenceResolver;

    public function __construct(PageElementReferenceResolver $pageElementReferenceResolver)
    {
        $this->pageElementReferenceResolver = $pageElementReferenceResolver;
    }

    public static function createResolver(): ActionResolver
    {
        return new ActionResolver(
            PageElementReferenceResolver::createResolver()
        );
    }

    /**
     * @param ActionInterface $action
     * @param PageProviderInterface $pageProvider
     * @param IdentifierProviderInterface $identifierProvider
     *
     * @return ActionInterface
     *
     * @throws UnknownPageElementException
     * @throws UnknownPageException
     * @throws UnknownIdentifierException
     */
    public function resolve(
        ActionInterface $action,
        PageProviderInterface $pageProvider,
        IdentifierProviderInterface $identifierProvider
    ): ActionInterface {
        if ($action instanceof InteractionActionInterface) {
            $identifier = $action->getIdentifier();
            $resolvedIdentifier = $this->resolveValue($identifier, $pageProvider, $identifierProvider);

            if ($resolvedIdentifier !== $identifier) {
                $action = $action->withIdentifier($resolvedIdentifier);
            }
        }

        if ($action instanceof InputActionInterface) {
            $value = $action->getValue();
            $resolvedValue = $this->resolveValue($value, $pageProvider, $identifierProvider);

            if ($resolvedValue !== $value) {
                $action = $action->withValue($resolvedValue);
            }
        }

        return $action;
    }

    /**
     * @param string $value
     * @param PageProviderInterface $pageProvider
     * @param IdentifierProviderInterface $identifierProvider
     *
     * @return string
     *
     * @throws UnknownIdentifierException
     * @throws UnknownPageElementException
     * @throws UnknownPageException
     */
    private function resolveValue(
        string $value,
        PageProviderInterface $pageProvider,
        IdentifierProviderInterface $identifierProvider
    ): string {
        if (ElementReference::is($value)) {
            return $identifierProvider->findIdentifier((new ElementReference($value))->getElementName());
        }

        if (PageElementReference::is($value)) {
            return $this->pageElementReferenceResolver->resolve($value, $pageProvider);
        }

        return $value;
    }
}
