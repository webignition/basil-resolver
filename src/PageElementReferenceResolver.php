<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\Page\PageInterface;
use webignition\BasilModels\PageElementReference\PageElementReference;

class PageElementReferenceResolver
{
    public function __construct(
        private PageResolver $pageResolver
    ) {
    }

    public static function createResolver(): PageElementReferenceResolver
    {
        return new PageElementReferenceResolver(
            PageResolver::createResolver()
        );
    }

    /**
     * @throws UnknownPageElementException
     * @throws UnknownItemException
     */
    public function resolve(
        string $pageElementReference,
        ProviderInterface $pageProvider
    ): string {
        $model = new PageElementReference(ltrim($pageElementReference, '$'));

        $page = $pageProvider->find($model->getImportName());

        if ($page instanceof PageInterface) {
            $page = $this->pageResolver->resolve($page);
            $identifier = $page->getIdentifier($model->getElementName());

            if (is_string($identifier)) {
                $attributeName = $model->getAttributeName();

                return '' === $attributeName
                    ? $identifier
                    : $identifier . '.' . $attributeName;
            }
        }

        throw new UnknownPageElementException($model->getImportName(), $model->getElementName());
    }
}
