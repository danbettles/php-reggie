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
 *   anchorLeft: bool,
 *   anchorRight: bool,
 * }
 *
 * @todo Create `build(): Regex`
 */
class Builder
{
    private const string FLAG_CASELESS = 'i';

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

    public function __construct()
    {
        $this->options = [
            'delimiter' => '~',
            'flags' => '',
            'anchorLeft' => false,
            'anchorRight' => false,
        ];

        $this->chunks = [];
    }

    /**
     * Builds, and returns, the regex string
     */
    public function buildString(): string
    {
        $chunks = $this->chunks;

        if ($this->options['anchorLeft']) {
            array_unshift($chunks, '^');
        }

        if ($this->options['anchorRight']) {
            $chunks[] = '$';
        }

        return (
            self::wrapString(
                implode($chunks),
                $this->options['delimiter'],
            )
            . $this->options['flags']
        );
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

    public function caseSensitive(bool $flag = true): self
    {
        if ($flag) {
            // Make case-sensitive:
            return $this->setFlags(str_replace(self::FLAG_CASELESS, '', $this->getFlags()));
        }

        // Make case-*in*sensitive:

        return str_contains($this->getFlags(), self::FLAG_CASELESS)
            ? $this  // (Already case-insensitive)
            : $this->setFlags($this->getFlags() . self::FLAG_CASELESS)
        ;
    }

    public function caseInsensitive(bool $flag = true): self
    {
        return $this->caseSensitive(!$flag);
    }

    /**
     * Shortcut, causes the regex to be 'anchored' on the left side (e.g.: "~^Left~")
     */
    public function anchorLeft(bool $flag = true): self
    {
        $this->options['anchorLeft'] = $flag;

        return $this;
    }

    /**
     * Shortcut, causes the regex to be 'anchored' on the right side (e.g.: "~Right$~")
     */
    public function anchorRight(bool $flag = true): self
    {
        $this->options['anchorRight'] = $flag;

        return $this;
    }

    /**
     * Shortcut, causes the regex to be 'anchored' on both sides (e.g.: "~^Both$~")
     */
    public function anchorBoth(bool $flag = true): self
    {
        return $this
            ->anchorLeft($flag)
            ->anchorRight($flag)
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
     * @todo Option to quote word?
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
