<?php

declare(strict_types=1);

namespace webignition\BasilResolver\Tests\Unit;

use webignition\BasilModelProvider\Page\PageProvider;
use webignition\BasilModelProvider\Page\PageProviderInterface;
use webignition\BasilModels\Page\Page;
use webignition\BasilResolver\PageElementReferenceResolver;
use webignition\BasilResolver\UnknownPageElementException;

class PageElementReferenceResolverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PageElementReferenceResolver
     */
    private $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = PageElementReferenceResolver::createResolver();
    }

    /**
     * @dataProvider resolveIsResolvedDataProvider
     */
    public function testResolveIsResolved(
        string $pageElementReference,
        PageProviderInterface $pageProvider,
        string $expectedIdentifier
    ) {
        $identifier = $this->resolver->resolve($pageElementReference, $pageProvider);

        $this->assertEquals($expectedIdentifier, $identifier);
    }

    public function resolveIsResolvedDataProvider(): array
    {
        return [
            'resolvable' => [
                'pageElementReference' => '$page_import_name.elements.element_name',
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'page_import_name',
                        'http://example.com/',
                        [
                            'element_name' => '.selector',
                        ]
                    )
                ]),
                'expectedIdentifier' => '.selector',
            ],
        ];
    }

    /**
     * @dataProvider resolveThrowsUnknownPageElementExceptionDataProvider
     */
    public function testResolveThrowsUnknownPageElementException(
        string $pageElementReference,
        PageProviderInterface $pageProvider,
        string $expectedExceptionMessage
    ) {
        $this->expectException(UnknownPageElementException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->resolver->resolve($pageElementReference, $pageProvider);
    }

    public function resolveThrowsUnknownPageElementExceptionDataProvider(): array
    {
        return [
            'element not present in page' => [
                'pageElementReference' => '$page_import_name.elements.element_name',
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page('page_import_name', 'http://example.com/')
                ]),
                'expectedExceptionMessage' => 'Unknown page element "element_name" in page "page_import_name"',
            ],
            'parent element not present in page' => [
                'pageElementReference' => '$page_import_name.elements.element_name',
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page('page_import_name', 'http://example.com/', [
                        'element_name' => '{{ parent_element_name }}$".element"',
                    ])
                ]),
                'expectedExceptionMessage' => 'Unknown page element "parent_element_name" in page "page_import_name"',
            ],
        ];
    }
}
