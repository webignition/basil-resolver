<?php

declare(strict_types=1);

namespace webignition\BasilResolver\Tests\Unit;

use webignition\BasilModelProvider\Page\PageProvider;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\Page\Page;
use webignition\BasilResolver\PageElementReferenceResolver;
use webignition\BasilResolver\UnknownPageElementException;

class PageElementReferenceResolverTest extends \PHPUnit\Framework\TestCase
{
    private PageElementReferenceResolver $resolver;

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
        ProviderInterface $pageProvider,
        string $expectedIdentifier
    ): void {
        $identifier = $this->resolver->resolve($pageElementReference, $pageProvider);

        $this->assertEquals($expectedIdentifier, $identifier);
    }

    /**
     * @return array[]
     */
    public function resolveIsResolvedDataProvider(): array
    {
        return [
            'element reference' => [
                'pageElementReference' => '$page_import_name.elements.element_name',
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'page_import_name',
                        'http://example.com/',
                        [
                            'element_name' => '$".selector"',
                        ]
                    )
                ]),
                'expectedIdentifier' => '$".selector"',
            ],
            'attribute reference' => [
                'pageElementReference' => '$page_import_name.elements.element_name.attribute_name',
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'page_import_name',
                        'http://example.com/',
                        [
                            'element_name' => '$".selector"',
                        ]
                    )
                ]),
                'expectedIdentifier' => '$".selector".attribute_name',
            ],
        ];
    }

    /**
     * @dataProvider resolveThrowsUnknownPageElementExceptionDataProvider
     */
    public function testResolveThrowsUnknownPageElementException(
        string $pageElementReference,
        ProviderInterface $pageProvider,
        string $expectedExceptionMessage
    ): void {
        $this->expectException(UnknownPageElementException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->resolver->resolve($pageElementReference, $pageProvider);
    }

    /**
     * @return array[]
     */
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
                        'element_name' => '$parent_element_name >> $".element"',
                    ])
                ]),
                'expectedExceptionMessage' => 'Unknown page element "parent_element_name" in page "page_import_name"',
            ],
        ];
    }
}
