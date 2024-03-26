# Clock

A [PSR-20](https://www.php-fig.org/psr/psr-20/) Clock implementation, with time configuration and movement support for use in unit tests.

## Installation
```
composer require firehed/clock
```

## Usage

Generally speaking, `ClockInterface` is only useful when paired with Dependency Injection.
This allows unit tests to provide a test clock set to a specific point in time, where the actual application is wired to use a wall clock and follows real time.

### Wall Clock

A wall clock will return the current system time any time `->now()` is called.
It advances normally and behaves identically to calling `time()` or `new DateTimeImmutable()` directly would.

**This is what you should use in actual application code.**

```php
use Firehed\Clock\Clock;

$clock = new Clock();
```

> [!IMPORTANT]
> A clock in "wall clock" mode cannot be moved, and will throw an exception if you attempt to do so.

### Test Clock

A test clock will return a specified time, and can be manually moved.
It will _not_ advance automatically as actual wall time progresses (e.g. is unaffected by `sleep()`, etc).
This is intended for use in test cases, such as:

- Validating or adjusting date ranges in queries
- Ensuring that expiration behavior works as expected
- Verifying rate-limiting behavior

Basically, if you'd normally have to use `sleep()` to check something, you can instead move the test clock by a specificed amount or to a specified time and continue the test case _as if_ that time had passed.
This can result in tests that run faster and more reliably, without having to fuss with "give or take a second" logic.

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
Anything else | A `TypeError` will be thrown | Same

> [!WARNING]
> `float` values can _and often do_ lose precision at timestamps near the current time.
> If your test needs sub-second behavior, prefer any of the more-specific formats.
>
> Unixtime strings avoid floating point precision issues.
> These are `@` followed by the timestamp; e.g. `'@1234567890.987654'`

The library does **not** make guarantees about subsequent calls to ->now() on a test clock being the same or different `DateTimeImmutable` instances.
However, they are guaranteed to be in reference to the same point in time.

> [!TIP]
> If you only care about relative movement, a test clock can be set up as `new Clock('now')`.
> You may also use only small values near the Unix epoch (e.g. `0`, `20`); if your application uses `ClockInterface` consistently it should still work, and run as if the current time was in 1970.

#### Moving the clock backwards
Relative time changes always use `DateTimeImmutable->add()` or the equivalent internally.

To move the clock backwards:
- Pass a `DateInterval` where `invert` is set to `1`
- Pass any absolute timestamp equivalent before the currently-set value

### Time Zones

This library does not currently aim to handle any time zone specifics, and will default to the system configuration.
If your needs include specific behavior regarding time zones, be sure to provide values that include time zone information.

## Contributing

Please report any bugs or feature requests on GitHub.
Be aware that this is considered _mostly_ feature-complete, so feature requests may be declined.
