<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\Page\PageInterface;
use webignition\BasilModels\PageUrlReference\PageUrlReference;
use webignition\BasilModels\Test\Configuration;
use webignition\BasilModels\Test\ConfigurationInterface;

class TestConfigurationResolver
{
    public static function createResolver(): TestConfigurationResolver
    {
        return new TestConfigurationResolver();
    }

    /**
     * @param ConfigurationInterface $configuration
     * @param ProviderInterface $pageProvider
     *
     * @return ConfigurationInterface
     *
     * @throws UnknownItemException
     */
    public function resolve(
        ConfigurationInterface $configuration,
        ProviderInterface $pageProvider
    ): ConfigurationInterface {
        $url = $configuration->getUrl();

        $pageUrlReference = new PageUrlReference($url);
        if ($pageUrlReference->isValid()) {
            $page = $pageProvider->find($pageUrlReference->getImportName());

            if ($page instanceof PageInterface) {
                $url = (string) $page->getUrl();
            }
        }

        return new Configuration($configuration->getBrowsers(), $url);
    }
}
