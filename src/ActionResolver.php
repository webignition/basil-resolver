<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\Action\ActionInterface;
use webignition\BasilModels\Action\ResolvedAction;

class ActionResolver
{
    public function __construct(
        private ElementResolver $elementResolver,
        private ImportedUrlResolver $importedUrlResolver
    ) {
    }

    public static function createResolver(): ActionResolver
    {
        return new ActionResolver(
            ElementResolver::createResolver(),
            ImportedUrlResolver::createResolver()
        );
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

        if ($action->isInteraction() || $action->isInput()) {
            $identifier = (string) $action->getIdentifier();
            $resolvedIdentifier = $this->elementResolver->resolve($identifier, $pageProvider, $identifierProvider);

            $isIdentifierResolved = $resolvedIdentifier !== $identifier;
        }

        if ($action->isInput()) {
            $value = (string) $action->getValue();
            $resolvedValue = $this->elementResolver->resolve($value, $pageProvider, $identifierProvider);

            $isValueResolved = $resolvedValue !== $value;

            if (false === $isValueResolved) {
                $resolvedValue = $this->importedUrlResolver->resolve($value, $pageProvider);

                if ($resolvedValue !== $value) {
                    $resolvedValue = '"' . $resolvedValue . '"';
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
