<?php

declare(strict_types=1);

namespace webignition\BasilResolver\StatementComponentResolver;

use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilResolver\ElementResolver;
use webignition\BasilResolver\ResolvedComponent;
use webignition\BasilResolver\UnknownElementException;
use webignition\BasilResolver\UnknownPageElementException;

class ComponentElementResolver implements StatementComponentResolverInterface
{
    public function __construct(
        private ElementResolver $elementResolver
    ) {
    }

    public static function createResolver(): self
    {
        return new ComponentElementResolver(
            ElementResolver::createResolver()
        );
    }

    /**
     * @throws UnknownElementException
     * @throws UnknownPageElementException
     * @throws UnknownItemException
     */
    public function resolve(
        ?string $data,
        ProviderInterface $pageProvider,
        ProviderInterface $identifierProvider
    ): ?ResolvedComponent {
        if (is_string($data)) {
            return new ResolvedComponent(
                $data,
                $this->elementResolver->resolve($data, $pageProvider, $identifierProvider)
            );
        }

        return null;
    }
}
