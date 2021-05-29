<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\StatementInterface;

class StatementValueElementResolver implements StatementComponentResolverInterface
{
    public function __construct(
        private ElementResolver $elementResolver
    ) {
    }

    public static function createResolver(): self
    {
        return new StatementValueElementResolver(
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
        $value = $statement->getValue();
        if (is_string($value)) {
            return new ResolvedComponent(
                ResolvedComponentInterface::TYPE_VALUE,
                $value,
                $this->elementResolver->resolve($value, $pageProvider, $identifierProvider)
            );
        }

        return null;
    }
}
