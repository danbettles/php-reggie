<?php declare(strict_types=1);

namespace DanBettles\Reggie;

use Stringable;

use function boolval;
use function count;
use function preg_match;
use function preg_match_all;

use const false;
use const null;

class Regex implements Stringable
{
    public function __construct(
        private string $pattern,
    ) {
    }

    public function __toString(): string
    {
        return $this->pattern;
    }

    /**
     * A proxy, mainly for the purposes of testing
     *
     * @internal
     * @final
     * @param string[] &$matches
     */
    protected function pregMatch(
        string $pattern,
        string $subject,
        array &$matches = [],
    ): int|false {
        return preg_match($pattern, $subject, $matches);
    }

    /**
     * A proxy, mainly for the purposes of testing
     *
     * @internal
     * @final
     * @param array<string[]> &$matches
     */
    protected function pregMatchAll(
        string $pattern,
        string $subject,
        array &$matches = [],
    ): int|false {
        return preg_match_all($pattern, $subject, $matches);
    }

    /**
     * If the "global" option is enabled, the method will perform a *global* search for matches in the string.  In this
     * case, an array of `MatchData` objects will be returned -- one `MatchData` object for each match, in the order
     * they occur in the string.
     *
     * @return MatchData|MatchData[]|null
     */
    public function match(
        string $subject,
        bool $global = false,
    ): MatchData|array|null {
        if ($global) {
            $pregMatches = [];
            $numMatches = $this->pregMatchAll($this->pattern, $subject, $pregMatches);

            if (!boolval($numMatches)) {
                return null;
            }

            $numParts = count($pregMatches);
            $matchDataObjects = [];

            for ($matchNo = 0; $matchNo < $numMatches; $matchNo++) {
                $matchData = new MatchData([]);
                $matchDataObjects[$matchNo] = $matchData;

                for ($partNo = 0; $partNo < $numParts; $partNo++) {
                    $matchData[$partNo] = $pregMatches[$partNo][$matchNo];
                }
            }

            return $matchDataObjects;
        }

        $pregMatches = [];
        $matched = 1 === $this->pregMatch($this->pattern, $subject, $pregMatches);

        return $matched
            ? new MatchData($pregMatches)
            : null
        ;
    }

    /**
     * Returns `true` if the regex matches the string, or `false` otherwise
     */
    public function test(string $subject): bool
    {
        return null !== $this->match($subject);
    }
}
