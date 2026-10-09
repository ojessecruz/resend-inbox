<?php

declare(strict_types=1);

use Jessecruz\ResendInbox\Mailbox;

beforeEach(function () {
    $this->mailbox = new Mailbox('elenya.app', ['Contato@Elenya.app', 'suporte@elenya.app'], 'Jessé do Elenya');
});

it('keeps mail where any recipient is on our domain', function (array $recipients) {
    expect($this->mailbox->hasOwnRecipient($recipients))->toBeTrue();
})->with([
    'to' => [['contato@elenya.app']],
    'envelope only' => [['team@other-product.io', 'contato@elenya.app']],
    'cc with a display name' => [['team@other-product.io', 'Suporte <Suporte@Elenya.App>']],
]);

it('drops mail addressed only to other domains on the account', function () {
    expect($this->mailbox->hasOwnRecipient(['Support <support@other-product.io>', 'ops@other-product.io']))->toBeFalse()
        ->and($this->mailbox->hasOwnRecipient([]))->toBeFalse();
});

it('files mail under the first recipient of ours, even an unknown address', function () {
    expect($this->mailbox->mailboxFor(['team@other-product.io', 'Financeiro <Financeiro@elenya.app>']))->toBe('financeiro@elenya.app')
        ->and($this->mailbox->mailboxFor(['jesse@elenya.app']))->toBe('jesse@elenya.app')
        ->and($this->mailbox->mailboxFor(['team@other-product.io']))->toBe('team@other-product.io')
        ->and($this->mailbox->mailboxFor([]))->toBe('contato@elenya.app');
});

it('allows the configured senders plus our addresses that received mail in the conversation', function () {
    expect($this->mailbox->senderAddresses)->toBe(['contato@elenya.app', 'suporte@elenya.app'])
        ->and($this->mailbox->allowedSenders())->toBe(['contato@elenya.app', 'suporte@elenya.app'])
        ->and($this->mailbox->allowedSenders(['Jesse@Elenya.app', 'contato@elenya.app', 'maria@acme.com']))
        ->toBe(['contato@elenya.app', 'suporte@elenya.app', 'jesse@elenya.app']);
});

it('formats the from header with the sender name', function () {
    expect($this->mailbox->formatSender('contato@elenya.app'))->toBe('"Jessé do Elenya" <contato@elenya.app>')
        ->and((new Mailbox('elenya.app', [], ''))->formatSender('contato@elenya.app'))->toBe('contato@elenya.app')
        ->and((new Mailbox('elenya.app', [], 'The "Best" Team'))->formatSender('a@elenya.app'))->toBe('"The Best Team" <a@elenya.app>');
});
