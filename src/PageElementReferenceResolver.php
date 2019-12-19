<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownPageException;
use webignition\BasilModelProvider\Page\PageProviderInterface;
use webignition\BasilModels\PageElementReference\PageElementReference;

class PageElementReferenceResolver
{
    private $pageResolver;

    public function __construct(PageResolver $pageResolver)
    {
        $this->pageResolver = $pageResolver;
    }

    public static function createResolver(): PageElementReferenceResolver
    {
        return new PageElementReferenceResolver(
            PageResolver::createResolver()
        );
    }

    /**
     * @param string $pageElementReference
     * @param PageProviderInterface $pageProvider
     *
     * @return string
     *
     * @throws UnknownPageElementException
     * @throws UnknownPageException
     */
    public function resolve(
        string $pageElementReference,
        PageProviderInterface $pageProvider
    ): string {
        $model = new PageElementReference(ltrim($pageElementReference, '$'));

        $page = $pageProvider->findPage($model->getImportName());
        $page = $this->pageResolver->resolve($page);

        $identifier = $page->getIdentifier($model->getElementName());

        if (is_string($identifier)) {
            return $identifier;
        }

        throw new UnknownPageElementException($model->getImportName(), $model->getElementName());
    }
}
