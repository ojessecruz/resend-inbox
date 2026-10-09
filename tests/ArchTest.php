<?php

declare(strict_types=1);

/*
 * toOnlyUse only sees classes it can autoload, so it catches packages that
 * are installed (Guzzle, PSR) but not a framework that is absent from
 * composer.json: PHPStan (`composer analyse`) reports those as unknown classes.
 */
arch('the core depends on no framework, only on the Resend SDK and two standalone libraries')
    ->expect('Jessecruz\ResendInbox')
    ->toOnlyUse([
        'Jessecruz\ResendInbox',
        'Resend',
        'Symfony\Component\Mime',
        'League\CommonMark',
    ]);

arch('every file declares strict types')
    ->expect('Jessecruz\ResendInbox')
    ->toUseStrictTypes();

arch('classes are final')
    ->expect('Jessecruz\ResendInbox')
    ->classes()
    ->toBeFinal();

arch('no debugging leftovers')
    ->expect(['dd', 'dump', 'var_dump', 'print_r', 'ray'])
    ->not->toBeUsed();
