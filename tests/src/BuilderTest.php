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
    public static function providesRegularExpressionStrings(): array
    {
        return [
            [
                '~~',
                new Builder(),
            ],
            [
                '~~ism',
                (new Builder())
                    ->setFlags('ism')
                ,
            ],
        ];
    }

    #[DataProvider('providesRegularExpressionStrings')]
    public function testTostringReturnsARegularExpressionString(
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
                ['foo', false],
            ],
        ];
    }

    /** @param string[] $methodArgs */
    #[DataProvider('providesSubpatterns')]
    public function testAddsubpattern(
        string $expected,
        array $methodArgs,
    ): void {
        $builder = new Builder();
        $something = $builder->addSubpattern(...$methodArgs);

        $this->assertSame($expected, $builder->toString());
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

        $builderMock
            ->expects($this->exactly(2))
            ->method('quote')
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
                ['foo', true],
            ],
        ];
    }

    /** @param string[] $methodArgs */
    #[DataProvider('providesWholeWordPatterns')]
    public function testAddwholeword(
        string $expected,
        array $methodArgs,
    ): void {
        $builder = new Builder();
        $something = $builder->addWholeWord(...$methodArgs);

        $this->assertSame($expected, $builder->toString());
        $this->assertSame($builder->toString(), (string) $builder);
        $this->assertSame($builder, $something);
    }

    public function testBackslash(): void
    {
        $builder = new Builder();

        $this->assertSame('\\\\', $builder->backslash());
        $this->assertSame($builder->backslash(), $builder->slosh());
    }
}
