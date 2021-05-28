<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\Page\PageInterface;
use webignition\BasilModels\PageUrlReference\PageUrlReference;

class ImportedUrlResolver
{
    public static function createResolver(): ImportedUrlResolver
    {
        return new ImportedUrlResolver();
    }

    /**
     * @throws UnknownItemException
     */
    public function resolve(string $url, ProviderInterface $pageProvider): string
    {
        $pageUrlReference = new PageUrlReference($url);
        if ($pageUrlReference->isValid()) {
            $page = $pageProvider->find($pageUrlReference->getImportName());

            if ($page instanceof PageInterface) {
                $url = (string) $page->getUrl();
            }
        }

        return $url;
    }
}
