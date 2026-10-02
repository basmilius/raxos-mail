<?php
declare(strict_types=1);

use Mailgun\Api\Message;
use Mailgun\Mailgun as MailgunClient;
use Raxos\Mail\Email;
use Raxos\Mail\Mail;
use Raxos\Mail\Mailgun;
use Raxos\Mail\Recipient;
use Raxos\Mail\RecipientType;
use Raxos\Mail\Sender;
use Raxos\Mail\Util\PublicSuffixList;

it('sends Email value objects through the Mailgun string interface', function (): void {
    $message = $this->createMock(Message::class);
    $client = $this->createMock(MailgunClient::class);
    $client->method('messages')->willReturn($message);
    $message->expects($this->once())->method('send')->with('example.org', $this->callback(static function (array $payload): bool {
        return str_contains(implode(',', $payload['from']), 'sender@example.org')
            && str_contains(implode(',', $payload['to']), 'to@example.org')
            && str_contains(implode(',', $payload['cc']), 'cc@example.org')
            && str_contains(implode(',', $payload['bcc']), 'bcc@example.org');
    }));
    $mail = new Mail('Subject', '<b>Body</b>', 'Body', new Sender(new Email('sender', 'example.org'), 'Sender'), [
        new Recipient(new Email('to', 'example.org'), 'To'),
        new Recipient(new Email('cc', 'example.org'), 'Cc', RecipientType::CC),
        new Recipient(new Email('bcc', 'example.org'), 'Bcc', RecipientType::BCC),
    ]);
    expect(new Mailgun('test-key', 'example.org', client: $client)->send($mail))->toBeTrue();
});

it('replaces the public suffix index on forced reload', function (): void {
    PublicSuffixList::load(true);
    $before = PublicSuffixList::findSuggestionsForInvalidDomain('example.cmo', 5);
    PublicSuffixList::load(true);
    expect(PublicSuffixList::findSuggestionsForInvalidDomain('example.cmo', 5))->toBe($before);
    expect(count(array_unique($before)))->toBe(5);
    expect(PublicSuffixList::parseDomain('example.co.uk'))->toBe(['example', 'co.uk']);
});

it('round trips email addresses with tags', function (): void {
    $email = Email::fromString('bas+review@example.org');
    expect((string)$email)->toBe('bas+review@example.org');
});

it('rejects malformed email addresses', function (string $address): void {
    expect(fn(): Email => Email::fromString($address))->toThrow(Raxos\Mail\Error\InvalidEmailAddressException::class);
})->with(['', 'user', 'user@', '@example.org', 'a@@example.org', "user\n@example.org"]);

it('retains email tags and formatted sender and recipient names', function (string $address): void {
    $email = Email::fromString($address);
    expect((string)$email)->toBe($address)->and(json_encode($email))->toBe(json_encode($address))
        ->and((string)new Sender($email, 'Bas'))->toBe('Bas <' . $address . '>')
        ->and((string)new Recipient($email, 'Bas'))->toBe('Bas <' . $address . '>');
})->with(['bas@example.org', 'bas+tag@example.org', 'bas+tag+extra@example.org']);

it('identifies supported multi-label domains and rejects unknown suffixes', function (string $domain, bool $valid): void {
    expect(PublicSuffixList::validateDomain($domain))->toBe($valid);
})->with([['example.org', true], ['example.co.uk', true], ['sub.example.com', true], ['example.invalidsuffix', false]]);
