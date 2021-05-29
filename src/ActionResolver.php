<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\Action\ActionInterface;

class ActionResolver extends AbstractStatementResolver
{
    public static function createResolver(): ActionResolver
    {
        return new ActionResolver([
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
        ActionInterface $action,
        ProviderInterface $pageProvider,
        ProviderInterface $identifierProvider
    ): ActionInterface {
        $resolvedAction = $this->doResolve($action, $pageProvider, $identifierProvider);

        return $resolvedAction instanceof ActionInterface ? $resolvedAction : $action;
    }
}
