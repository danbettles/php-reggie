<?php declare(strict_types=1);

namespace DanBettles\Reggie;

use function array_map;
use function array_unshift;
use function implode;
use function preg_quote;
use function str_contains;
use function str_replace;

use const false;
use const true;

/**
 * @phpstan-type OptionsArray array{
 *   delimiter: string,
 *   flags: string,
 * }
 *
 * @todo Create Flags class
 */
class Builder
{
    private const string FLAG_CASELESS = 'i';
    private const string FLAG_ANCHORED = 'A';
    private const string FLAG_ANCHORED_END = 'Z';  // Custom

    /**
     * @phpstan-var OptionsArray
     */
    private array $options;

    /**
     * @var string[]
     */
    private array $chunks;

    private static function wrapString(
        string $str,
        string $wrapStr,
    ): string {
        return $wrapStr . $str . $wrapStr;
    }

    // @todo Pass options
    public function __construct()
    {
        $this->options = [
            'delimiter' => '~',
            'flags' => '',
        ];

        $this->chunks = [];
    }

    /**
     * Builds, and returns, the regex string
     */
    public function buildString(): string
    {
        $chunks = $this->chunks;
        $flags = $this->options['flags'];

        if (str_contains($flags, self::FLAG_ANCHORED)) {
            array_unshift($chunks, '^');
            $flags = str_replace(self::FLAG_ANCHORED, '', $flags);
        }

        if (str_contains($flags, self::FLAG_ANCHORED_END)) {
            $chunks[] = '$';
            $flags = str_replace(self::FLAG_ANCHORED_END, '', $flags);
        }

        return (
            self::wrapString(
                implode($chunks),
                $this->options['delimiter'],
            )
            . $flags
        );
    }

    public function build(): Regex
    {
        return new Regex($this->buildString());
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

    // @todo Expose this?
    private function addFlag(string $flag): self
    {
        if (str_contains($this->getFlags(), $flag)) {
            return $this;
        }

        return $this->setFlags($this->getFlags() . $flag);
    }

    // @todo Expose this?
    private function removeFlag(string $flag): self
    {
        return $this->setFlags(str_replace($flag, '', $this->getFlags()));
    }

    public function caseSensitive(bool $apply = true): self
    {
        if ($apply) {
            // Make case-sensitive
            return $this->removeFlag(self::FLAG_CASELESS);
        }

        // Make case-*in*sensitive
        return $this->addFlag(self::FLAG_CASELESS);
    }

    public function caseInsensitive(bool $apply = true): self
    {
        return $this->caseSensitive(!$apply);
    }

    /**
     * Shortcut, causes the regex to be 'anchored' at the start (e.g.: "~^Start~")
     */
    public function anchorStart(bool $apply = true): self
    {
        return $apply
            ? $this->addFlag(self::FLAG_ANCHORED)
            : $this->removeFlag(self::FLAG_ANCHORED)
        ;
    }

    /**
     * Shortcut, causes the regex to be 'anchored' at the end (e.g.: "~end$~")
     */
    public function anchorEnd(bool $apply = true): self
    {
        return $apply
            ? $this->addFlag(self::FLAG_ANCHORED_END)
            : $this->removeFlag(self::FLAG_ANCHORED_END)
        ;
    }

    /**
     * Shortcut, causes the regex to be 'anchored' on both sides (e.g.: "~^Start end$~")
     */
    public function anchorBoth(bool $apply = true): self
    {
        return $this
            ->anchorStart($apply)
            ->anchorEnd($apply)
        ;
    }

    /**
     * Quotes special regex characters
     */
    public function quote(string $str): string
    {
        return preg_quote($str, $this->options['delimiter']);
    }

    /**
     * Because literal backslashes in regex strings are bewildering
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
     * Adds a chunk -- anything -- to the pattern being built
     */
    public function add(
        string $str,
        bool $quote = false,
    ): self {
        if ($quote) {
            $str = $this->quote($str);
        }

        $this->chunks[] = $str;

        return $this;
    }

    /**
     * Adds a subpattern (e.g. "(foo)") to the pattern being built
     */
    public function addSubpattern(
        string $pattern,
        bool $capturing = true,
    ): self {
        return $this->add($this->createSubpattern($pattern, $capturing));
    }

    /**
     * Shortcut, adds a 'whole word' (e.g. "\bfoo\b") to the pattern being built
     *
     * @todo Option to quote word
     */
    public function addWholeWord(
        string $word,
        bool $captureWord = false,
    ): self {
        if ($captureWord) {
            $word = $this->createSubpattern($word, capturing: true);
        }

        return $this->add(self::wrapString($word, '\b'));
    }

    /**
     * Adds a literal to the pattern being built.  (The string will be quoted.)
     */
    public function addLiteral(string $str): self
    {
        return $this->add($str, quote: true);
    }
}
