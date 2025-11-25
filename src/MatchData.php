<?php declare(strict_types=1);

namespace DanBettles\Reggie;

use ArrayAccess;
use InvalidArgumentException;
use OutOfRangeException;

use function array_key_exists;
use function is_int;
use function is_string;

/**
 * @phpstan-type MatchesArrayKeyType int|string
 * @phpstan-type MatchesArrayValueType string
 * @phpstan-type MatchesArray array<MatchesArrayKeyType,MatchesArrayValueType>
 * @implements ArrayAccess<int|string,string>
 */
class MatchData implements ArrayAccess
{
    /**
     * @phpstan-var MatchesArray
     */
    private array $matches;

    /**
     * @phpstan-param MatchesArray $matches
     */
    public function __construct(array $matches = [])
    {
        // @todo Validate?
        $this->matches = $matches;
    }

    /**
     * @phpstan-return MatchesArray
     */
    public function toArray(): array
    {
        return $this->matches;
    }

    /**
     * @phpstan-param MatchesArrayKeyType $key
     * @phpstan-return MatchesArrayValueType
     */
    public function get(mixed $key): mixed
    {
        return $this->offsetGet($key);
    }

    /**
     * @throws OutOfRangeException If the key is invalid
     */
    private function assertMatchKeyValid(mixed $key): void
    {
        if (is_int($key) || is_string($key)) {
            return;
        }

        throw new OutOfRangeException('The key is invalid');
    }

    /**
     * @throws InvalidArgumentException If the value is invalid
     */
    private function assertMatchValueValid(mixed $value): void
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('The value is invalid');
        }
    }

    /**
     * @phpstan-param MatchesArrayKeyType $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        $this->assertMatchKeyValid($offset);

        return array_key_exists($offset, $this->matches);
    }

    /**
     * @phpstan-param MatchesArrayKeyType $offset
     * @phpstan-return MatchesArrayValueType
     */
    public function offsetGet(mixed $offset): mixed
    {
        $this->assertMatchKeyValid($offset);

        return $this->matches[$offset];
    }

    /**
     * @phpstan-param MatchesArrayKeyType $offset
     * @phpstan-param MatchesArrayValueType $value
     * @throws InvalidArgumentException If the value is invalid
     */
    public function offsetSet(
        mixed $offset,
        mixed $value,
    ): void {
        $this->assertMatchKeyValid($offset);
        $this->assertMatchValueValid($value);

        $this->matches[$offset] = $value;
    }

    /**
     * @phpstan-param MatchesArrayKeyType $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->assertMatchKeyValid($offset);

        unset($this->matches[$offset]);
    }
}
