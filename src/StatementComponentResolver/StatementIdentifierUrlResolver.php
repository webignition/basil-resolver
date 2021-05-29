<?php

declare(strict_types=1);

namespace webignition\BasilResolver\StatementComponentResolver;

use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\PageProperty\PageProperty;
use webignition\BasilModels\StatementInterface;
use webignition\BasilResolver\ImportedUrlResolver;
use webignition\BasilResolver\ResolvedComponent;
use webignition\BasilResolver\ResolvedComponentInterface;

class StatementIdentifierUrlResolver implements StatementComponentResolverInterface
{
    public function __construct(
        private ImportedUrlResolver $importedUrlResolver
    ) {
    }

    public static function createResolver(): self
    {
        return new StatementIdentifierUrlResolver(
            ImportedUrlResolver::createResolver()
        );
    }

    /**
     * @throws UnknownItemException
     */
    public function resolve(
        StatementInterface $statement,
        ProviderInterface $pageProvider,
        ProviderInterface $identifierProvider
    ): ?ResolvedComponentInterface {
        $identifier = $statement->getIdentifier();

        if (is_string($identifier) && false === PageProperty::is($identifier)) {
            $resolvedIdentifier = $this->importedUrlResolver->resolve($identifier, $pageProvider);

            if ($identifier !== $resolvedIdentifier) {
                $resolvedIdentifier = '"' . $resolvedIdentifier . '"';
            }

            return new ResolvedComponent(
                ResolvedComponentInterface::TYPE_IDENTIFIER,
                $identifier,
                $resolvedIdentifier
            );
        }

        return null;
    }
}
