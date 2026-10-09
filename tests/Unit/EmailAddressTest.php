<?php

declare(strict_types=1);

use Jessecruz\ResendInbox\EmailAddress;
use Jessecruz\ResendInbox\Subject;

it('splits a display name from a lowercased address', function () {
    expect(EmailAddress::parse('Maria Silva <Maria@Acme.com>'))->toBe(['address' => 'maria@acme.com', 'name' => 'Maria Silva'])
        ->and(EmailAddress::parse('maria@acme.com'))->toBe(['address' => 'maria@acme.com', 'name' => null])
        ->and(EmailAddress::parse('"Silva, Maria" <maria@acme.com>'))->toBe(['address' => 'maria@acme.com', 'name' => 'Silva, Maria']);
});

it('falls back to the stripped value when the address cannot be parsed', function () {
    expect(EmailAddress::parse('<Not An Address>'))->toBe(['address' => 'not an address', 'name' => null]);
});

it('lists unique bare addresses from a comma separated input', function () {
    expect(EmailAddress::list('A@x.com, b@y.com; a@x.com'))->toBe(['a@x.com', 'b@y.com']);
});

it('extracts the domain of an address', function () {
    expect(EmailAddress::domain('Sales <Sales@OmniLine.App>'))->toBe('omniline.app')
        ->and(EmailAddress::domain('nobody'))->toBe('');
});

it('reads message ids with or without angle brackets', function () {
    expect(EmailAddress::messageId(' <abc@x.com> '))->toBe('abc@x.com')
        ->and(EmailAddress::messageId('<>'))->toBeNull()
        ->and(EmailAddress::messageIds('<a@x.com> <b@x.com>  <a@x.com>'))->toBe(['a@x.com', 'b@x.com'])
        ->and(EmailAddress::messageIds('a@x.com b@x.com'))->toBe(['a@x.com', 'b@x.com'])
        ->and(EmailAddress::messageIds(null))->toBe([]);
});

it('normalizes reply and forward prefixes out of a subject', function (string $subject) {
    expect(Subject::normalize($subject))->toBe('pricing for 50 engineers');
})->with([
    'plain' => 'Pricing for 50 engineers',
    'english reply' => 'RE: Pricing for 50 engineers',
    'stacked' => 'Re: Fwd: Pricing for 50 engineers',
    'portuguese reply' => 'Res: Pricing for 50 engineers',
    'portuguese forward' => 'ENC: Pricing for 50 engineers',
    'german reply' => 'AW: Pricing for 50 engineers',
    'numbered reply' => 'Re[2]: Pricing for 50 engineers',
]);
