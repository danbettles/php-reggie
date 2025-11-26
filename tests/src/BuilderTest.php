<?php declare(strict_types=1);

namespace DanBettles\Reggie\Tests;

use DanBettles\Reggie\Builder;
use DanBettles\Reggie\Regex;
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
                        ->anchorStart()
                        ->addLiteral("/path/to/symfony/project/")
                        ->addSubpattern($builder->listOfAlternatives(['vendor', 'var', 'node_modules'], quoteEach: true))
                        ->addLiteral('/')
                    ;
                })(),
            ],
            'Paths to include' => [
                '~\.php$~i',
                (new Builder())
                    ->caseInsensitive()
                    ->anchorEnd()
                    ->addLiteral('.php')
                ,
            ],
        ];
    }

    #[DataProvider('providesRegexStrings')]
    public function testBuildstringReturnsARegexString(
        string $expected,
        Builder $builder,
    ): void {
        $this->assertSame($expected, $builder->buildString());
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

        $this->assertSame($expectedRegex, $builder->buildString());
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

    public function testListofalternativesCanQuoteEachAlternative(): void
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
        $this->assertSame('~~i', $builder->buildString());

        $builder->setFlags('s');
        $this->assertSame('s', $builder->getFlags());
        $this->assertSame('~~s', $builder->buildString());

        $builder->setFlags('');
        $this->assertSame('', $builder->getFlags());
        $this->assertSame('~~', $builder->buildString());
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

        $this->assertSame($expectedRegex, $builder->buildString());
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

        $this->assertSame($expectedRegex, $builder->buildString());
        $this->assertSame($builder, $something);
    }

    public function testAddCanQuoteTheInput(): void
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

        $this->assertSame('~foo\.bar~', $builderMock->buildString());
        $this->assertSame($builderMock, $something);
    }

    public function testAnchorstartCausesTheRegexToBeAnchoredAtTheStart(): void
    {
        $builder = new Builder();
        $builder->add('foo');
        $something = $builder->anchorStart();

        $this->assertSame('~^foo~', $builder->buildString());
        $this->assertSame($builder, $something);

        $builder->anchorStart(false);

        $this->assertSame('~foo~', $builder->buildString());
    }

    public function testAnchorendCausesTheRegexToBeAnchoredAtTheEnd(): void
    {
        $builder = new Builder();
        $builder->add('foo');
        $something = $builder->anchorEnd();

        $this->assertSame('~foo$~', $builder->buildString());
        $this->assertSame($builder, $something);

        $builder->anchorEnd(false);

        $this->assertSame('~foo~', $builder->buildString());
    }

    public function testAnchorbothCausesTheRegexToBeAnchoredOnBothSides(): void
    {
        $builder = new Builder();
        $builder->add('foo');
        $something = $builder->anchorBoth();

        $this->assertSame('~^foo$~', $builder->buildString());
        $this->assertSame($builder, $something);

        $builder->anchorBoth(false);

        $this->assertSame('~foo~', $builder->buildString());
    }

    public function testAnchoredFlagIsRemovedAndACaretIsAddedToTheStartOfThePattern(): void
    {
        $regexStr = (new Builder())
            ->setFlags('A')
            ->add('foo')
            ->buildString()
        ;

        $this->assertSame('~^foo~', $regexStr);
    }

    public function testAnchoredendFlagIsRemovedAndADollarIsAddedToTheEndOfThePattern(): void
    {
        $regexStr = (new Builder())
            ->setFlags('Z')
            ->add('foo')
            ->buildString()
        ;

        $this->assertSame('~foo$~', $regexStr);
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

        $this->assertSame('~\{\{ value \}\}~', $builderMock->buildString());
        $this->assertSame($builderMock, $something);
    }

    /** @return array<mixed[]> */
    public static function providesCasesensitiveFlags(): array
    {
        return [
            // Starting sensitive:
            [
                '',
                'initialFlags' => '',
                'methodArgs' => [],
            ],
            [
                '',
                'initialFlags' => '',
                'methodArgs' => [true],
            ],
            [
                'i',
                'initialFlags' => '',
                'methodArgs' => [false],
            ],
            // Starting insensitive:
            [
                '',
                'initialFlags' => 'i',
                'methodArgs' => [],
            ],
            [
                '',
                'initialFlags' => 'i',
                'methodArgs' => [true],
            ],
            [
                'i',
                'initialFlags' => 'i',
                'methodArgs' => [false],
            ],
            // Starting sensitive with others:
            [
                'sm',
                'initialFlags' => 'sm',
                'methodArgs' => [],
            ],
            [
                'sm',
                'initialFlags' => 'sm',
                'methodArgs' => [true],
            ],
            [
                'smi',
                'initialFlags' => 'sm',
                'methodArgs' => [false],
            ],
            // Starting insensitive with others:
            [
                'sm',
                'initialFlags' => 'sim',
                'methodArgs' => [],
            ],
            [
                'sm',
                'initialFlags' => 'sim',
                'methodArgs' => [true],
            ],
            [
                'sim',
                'initialFlags' => 'sim',
                'methodArgs' => [false],
            ],
        ];
    }

    /** @param array{0?:bool} $methodArgs */
    #[DataProvider('providesCasesensitiveFlags')]
    public function testCasesensitive(
        string $expected,
        string $initialFlags,
        array $methodArgs,
    ): void {
        $builder = (new Builder())
            ->setFlags($initialFlags)
        ;

        $something = $builder->caseSensitive(...$methodArgs);

        $this->assertSame($expected, $builder->getFlags());
        $this->assertSame($builder, $something);
    }

    /** @return array<mixed[]> */
    public static function providesCaseinsensitiveFlags(): array
    {
        return [
            // Starting sensitive:
            [
                'i',
                'initialFlags' => '',
                'methodArgs' => [],
            ],
            [
                'i',
                'initialFlags' => '',
                'methodArgs' => [true],
            ],
            [
                '',
                'initialFlags' => '',
                'methodArgs' => [false],
            ],
            // Starting insensitive:
            [
                'i',
                'initialFlags' => 'i',
                'methodArgs' => [],
            ],
            [
                'i',
                'initialFlags' => 'i',
                'methodArgs' => [true],
            ],
            [
                '',
                'initialFlags' => 'i',
                'methodArgs' => [false],
            ],
            // Starting sensitive with others:
            [
                'smi',
                'initialFlags' => 'sm',
                'methodArgs' => [],
            ],
            [
                'smi',
                'initialFlags' => 'sm',
                'methodArgs' => [true],
            ],
            [
                'sm',
                'initialFlags' => 'sm',
                'methodArgs' => [false],
            ],
            // Starting insensitive with others:
            [
                'sim',
                'initialFlags' => 'sim',
                'methodArgs' => [],
            ],
            [
                'sim',
                'initialFlags' => 'sim',
                'methodArgs' => [true],
            ],
            [
                'sm',
                'initialFlags' => 'sim',
                'methodArgs' => [false],
            ],
        ];
    }

    /** @param array{0?:bool} $methodArgs */
    #[DataProvider('providesCaseinsensitiveFlags')]
    public function testCaseinsensitive(
        string $expected,
        string $initialFlags,
        array $methodArgs,
    ): void {
        $builder = (new Builder())
            ->setFlags($initialFlags)
        ;

        $something = $builder->caseInsensitive(...$methodArgs);

        $this->assertSame($expected, $builder->getFlags());
        $this->assertSame($builder, $something);
    }

    public function testBuildReturnsARegex(): void
    {
        $actual = (new Builder())
            ->caseInsensitive()
            ->anchorBoth()
            ->addLiteral('foo')
            ->build()
        ;

        $this->assertInstanceOf(Regex::class, $actual);
        $this->assertSame('~^foo$~i', (string) $actual);
    }

    public function testBuildCallsBuildstring(): void
    {
        $regexStr = '~^foo$~i';

        $builderMock = $this
            ->getMockBuilder(Builder::class)
            ->onlyMethods(['buildString'])
            ->getMock()
        ;

        $builderMock
            ->expects($this->once())
            ->method('buildString')
            ->willReturn($regexStr)
        ;

        /** @var Builder $builderMock */

        $actual = $builderMock
            // ...
            ->build()
        ;

        $this->assertInstanceOf(Regex::class, $actual);
        $this->assertSame($regexStr, (string) $actual);
    }
}
