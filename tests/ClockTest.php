<?php

declare(strict_types=1);

namespace Firehed\Clock;

use DateTime;
use DateInterval;
use DateTimeImmutable;
use LogicException;
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
    public function testConstructWithAbsoluteInput(mixed $input, DateTimeImmutable $expected): void
    {
        $clock = new Clock($input);
        self::assertEquals($expected, $clock->now());
        self::assertNotSame($expected, $clock->now(), 'Should not return same instance from construct');
    }

    /**
     * @dataProvider relativeInputs
     */
    public function testConstructWithRelativeInput(mixed $input, DateInterval $expectedOffset): void
    {
        $now = new DateTimeImmutable();
        $clock = new Clock($input);
        $expected = $now->add($expectedOffset);

        self::assertEqualsWithDelta($expected, $clock->now(), 0.001);
    }

    /**
     * @dataProvider absoluteInputs
     */
    public function testMovingTestClockToAbsoluteTime(mixed $input, DateTimeImmutable $expected): void
    {
        $clock = new Clock('now');
        $beforeMoving = $clock->now();
        $clock->moveTo($input);
        self::assertEquals($expected, $clock->now());
        self::assertNotEquals($beforeMoving, $clock->now());
    }

    /**
     * @dataProvider relativeInputs
     */
    public function testMovingTestClockWithRelativeInput(mixed $input, DateInterval $expectedOffset): void
    {
        $now = new DateTimeImmutable();
        $clock = new Clock($now);
        $beforeMoving = $clock->now();
        $expected = $now->add($expectedOffset);
        $clock->moveTo($input);

        self::assertEquals($expected, $clock->now());
    }

    public function testMovingWallClockIsLogicException(): void
    {
        $clock = new Clock();
        self::expectException(LogicException::class);
        $clock->moveTo('PT1S');
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
            'DateTime literal' => [new DateTime('2019-04-12T13:05:27.5216Z'), new DateTimeImmutable('2019-04-12T13:05:27.5216Z')],
            'DateTimeImmutable literal' => [new DateTimeImmutable('2019-04-12T13:05:27.5216Z'), new DateTimeImmutable('2019-04-12T13:05:27.5216Z')],

        ];
    }

    public static function relativeInputs(): array
    {
        return [
            'DI literal' => [new DateInterval('P1D'), new DateInterval('P1D')],
            'DI date string' => ['P1D', new DateInterval('P1D')],
            'DI time string' => ['PT5S', new DateInterval('PT5S')],
            'DI date and time string' => ['P5MT5M', new DateInterval('P5MT5M')],
        ];
    }
}
