<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\StatementInterface;

class StatementIdentifierElementResolver implements StatementComponentResolverInterface
{
    public function __construct(
        private ElementResolver $elementResolver
    ) {
    }

    public static function createResolver(): self
    {
        return new StatementIdentifierElementResolver(
            ElementResolver::createResolver()
        );
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
    ): ?ResolvedComponentInterface {
        $identifier = $statement->getIdentifier();
        if (is_string($identifier)) {
            return new ResolvedComponent(
                ResolvedComponentInterface::TYPE_IDENTIFIER,
                $identifier,
                $this->elementResolver->resolve($identifier, $pageProvider, $identifierProvider)
            );
        }

        return null;
    }
}
