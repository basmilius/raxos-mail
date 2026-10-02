<?php
declare(strict_types=1);

use Raxos\Mail\{Email, Sender};

covers(Sender::class);

it('preserves the mail value and its wire representation', function (): void {
    $email = Email::fromString('unit+tag@example.org');
    $value = new Sender($email, 'Name');
    expect($value->email)->toBe($email)->and($value->name)->toBe('Name')->and((string)$value)->toBe('Name <unit+tag@example.org>');
});
