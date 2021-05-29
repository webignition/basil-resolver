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

class StatementValueUrlResolver implements StatementComponentResolverInterface
{
    public function __construct(
        private ImportedUrlResolver $importedUrlResolver
    ) {
    }

    public static function createResolver(): self
    {
        return new StatementValueUrlResolver(
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
        $value = $statement->getValue();
        if (is_string($value) && false === PageProperty::is($value)) {
            $resolvedValue = $this->importedUrlResolver->resolve($value, $pageProvider);

            if ($value !== $resolvedValue) {
                $resolvedValue = '"' . $resolvedValue . '"';
            }

            return new ResolvedComponent(
                ResolvedComponentInterface::TYPE_VALUE,
                $value,
                $resolvedValue
            );
        }

        return null;
    }
}
