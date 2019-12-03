<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownPageException;
use webignition\BasilModelProvider\Page\PageProviderInterface;
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
     * @param PageProviderInterface $pageProvider
     *
     * @return ConfigurationInterface
     *
     * @throws UnknownPageException
     */
    public function resolve(
        ConfigurationInterface $configuration,
        PageProviderInterface $pageProvider
    ): ConfigurationInterface {
        $url = $configuration->getUrl();

        $pageUrlReference = new PageUrlReference($url);
        if ($pageUrlReference->isValid()) {
            $page = $pageProvider->findPage($pageUrlReference->getImportName());
            $url = (string) $page->getUrl();
        }

        return new Configuration($configuration->getBrowser(), $url);
    }
}
