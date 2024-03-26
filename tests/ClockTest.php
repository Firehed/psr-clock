<?php

declare(strict_types=1);

namespace Firehed\Clock;

use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Firehed\Clock\Clock
 */
class ClockTest extends TestCase
{
    public function testNullTime(): void
    {
        $now = new DateTimeImmutable();

        $clock = new Clock();
        $now1 = $clock->now();
        usleep(1);
        $now2 = $clock->now();
        self::assertNotSame($now1, $now2);
        self::assertGreaterThan($now1, $now2);
        self::assertEqualsWithDelta($now, $now1, 0.001);
        self::assertEqualsWithDelta($now, $now2, 0.001);
    }

    /**
     * When passing literal `now`, it's the same behavior as strtotime _at the
     * time the Clock is constructed_. In effect, it's frozen at that time.
     */
    public function testStringLiteralNow(): void
    {
        $now = new DateTimeImmutable();

        $clock = new Clock('now');
        $now1 = $clock->now();
        $now2 = $clock->now();
        self::assertSame($now1, $now2);
        self::assertNotSame($now, $now1);
        // There's still some tiny time passage
        self::assertEqualsWithDelta($now, $now1, 0.001);
    }

    public function testStringTime(): void
    {
        $str = '2019-04-23';
        $clock = new Clock($str);
        $now = $clock->now();
        self::assertSame($str, $now->format('Y-m-d'));
    }

    public function testDateTime(): void
    {
        $dt = new DateTime('2028-12-02T09:43:11Z');
        $clock = new Clock($dt);
        $now = $clock->now();
        self::assertNotSame($dt, $now); // @phpstan-ignore-line
        self::assertSame($dt->getTimestamp(), $now->getTimestamp());
    }

    public function testDateTimeImmutable(): void
    {
        $dt = new DateTimeImmutable('2028-12-02T09:43:11Z');
        $clock = new Clock($dt);
        $now = $clock->now();
        self::assertNotSame($dt, $now, 'DTImm should still copy');
        self::assertSame($dt->getTimestamp(), $now->getTimestamp());
    }

    /**
     * @dataProvider absoluteInputs
     */
    public function testConstructWithAbsoluteInput($input, DateTimeImmutable $expected): void
    {
        $clock = new Clock($input);
        self::assertEquals($expected, $clock->now());
    }

    // Provide number as input: should treat as unixtime
    // Provide DateInterval as input: should offset relative to current time
    // public function testDateInterval
    //
    public static function absoluteInputs(): array
    {
        return [
            'Date only' => ['2020-03-14', new DateTimeImmutable('2020-03-14')],
            'Int timestamp' => [1711475822, new DateTimeImmutable('@1711475822')],
            'Int timestamp explicit' => [1711475822, new DateTimeImmutable('2024-03-26T17:57:02.000000+0000')],
            // 'Float timestamp' => [1711475822.123456, new DateTimeImmutable('@1711475822.123456')],
            'Float timestamp as @string' => ['@1711475822.123456', new DateTimeImmutable('@1711475822.123456')],

        ];
    }
}
