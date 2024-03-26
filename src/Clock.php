<?php

declare(strict_types=1);

namespace Firehed\Clock;

use BadMethodCallException;
use DateTimeImmutable;
use DateTimeInterface;
use DateInterval;
use Psr\Clock\ClockInterface;

use function is_float;
use function is_int;
use function is_string;

/**
 * PSR-20 Clock implementation, with optional support for overriding the wall
 * time. The override behavior is intended only for test cases and local
 * development (i.e. manual testing), and attempting to use it on a wall clock
 * will throw a BadMethodCallException.
 */
class Clock implements ClockInterface
{
    private ?DateTimeImmutable $now;

    /**
     * Initialize the clock, with behavior as following:
     *
     * - null results in a "wall clock"; i.e. time advances normally in
     * accordance with the system clock.
     *
     * - DateTimeInterface (either implementation) uses that exact time for all
     * calls to now(), unless later modified
     *
     * - DateInterval will start a fixed clock (like DateTimeInterface), offset
     * from the system time _at time of initialization_ by the interval
     *
     * - Strings have two behaviors:
     *   - If starting with `P`, will be parsed as a DateInterval and follow
     *   the logic described above
     *   - Any other value is equivalent to setting the fixed clock to `strtotime`
     *
     * - Ints or floats will be intrepreted as a Unix timestamp and use that
     * for a fixed clock.
     *
     * CAUTION: while floats can be used to express fractions of a second, they
     * _can_ and _often will_ lose precision
     */
    public function __construct(DateTimeInterface|string|int|float|DateInterval|null $time = null)
    {
        if ($time === null) {
            $this->now = null;
        } else {
            $parsed = self::parse($time);
            if ($parsed instanceof DateTimeImmutable) {
                $this->now = $parsed;
            } else {
                $this->now = (new DateTimeImmutable())->add($parsed);
            }
        }
    }

    /**
     * When the clock instance is configured with a specific time, the simulated
     * time can be moved. The input to this function is equivalent to the rules
     * declared in the constructor, with the exception that
     * DateInterval-equivalent values are offset to the _configured_ clock
     * rather than the current system clock.
     *
     * This will throw a BadMethodCallException if the clock instance is an
     * actual wall clock.
     */
    public function moveTo(DateInterval|string|float|int|DateTimeInterface $time): void
    {
        if ($this->now === null) {
            throw new BadMethodCallException('moveTo cannot be used with real time');
        }

        $change = self::parse($time);
        if ($change instanceof DateTimeImmutable) {
            $this->now = $change;
        } else {
            $this->now = $this->now->add($change);
        }
    }

    /**
     * Performs the parsing described in the constructor, returning either
     * a DateInterval if the input was relative or a DateTimeImmutable if the
     * input was absolute.
     */
    private static function parse(DateInterval|string|float|int|DateTimeInterface $time): DateTimeImmutable|DateInterval
    {
        if ($time instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($time);
        } elseif ($time instanceof DateInterval) {
            return $time;
        } elseif (is_string($time)) {
            // P1M, P2DT5M30S, etc.
            if (str_starts_with($time, 'P')) {
                return new DateInterval($time);
            } else {
                return new DateTimeImmutable($time);
            }
        } else {
            assert(is_int($time) || is_float($time)); // @phpstan-ignore-line Be extra safe
            // Treat as unixtime
            return new DateTimeImmutable('@' . $time);
        }
    }

    public function now(): DateTimeImmutable
    {
        return $this->now ?? new DateTimeImmutable();
    }
}
