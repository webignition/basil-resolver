<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\Assertion\AssertionInterface;

class AssertionResolver extends AbstractStatementResolver
{
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
