<?php declare(strict_types=1);

namespace DanBettles\Reggie\Tests;

use DanBettles\Reggie\MatchData;
use DanBettles\Reggie\Regex;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stringable;

use const null;
use const true;

class RegexTest extends TestCase
{
    public function testIsStringable(): void
    {
        $this->assertTrue(
            (new ReflectionClass(Regex::class))->implementsInterface(Stringable::class),
        );
    }

    /** @return array<mixed[]> */
    public static function providesRegexStrings(): array
    {
        return [
            ['~~'],
            ['~^Foo$~'],
        ];
    }

    #[DataProvider('providesRegexStrings')]
    public function testMagicTostringConvertsTheObjectToAString(string $regexStr): void
    {
        $regex = new Regex($regexStr);

        $this->assertSame($regexStr, (string) $regex);
    }

    public function testTest(): void
    {
        $regex = new Regex('~foo~');

        $this->assertTrue($regex->test('foo'));
        $this->assertFalse($regex->test('bar'));
    }

    public function testTestCallsMatch(): void
    {
        $regexStr = '~foo~';
        $subject = 'bar';

        $regexMock = $this
            ->getMockBuilder(Regex::class)
            ->onlyMethods(['match'])
            ->setConstructorArgs([$regexStr])
            ->getMock()
        ;

        $regexMock
            ->expects($this->once())
            ->method('match')
            ->with($subject)
            ->willReturn(null)  // (No match)
        ;

        /** @var Regex $regexMock */

        $actual = $regexMock->test($subject);

        $this->assertFalse($actual);
    }

    public function testMatch(): void
    {
        $regex = new Regex('~://(.*?)(/|$)~');

        $actual0 = $regex->match('https://example.com');

        $this->assertEquals(
            new MatchData([
                '://example.com',
                'example.com',
                '',
            ]),
            $actual0,
        );

        $actual1 = $regex->match('foo');

        $this->assertNull($actual1);
    }

    public function testMatchCallsProxyMethod(): void
    {
        $regexStr = '~://(.*?)(/|$)~';
        $subject = 'https://example.com';

        $pregmatchMatches = [
            '://example.com',
            'example.com',
            '',
        ];

        $regexMock = $this
            ->getMockBuilder(Regex::class)
            ->onlyMethods(['pregMatch'])
            ->setConstructorArgs([$regexStr])
            ->getMock()
        ;

        $regexMock
            ->expects($this->once())
            ->method('pregMatch')
            ->with($regexStr, $subject, [])
            ->willReturnCallback(function ($pattern, $subject, array &$matches) use ($pregmatchMatches): int {
                $matches = $pregmatchMatches;

                return 1;
            });
        ;

        /** @var Regex $regexMock */

        $actual = $regexMock->match($subject);

        $this->assertEquals(new MatchData($pregmatchMatches), $actual);
    }

    public function testMatchCanPerformAGlobalSearch(): void
    {
        $regex = new Regex('~[?&]([a-z]+)=([a-z]+)~');

        $actual = $regex->match(
            'https://example.com?foo=bar&baz=qux',
            global: true,
        );

        $this->assertEquals(
            [
                new MatchData(['?foo=bar', 'foo', 'bar']),
                new MatchData(['&baz=qux', 'baz', 'qux']),
            ],
            $actual,
        );
    }

    public function testMatchCallsProxyMethodWhenPerformingAGlobalSearch(): void
    {
        $regexStr = '~[?&]([a-z]+)=([a-z]+)~';
        $subject = 'https://example.com?foo=bar&baz=qux';

        $pregmatchallMatches = [
            0 => [
                '?foo=bar',
                '&baz=qux',
            ],
            1 => [
                'foo',
                'baz',
            ],
            2 => [
                'bar',
                'qux',
            ],
        ];

        $regexMock = $this
            ->getMockBuilder(Regex::class)
            ->onlyMethods(['pregMatchAll'])
            ->setConstructorArgs([$regexStr])
            ->getMock()
        ;

        $regexMock
            ->expects($this->once())
            ->method('pregMatchAll')
            ->with($regexStr, $subject, [])
            ->willReturnCallback(function ($pattern, $subject, array &$matches) use ($pregmatchallMatches): int {
                $matches = $pregmatchallMatches;

                return 2;
            });
        ;

        /** @var Regex $regexMock */

        $actual = $regexMock->match($subject, global: true);

        $this->assertEquals(
            [
                new MatchData(['?foo=bar', 'foo', 'bar']),
                new MatchData(['&baz=qux', 'baz', 'qux']),
            ],
            $actual,
        );
    }
}
