# Clock

A PSR-20 clock implementation, with time configuration and movement support for use in unit tests.

## Installation
```
composer require firehed/clock
```

## Usage

### Wall Clock

A wall clock will return the current system time any time `->now()` is called.
It advances normally and behavies identically to calling `time()` or `new DateTimeImmutable()` directly would.

**This is what you should use in actual application code.**

```php
use Firehed\Clock\Clock;

$clock = new Clock();
```

### Test Clock

A test clock will return a specified time, and can be moved.
This is intended for use in test cases, such as:

- Validating or adjusting date ranges in queries
- Ensuring that expiration behavior works as expected
- Verifying rate-limiting behavior

It is permissable to use a test clock in real, non-test application code, but should be done with extreme caution.

Basically, if you'd normally have to use `sleep()` to check something, you can instead move the test clock by a specificed amount or to a specified time and continue the test case _as if_ that time had passed.

```php
use Firehed\Clock\Clock;

$clock = new Clock($timeOrOffset);

// ...

$clock->moveTo($otherTimeOrOffset);
```

The behavior of `$timeOrOffset` and `$otherTimeOrOffset` is as follows:

Type | `__construct` Behavior | `moveTo()` behavior
--- | --- | ---
`DateTimeInterface` | The clock will be fixed to the specified time | The clock will move to the specified time
`DateInterval` | The clock will be fixed to the system time _when construct is called_, plus the offset | The clock will advance by the offset from its initial fixed time
`string` that starts with `P` | The string will be parsed as a `DateInterval` and behave as above | Same
`string` | The clock will be fixed to an equivalent of `strtotime(string)` | Same
`int` or `float` | The value will be interpreted as a Unix timestamp and fixed to that time | Same

> [!WARNING]
> `float` values can _and often do_ lose precision at timestamps near the current time.
> If your test needs sub-second behavior, prefer any of the more-specific formats.

> [!TIP]
> Unixtime strings avoid floating point precision issues.
> These are `@` followed by the timestamp; e.g. `'@1234567890.987654'`

#### Moving the clock backwards
Relative time always uses `DateTimeImmutable->add()` or the equivalent.

To move the clock backwards:
- Pass a `DateInterval` where `invert` is set to `1`
- Pass any absolute timestamp equivalent before the current value

### Time Zones

This library does not currently aim to handle any time zone specifics, and will default to the system configuration.
If your needs include specific behavior regarding time zones, be sure to provide values that include time zone information.
