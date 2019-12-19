<?php

declare(strict_types=1);

namespace webignition\BasilResolver\Tests\Unit;

use webignition\BasilModelProvider\Page\EmptyPageProvider;
use webignition\BasilModelProvider\Page\PageProvider;
use webignition\BasilModelProvider\Page\PageProviderInterface;
use webignition\BasilModels\Page\Page;
use webignition\BasilModels\Test\Configuration;
use webignition\BasilModels\Test\ConfigurationInterface;
use webignition\BasilResolver\TestConfigurationResolver;

class TestConfigurationResolverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TestConfigurationResolver
     */
    private $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = TestConfigurationResolver::createResolver();
    }

    /**
     * @dataProvider resolveDataProvider
     */
    public function testResolve(
        ConfigurationInterface $configuration,
        PageProviderInterface $pageProvider,
        ConfigurationInterface $expectedConfiguration
    ) {
        $resolvedConfiguration = $this->resolver->resolve($configuration, $pageProvider);

        $this->assertEquals($expectedConfiguration, $resolvedConfiguration);
    }

    public function resolveDataProvider(): array
    {
        return [
            'empty' => [
                'configuration' => new Configuration('', ''),
                'pageProvider' => new EmptyPageProvider(),
                'expectedConfiguration' => new Configuration('', ''),
            ],
            'browser only' => [
                'configuration' => new Configuration('chrome', ''),
                'pageProvider' => new EmptyPageProvider(),
                'expectedConfiguration' => new Configuration('chrome', ''),
            ],
            'literal url' => [
                'configuration' => new Configuration('chrome', 'http://example.com/'),
                'pageProvider' => new EmptyPageProvider(),
                'expectedConfiguration' => new Configuration('chrome', 'http://example.com/'),
            ],
            'well-formed page url reference' => [
                'configuration' => new Configuration('chrome', 'page_import_name.url'),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page('page_import_name', 'http://page.example.com/'),
                ]),
                'expectedConfiguration' => new Configuration('chrome', 'http://page.example.com/'),
            ],
        ];
    }
}
