<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\StatementInterface;

class AssertionResolver extends AbstractStatementResolver
{
    public static function createResolver(): AssertionResolver
    {
        return new AssertionResolver([
            StatementIdentifierElementResolver::createResolver(),
            StatementValueElementResolver::createResolver(),
            StatementValueUrlResolver::createResolver(),
            StatementIdentifierUrlResolver::createResolver(),
        ]);
    }

    /**
     * @throws UnknownElementException
     * @throws UnknownPageElementException
     * @throws UnknownItemException
     */
    public function resolve(
        AssertionInterface $assertion,
        ProviderInterface $pageProvider,
        ProviderInterface $identifierProvider
    ): AssertionInterface {
        $resolvedAssertion = $this->doResolve($assertion, $pageProvider, $identifierProvider);

        return $resolvedAssertion instanceof AssertionInterface ? $resolvedAssertion : $assertion;
    }
}
