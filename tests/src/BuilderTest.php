<?php declare(strict_types=1);

namespace DanBettles\Reggie\Tests;

use DanBettles\Reggie\Builder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function explode;

use const false;
use const true;

class BuilderTest extends TestCase
{
    /** @return array<mixed[]> */
    public static function providesRegexStrings(): array
    {
        return [
            'Empty pattern' => [
                '~~',
                new Builder(),
            ],
            'Empty pattern with flags' => [
                '~~ism',
                (new Builder())
                    ->setFlags('ism')
                ,
            ],
            'Paths to exclude' => [
                '~^/path/to/symfony/project/(vendor|var|node_modules)/~',
                (function (): Builder {
                    $builder = new Builder();

                    return $builder
                        ->anchorLeft()
                        ->addLiteral("/path/to/symfony/project/")
                        ->addSubpattern($builder->listOfAlternatives(['vendor', 'var', 'node_modules'], quoteEach: true))
                        ->addLiteral('/')
                    ;
                })(),
            ],
            'Paths to include' => [
                '~\.php$~i',
                (new Builder())
                    ->setFlags('i')
                    ->anchorRight()
                    ->addLiteral('.php')
                ,
            ],
        ];
    }

    #[DataProvider('providesRegexStrings')]
    public function testTostringReturnsARegexString(
        string $expected,
        Builder $builder,
    ): void {
        $this->assertSame($expected, $builder->toString());
        $this->assertSame($builder->toString(), (string) $builder);
    }

    /** @return array<mixed[]> */
    public static function providesSubpatterns(): array
    {
        return [
            [
                '~(foo)~',
                ['foo'],
            ],
            [
                '~(?:foo)~',
                ['foo', 'capturing' => false],
            ],
        ];
    }

    /** @param string[] $methodArgs */
    #[DataProvider('providesSubpatterns')]
    public function testAddsubpattern(
        string $expectedRegex,
        array $methodArgs,
    ): void {
        $builder = new Builder();
        $something = $builder->addSubpattern(...$methodArgs);

        $this->assertSame($expectedRegex, $builder->toString());
        $this->assertSame($builder->toString(), (string) $builder);
        $this->assertSame($builder, $something);
    }

    /** @return array<mixed[]> */
    public static function providesListsOfAlternatives(): array
    {
        return [
            [
                'foo',
                ['foo'],
            ],
            [
                'foo|bar',
                ['foo', 'bar'],
            ],
            [
                'foo|',
                ['foo', ''],
            ],
            [
                'foo.bar|baz.qux',  // N.B. Not quoted by default
                ['foo.bar', 'baz.qux'],
            ],
        ];
    }

    /** @param string[] $input */
    #[DataProvider('providesListsOfAlternatives')]
    public function testListofalternativesCreatesAListOfAlternatives(
        string $expected,
        array $input,
    ): void {
        $builder = new Builder();

        $this->assertSame($expected, $builder->listOfAlternatives($input));
    }

    public function testListofalternativesQuotesEachAlternative(): void
    {
        $builderMock = $this
            ->getMockBuilder(Builder::class)
            ->onlyMethods(['quote'])
            ->getMock()
        ;

        $matcher = $this->exactly(2);

        $builderMock
            ->expects($matcher)
            ->method('quote')
            ->willReturnCallback(function (string $key, string $value) use ($matcher): void {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals('foo.bar', $value),
                    2 => $this->assertEquals('baz.qux', $value),
                    default => $this->fail(),
                };
            })
            ->willReturnOnConsecutiveCalls('foo\.bar', 'baz\.qux')
        ;

        /** @var Builder $builderMock */

        $this->assertSame(
            'foo\.bar|baz\.qux',
            $builderMock->listOfAlternatives(['foo.bar', 'baz.qux'], quoteEach: true),
        );
    }

    /** @return array<mixed[]> */
    public static function providesQuotedStrings(): array
    {
        $returnValue = [
            [
                '\~',  // Because "~" is the default delimiter
                '~',
            ],
        ];

        $specialChars = explode(
            ' ',
            // See https://www.php.net/manual/en/function.preg-quote.php#refsect1-function.preg-quote-description
            '. \ + * ? [ ^ ] $ ( ) { } = ! < > | : - #',
        );

        foreach ($specialChars as $specialChar) {
            $returnValue[] = [
                '\\' . $specialChar,
                $specialChar,
            ];
        }

        return $returnValue;
    }

    #[DataProvider('providesQuotedStrings')]
    public function testQuote(
        string $expected,
        string $input,
    ): void {
        $builder = new Builder();

        $this->assertSame($expected, $builder->quote($input));
    }

    public function testFlagsAccessors(): void
    {
        $builder = new Builder();

        $this->assertSame('', $builder->getFlags());

        $something = $builder->setFlags('i');
        $this->assertSame('i', $builder->getFlags());
        $this->assertSame($builder, $something);

        $builder->setFlags('s');
        $this->assertSame('s', $builder->getFlags());

        $builder->setFlags('');
        $this->assertSame('', $builder->getFlags());
    }

    /** @return array<mixed[]> */
    public static function providesWholeWordPatterns(): array
    {
        return [
            [
                '~\bfoo\b~',
                ['foo'],
            ],
            [
                '~\b(foo)\b~',
                ['foo', 'captureWord' => true],
            ],
        ];
    }

    /** @param string[] $methodArgs */
    #[DataProvider('providesWholeWordPatterns')]
    public function testAddwholeword(
        string $expectedRegex,
        array $methodArgs,
    ): void {
        $builder = new Builder();
        $something = $builder->addWholeWord(...$methodArgs);

        $this->assertSame($expectedRegex, $builder->toString());
        $this->assertSame($builder->toString(), (string) $builder);
        $this->assertSame($builder, $something);
    }

    public function testBackslash(): void
    {
        $builder = new Builder();

        $this->assertSame('\\\\', $builder->backslash());
        $this->assertSame($builder->backslash(), $builder->slosh());
    }

    /** @return array<mixed[]> */
    public static function providesChunks(): array
    {
        return [
            [
                '~~',
                [''],
            ],
            [
                '~foo~',
                ['foo'],
            ],
        ];
    }

    /** @param string[] $methodArgs */
    #[DataProvider('providesChunks')]
    public function testAddAddsAnyKindOfChunk(
        string $expectedRegex,
        array $methodArgs,
    ): void {
        $builder = new Builder();
        $something = $builder->add(...$methodArgs);

        $this->assertSame($expectedRegex, $builder->toString());
        $this->assertSame($builder->toString(), (string) $builder);
        $this->assertSame($builder, $something);
    }

    public function testAddQuotesTheString(): void
    {
        $builderMock = $this
            ->getMockBuilder(Builder::class)
            ->onlyMethods(['quote'])
            ->getMock()
        ;

        $builderMock
            ->expects($this->once())
            ->method('quote')
            ->with('foo.bar')
            ->willReturn('foo\.bar')
        ;

        /** @var Builder $builderMock */

        $something = $builderMock->add('foo.bar', quote: true);

        $this->assertSame('~foo\.bar~', $builderMock->toString());
        $this->assertSame($builderMock->toString(), (string) $builderMock);
        $this->assertSame($builderMock, $something);
    }

    public function testAnchorleftCausesTheRegexToBeAnchoredOnTheLeftSide(): void
    {
        $builder = new Builder();
        $builder->add('foo');
        $something = $builder->anchorLeft();

        $this->assertSame('~^foo~', $builder->toString());
        $this->assertSame($builder->toString(), (string) $builder);
        $this->assertSame($builder, $something);

        $builder->anchorLeft(false);

        $this->assertSame('~foo~', $builder->toString());
    }

    public function testAnchorrightCausesTheRegexToBeAnchoredOnTheRightSide(): void
    {
        $builder = new Builder();
        $builder->add('foo');
        $something = $builder->anchorRight();

        $this->assertSame('~foo$~', $builder->toString());
        $this->assertSame($builder->toString(), (string) $builder);
        $this->assertSame($builder, $something);

        $builder->anchorRight(false);

        $this->assertSame('~foo~', $builder->toString());
    }

    public function testAnchorbothCausesTheRegexToBeAnchoredOnBothSides(): void
    {
        $builder = new Builder();
        $builder->add('foo');
        $something = $builder->anchorBoth();

        $this->assertSame('~^foo$~', $builder->toString());
        $this->assertSame($builder->toString(), (string) $builder);
        $this->assertSame($builder, $something);

        $builder->anchorBoth(false);

        $this->assertSame('~foo~', $builder->toString());
    }

    public function testAddliteral(): void
    {
        $builderMock = $this
            ->getMockBuilder(Builder::class)
            ->onlyMethods(['quote'])
            ->getMock()
        ;

        $builderMock
            ->expects($this->once())
            ->method('quote')
            ->with('{{ value }}')
            ->willReturn('\{\{ value \}\}')
        ;

        /** @var Builder $builderMock */

        $something = $builderMock->addLiteral('{{ value }}');

        $this->assertSame('~\{\{ value \}\}~', $builderMock->toString());
        $this->assertSame($builderMock->toString(), (string) $builderMock);
        $this->assertSame($builderMock, $something);
    }
}
