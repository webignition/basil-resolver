<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\Test\Configuration;
use webignition\BasilModels\Test\ConfigurationInterface;

class TestConfigurationResolver
{
    public function __construct(
        private ImportedUrlResolver $importedUrlResolver
    ) {
    }

    public static function createResolver(): TestConfigurationResolver
    {
        return new TestConfigurationResolver(
            ImportedUrlResolver::createResolver()
        );
    }

    /**
     * @throws UnknownItemException
     */
    public function resolve(
        ConfigurationInterface $configuration,
        ProviderInterface $pageProvider
    ): ConfigurationInterface {
        return new Configuration(
            $configuration->getBrowser(),
            $this->importedUrlResolver->resolve($configuration->getUrl(), $pageProvider)
        );
    }
}
