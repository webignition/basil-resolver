<?php

declare(strict_types=1);

namespace webignition\BasilResolver\Tests\Unit;

use webignition\BasilModelProvider\Page\EmptyPageProvider;
use webignition\BasilModelProvider\Page\PageProvider;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\Page\Page;
use webignition\BasilModels\Test\Configuration;
use webignition\BasilModels\Test\ConfigurationInterface;
use webignition\BasilResolver\TestConfigurationResolver;

class TestConfigurationResolverTest extends \PHPUnit\Framework\TestCase
{
    private TestConfigurationResolver $resolver;

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
        ProviderInterface $pageProvider,
        ConfigurationInterface $expectedConfiguration
    ): void {
        $resolvedConfiguration = $this->resolver->resolve($configuration, $pageProvider);

        $this->assertEquals($expectedConfiguration, $resolvedConfiguration);
    }

    /**
     * @return array<mixed>
     */
    public function resolveDataProvider(): array
    {
        return [
            'literal url' => [
                'configuration' => new Configuration('chrome', 'http://example.com/'),
                'pageProvider' => new EmptyPageProvider(),
                'expectedConfiguration' => new Configuration('chrome', 'http://example.com/'),
            ],
            'well-formed page url reference' => [
                'configuration' => new Configuration('chrome', '$page_import_name.url'),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page('page_import_name', 'http://page.example.com/'),
                ]),
                'expectedConfiguration' => new Configuration('chrome', 'http://page.example.com/'),
            ],
        ];
    }
}
