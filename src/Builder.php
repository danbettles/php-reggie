<?php declare(strict_types=1);

namespace DanBettles\Reggie;

use function array_map;
use function implode;
use function preg_quote;

use const false;
use const true;

/**
 * @phpstan-type OptionsArray array{
 *   delimiter: string,
 *   flags: string,
 * }
 */
class Builder
{
    /**
     * @phpstan-var OptionsArray
     */
    private array $options;

    /**
     * @var string[]
     */
    private array $chunks;

    private static function wrapString(
        string $string,
        string $wrapString,
    ): string {
        return $wrapString . $string . $wrapString;
    }

    public function __construct()
    {
        $this->options = [
            'delimiter' => '~',
            'flags' => '',
        ];

        $this->chunks = [];
    }

    public function toString(): string
    {
        return self::wrapString(
            implode($this->chunks),
            $this->options['delimiter'],
        ) . $this->options['flags'];
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function setFlags(string $flags): self
    {
        $this->options['flags'] = $flags;

        return $this;
    }

    public function getFlags(): string
    {
        return $this->options['flags'];
    }

    /**
     * Quotes special regular-expression characters
     */
    public function quote(string $str): string
    {
        return preg_quote($str, $this->options['delimiter']);
    }

    /**
     * Quite simply because literal backslashes in regular-expression strings are hideous
     */
    public function backslash(): string
    {
        return $this->quote('\\');
    }

    /**
     * @see self::backslash()
     */
    public function slosh(): string
    {
        return $this->backslash();
    }

    /**
     * Creates, and returns, a list of alternatives (e.g. "foo|bar")
     *
     * @param string[] $patterns
     */
    public function listOfAlternatives(
        array $patterns,
        bool $quoteEach = false,
    ): string {
        if ($quoteEach) {
            $patterns = array_map($this->quote(...), $patterns);
        }

        return implode('|', $patterns);
    }

    private function createSubpattern(
        string $pattern,
        bool $capturing,
    ): string {
        $pattern = ($capturing ? '' : '?:') . $pattern;

        return "({$pattern})";
    }

    /**
     * Adds a subpattern (e.g. "(foo)") to the pattern being built
     */
    public function addSubpattern(
        string $pattern,
        bool $capturing = true,
    ): self {
        $this->chunks[] = $this->createSubpattern($pattern, $capturing);

        return $this;
    }

    /**
     * Shortcut, adds a 'whole word' (e.g. "\bfoo\b") to the pattern being built
     */
    public function addWholeWord(
        string $pattern,
        bool $captureWord = false,
    ): self {
        if ($captureWord) {
            $pattern = $this->createSubpattern($pattern, capturing: true);
        }

        $this->chunks[] = self::wrapString($pattern, '\b');

        return $this;
    }
}
