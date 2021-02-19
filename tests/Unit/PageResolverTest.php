<?php

declare(strict_types=1);

namespace webignition\BasilResolver\Tests\Unit;

use webignition\BasilModels\Page\Page;
use webignition\BasilModels\Page\PageInterface;
use webignition\BasilParser\PageParser;
use webignition\BasilResolver\PageResolver;
use webignition\BasilResolver\UnknownPageElementException;

class PageResolverTest extends \PHPUnit\Framework\TestCase
{
    private PageResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = PageResolver::createResolver();
    }

    /**
     * @dataProvider resolveSuccessDataProvider
     */
    public function testResolveSuccess(
        PageInterface $page,
        PageInterface $expectedPage
    ): void {
        $resolvedPage = $this->resolver->resolve($page);

        $this->assertEquals($expectedPage, $resolvedPage);
    }

    /**
     * @return array[]
     */
    public function resolveSuccessDataProvider(): array
    {
        $pageParser = PageParser::create();

        return [
            'empty page' => [
                'page' => $pageParser->parse('import_name', []),
                'expectedPage' => new Page('import_name', '', []),
            ],
            'identifiers require no resolution' => [
                'page' => $pageParser->parse('import_name', [
                    'elements' => [
                        'form' => '$".form"',
                    ],
                ]),
                'expectedPage' => new Page('import_name', '', [
                    'form' => '$".form"',
                ]),
            ],
            'direct parent reference' => [
                'page' => $pageParser->parse('import_name', [
                    'elements' => [
                        'form' => '$".form"',
                        'form_container' => '$form >> $".container"',
                    ],
                ]),
                'expectedPage' => new Page('import_name', '', [
                    'form' => '$".form"',
                    'form_container' => '$".form" >> $".container"',
                ]),
            ],
            'indirect parent reference, defined in order' => [
                'page' => $pageParser->parse('import_name', [
                    'elements' => [
                        'form' => '$".form"',
                        'form_container' => '$form >> $".container"',
                        'form_input' => '$form_container >> $".input"',
                    ],
                ]),
                'expectedPage' => new Page('import_name', '', [
                    'form' => '$".form"',
                    'form_container' => '$".form" >> $".container"',
                    'form_input' => '$".form" >> $".container" >> $".input"',
                ]),
            ],
            'indirect parent reference, defined in out of order' => [
                'page' => $pageParser->parse('import_name', [
                    'elements' => [
                        'form' => '$".form"',
                        'form_input' => '$".form" >> $".container" >> $".input"',
                        'form_container' => '$".form" >> $".container"',
                    ],
                ]),
                'expectedPage' => new Page('import_name', '', [
                    'form' => '$".form"',
                    'form_container' => '$".form" >> $".container"',
                    'form_input' => '$".form" >> $".container" >> $".input"',
                ]),
            ],
        ];
    }

    public function testResolveUnresolvableReference(): void
    {
        $pageParser = PageParser::create();

        $page = $pageParser->parse('import_name', [
            'elements' => [
                'form' => '$".form"',
                'unresolvable' => '$missing >> $".button"',
            ],
        ]);

        $this->expectExceptionObject(new UnknownPageElementException('import_name', 'missing'));

        $this->resolver->resolve($page);
    }
}
