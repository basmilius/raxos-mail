<?php
declare(strict_types=1);

use Raxos\Mail\{Email, Recipient, Sender};

covers(Email::class);

it('round trips email addresses with tags', function (): void {
    $email = Email::fromString('bas+review@example.org');
    expect((string)$email)->toBe('bas+review@example.org');
});

it('rejects malformed email addresses', function (string $address): void {
    expect(fn (): Email => Email::fromString($address))->toThrow(Raxos\Mail\Error\InvalidEmailAddressException::class);
})->with(['', 'user', 'user@', '@example.org', 'a@@example.org', "user\n@example.org"]);

it('retains email tags and formatted sender and recipient names', function (string $address): void {
    $email = Email::fromString($address);
    expect((string)$email)->toBe($address)->and(json_encode($email))->toBe(json_encode($address))
        ->and((string)new Sender($email, 'Bas'))->toBe('Bas <' . $address . '>')
        ->and((string)new Recipient($email, 'Bas'))->toBe('Bas <' . $address . '>');
})->with(['bas@example.org', 'bas+tag@example.org', 'bas+tag+extra@example.org']);
