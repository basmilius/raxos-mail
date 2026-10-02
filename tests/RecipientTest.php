<?php
declare(strict_types=1);

use Raxos\Mail\{Email, Recipient, RecipientType};

covers(Recipient::class);

it('preserves the mail value and its wire representation', function (): void {
    $email = Email::fromString('unit@example.org');
    $value = new Recipient($email, 'Name', RecipientType::BCC);
    expect($value->email)->toBe($email)->and($value->type)->toBe(RecipientType::BCC)->and((string)$value)->toBe('Name <unit@example.org>')
        ->and(new Recipient('unit@example.org', 'Name')->type)->toBe(RecipientType::TO);
});
