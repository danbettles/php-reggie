<?php declare(strict_types=1);

namespace DanBettles\Reggie\Tests;

use ArrayAccess;
use DanBettles\Reggie\MatchData;
use InvalidArgumentException;
use OutOfRangeException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

use const null;

class MatchDataTest extends TestCase
{
    public function testIsInstantiable(): void
    {
        $pregMatches = [
            'foo bar',
            'foo',
        ];

        $nonEmptyMatchData = new MatchData($pregMatches);

        $this->assertSame($pregMatches, $nonEmptyMatchData->toArray());

        $emptyMatchData = new MatchData();

        $this->assertSame([], $emptyMatchData->toArray());
    }

    public function testGet(): void
    {
        $matchData = new MatchData(['foo bar', 'foo']);

        $this->assertSame('foo bar', $matchData->get(0));
        $this->assertSame('foo', $matchData->get(1));
    }

    public function testImplementsArrayaccess(): void
    {
        $this->assertTrue((new ReflectionClass(MatchData::class))->implementsInterface(ArrayAccess::class));

        $matchData = new MatchData(['foo bar', 'foo']);

        $this->assertTrue($matchData->offsetExists(1));
        $this->assertFalse($matchData->offsetExists(2));

        $this->assertSame('foo bar', $matchData->offsetGet(0));
        $this->assertSame('foo', $matchData->offsetGet(1));

        $matchData->offsetSet(2, 'baz');

        $this->assertTrue($matchData->offsetExists(2));
        $this->assertSame('baz', $matchData->offsetGet(2));

        $matchData->offsetUnset(2);

        $this->assertFalse($matchData->offsetExists(2));
    }

    /** @return array<mixed[]> */
    public static function providesInvalidOffsets(): array
    {
        return [
            [
                1.23,
            ],
            [
                null,
            ],
            [
                [],
            ],
            [
                new stdClass(),
            ],
        ];
    }

    #[DataProvider('providesInvalidOffsets')]
    public function testOffsetexistsThrowsAnExceptionIfTheOffsetIsInvalid(mixed $invalidOffset): void
    {
        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('The key is invalid');

        // @phpstan-ignore argument.type
        (new MatchData([]))->offsetExists($invalidOffset);
    }

    #[DataProvider('providesInvalidOffsets')]
    public function testOffsetgetThrowsAnExceptionIfTheOffsetIsInvalid(mixed $invalidOffset): void
    {
        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('The key is invalid');

        // @phpstan-ignore argument.type
        (new MatchData([]))->offsetGet($invalidOffset);
    }

    public function testOffsetset(): void
    {
        $value = 'foo';

        $matchData = new MatchData([]);
        $matchData->offsetSet(1, $value);

        $this->assertTrue($matchData->offsetExists(1));
        $this->assertSame($value, $matchData->offsetGet(1));
    }

    #[DataProvider('providesInvalidOffsets')]
    public function testOffsetsetThrowsAnExceptionIfTheOffsetIsInvalid(mixed $invalidOffset): void
    {
        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('The key is invalid');

        // @phpstan-ignore argument.type
        (new MatchData([]))->offsetSet($invalidOffset, 'valid value');
    }

    /** @return array<mixed[]> */
    public static function providesInvalidValues(): array
    {
        return [
            [
                1.23,
            ],
            [
                null,
            ],
            [
                new stdClass(),
            ],
            [
                [],
            ],
            [
                ['foo'],
            ],
            [
                [1.23],
            ],
            [
                [null],
            ],
            [
                [new stdClass()],
            ],
            [
                [[]],
            ],
        ];
    }

    #[DataProvider('providesInvalidValues')]
    public function testOffsetsetThrowsAnExceptionIfTheValueIsInvalid(mixed $invalidValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value is invalid');

        $matchData = new MatchData([]);
        // @phpstan-ignore argument.type
        $matchData->offsetSet(1, $invalidValue);
    }

    #[DataProvider('providesInvalidOffsets')]
    public function testOffsetunsetThrowsAnExceptionIfTheOffsetIsInvalid(mixed $invalidOffset): void
    {
        $this->expectException(OutOfRangeException::class);
        $this->expectExceptionMessage('The key is invalid');

        // @phpstan-ignore argument.type
        (new MatchData([]))->offsetUnset($invalidOffset);
    }

    public function testGetCallsOffsetget(): void
    {
        $matchDataMock = $this
            ->getMockBuilder(MatchData::class)
            ->onlyMethods(['offsetGet'])
            ->setConstructorArgs([
                ['foo', 'bar'],
            ])
            ->getMock()
        ;

        $matchDataMock
            ->expects($this->once())
            ->method('offsetGet')
            ->with(1)
            ->willReturn('bar')
        ;

        /** @var MatchData $matchDataMock */

        $actual = $matchDataMock->get(1);

        $this->assertSame('bar', $actual);
    }
}
