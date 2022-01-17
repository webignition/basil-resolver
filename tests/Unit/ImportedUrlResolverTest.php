<?php

declare(strict_types=1);

namespace webignition\BasilResolver\Tests\Unit;

use webignition\BasilModelProvider\Page\EmptyPageProvider;
use webignition\BasilModelProvider\Page\PageProvider;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\Page\Page;
use webignition\BasilResolver\ImportedUrlResolver;

class ImportedUrlResolverTest extends \PHPUnit\Framework\TestCase
{
    private ImportedUrlResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = ImportedUrlResolver::createResolver();
    }

    /**
     * @dataProvider resolveDataProvider
     */
    public function testResolve(string $url, ProviderInterface $pageProvider, string $expectedUrl): void
    {
        $resolvedUrl = $this->resolver->resolve($url, $pageProvider);

        $this->assertEquals($expectedUrl, $resolvedUrl);
    }

    /**
     * @return array<mixed>
     */
    public function resolveDataProvider(): array
    {
        return [
            'empty' => [
                'url' => '',
                'pageProvider' => new EmptyPageProvider(),
                'expectedUrl' => '',
            ],
            'literal url' => [
                'url' => 'http://example.com/',
                'pageProvider' => new EmptyPageProvider(),
                'expectedUrl' => 'http://example.com/',
            ],
            'well-formed page url reference' => [
                'url' => '$page_import_name.url',
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page('page_import_name', 'http://page.example.com/'),
                ]),
                'expectedUrl' => 'http://page.example.com/',
            ],
        ];
    }
}
