<?php

declare(strict_types=1);

use Jessecruz\ResendInbox\Composing\Conversation;
use Jessecruz\ResendInbox\Composing\ReplyBuilder;
use Jessecruz\ResendInbox\Exceptions\DisallowedSender;
use Jessecruz\ResendInbox\Mailbox;

beforeEach(function () {
    $this->builder = new ReplyBuilder(new Mailbox('elenya.app', ['contato@elenya.app', 'suporte@elenya.app'], 'Jessé do Elenya'));
});

it('builds a new email with our own message id and no reply headers', function () {
    $composed = $this->builder->build('Contato@Elenya.app', ['Maria <Maria@Acme.com>'], 'Olá', "**Oi**, Maria\n\nJessé");

    expect($composed->fromAddress)->toBe('contato@elenya.app')
        ->and($composed->messageId)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}@elenya\.app$/')
        ->and($composed->inReplyTo)->toBeNull()
        ->and($composed->references)->toBe([])
        ->and($composed->email->from)->toBe('"Jessé do Elenya" <contato@elenya.app>')
        ->and($composed->email->to)->toBe(['maria@acme.com'])
        ->and($composed->email->headers)->toBe(['Message-ID' => "<{$composed->messageId}>"])
        ->and($composed->email->html)->toContain('<strong>Oi</strong>')
        ->and($composed->email->text)->toBe("**Oi**, Maria\n\nJessé");
});

it('threads a reply onto the conversation', function () {
    $composed = $this->builder->build('contato@elenya.app', ['maria@acme.com'], 'Re: Olá', 'Resposta', new Conversation(
        messageIds: ['abc@mail.acme.com', 'ours-1@elenya.app'],
        latestMessageId: 'ours-1@elenya.app',
    ));

    expect($composed->inReplyTo)->toBe('ours-1@elenya.app')
        ->and($composed->references)->toBe(['abc@mail.acme.com', 'ours-1@elenya.app'])
        ->and($composed->email->headers['In-Reply-To'])->toBe('<ours-1@elenya.app>')
        ->and($composed->email->headers['References'])->toBe('<abc@mail.acme.com> <ours-1@elenya.app>');
});

it('lets a reply go out from our address that received the conversation', function () {
    $composed = $this->builder->build('financeiro@elenya.app', ['maria@acme.com'], 'Re: Nota', 'Segue', new Conversation(
        messageIds: ['abc@mail.acme.com'],
        latestMessageId: 'abc@mail.acme.com',
        mailboxes: ['financeiro@elenya.app'],
    ));

    expect($composed->email->from)->toBe('"Jessé do Elenya" <financeiro@elenya.app>');
});

it('refuses a sender that is not allowed', function (string $from) {
    $this->builder->build($from, ['maria@acme.com'], 'Olá', 'Oi');
})->with([
    'unconfigured address of ours' => 'financeiro@elenya.app',
    'other domain' => 'ceo@other.com',
])->throws(DisallowedSender::class);

it('escapes raw html and drops unsafe links from the markdown', function () {
    $html = $this->builder->build('contato@elenya.app', ['maria@acme.com'], 'Olá', "<script>alert(1)</script>\n\n[clique](javascript:alert(1))")->email->html;

    expect($html)->not->toContain('<script>')
        ->and($html)->toContain('&lt;script&gt;')
        ->and($html)->not->toContain('javascript:');
});

it('generates a different message id every time', function () {
    expect(ReplyBuilder::newMessageId('Elenya.app'))->not->toBe(ReplyBuilder::newMessageId('elenya.app'))
        ->and(ReplyBuilder::newMessageId('Elenya.app'))->toEndWith('@elenya.app');
});
