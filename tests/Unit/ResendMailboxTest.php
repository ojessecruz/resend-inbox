<?php

declare(strict_types=1);

use Jessecruz\ResendInbox\Exceptions\MailboxException;
use Jessecruz\ResendInbox\OutgoingEmail;
use Jessecruz\ResendInbox\ResendMailbox;
use Resend\Client;
use Resend\Contracts\Transporter;
use Resend\Exceptions\ErrorException;
use Resend\ValueObjects\Transporter\Payload;

beforeEach(function () {
    $this->transporter = Mockery::mock(Transporter::class);
    $this->mailbox = new ResendMailbox(new Client($this->transporter));
});

afterEach(fn () => Mockery::close());

it('fetches a received email', function () {
    $this->transporter->shouldReceive('request')->once()->andReturn([
        'object' => 'email',
        'id' => 'rcv_1',
        'from' => 'Maria <maria@acme.com>',
        'to' => ['contato@elenya.app'],
        'subject' => 'Olá',
        'created_at' => '2026-10-09T12:00:00Z',
    ]);

    $email = $this->mailbox->receivedEmail('rcv_1');

    expect($email->resendId)->toBe('rcv_1')
        ->and($email->fromName)->toBe('Maria')
        ->and($email->subject)->toBe('Olá');
});

it('returns the download url of an attachment', function () {
    $this->transporter->shouldReceive('request')->once()->andReturn([
        'object' => 'attachment',
        'id' => 'att_1',
        'download_url' => 'https://resend.example/att_1',
    ]);

    expect($this->mailbox->receivedAttachmentUrl('rcv_1', 'att_1'))->toBe('https://resend.example/att_1');
});

it('fails when an attachment has no download url', function () {
    $this->transporter->shouldReceive('request')->once()->andReturn(['object' => 'attachment', 'id' => 'att_1']);

    $this->mailbox->receivedAttachmentUrl('rcv_1', 'att_1');
})->throws(MailboxException::class, 'no download URL');

it('sends an email without empty fields and returns its id', function () {
    $this->transporter->shouldReceive('request')
        ->once()
        ->withArgs(function (Payload $payload): bool {
            $parameters = (fn (): array => $this->parameters)->call($payload);

            return $parameters['from'] === '"Jessé do Elenya" <contato@elenya.app>'
                && $parameters['headers'] === ['Message-ID' => '<x@elenya.app>']
                && ! array_key_exists('reply_to', $parameters);
        })
        ->andReturn(['id' => 'snd_1']);

    expect($this->mailbox->send(new OutgoingEmail(
        from: '"Jessé do Elenya" <contato@elenya.app>',
        to: ['maria@acme.com'],
        subject: 'Olá',
        html: '<p>Oi</p>',
        text: 'Oi',
        headers: ['Message-ID' => '<x@elenya.app>'],
    )))->toBe('snd_1');
});

it('wraps resend api errors', function () {
    $this->transporter->shouldReceive('request')->andThrow(new ErrorException(['message' => 'Not found', 'name' => 'not_found']));

    $this->mailbox->receivedEmail('missing');
})->throws(MailboxException::class, 'Failed to fetch received email [missing]');
