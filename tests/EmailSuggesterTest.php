<?php
declare(strict_types=1);

use Raxos\Mail\{Email, EmailSuggester};

covers(EmailSuggester::class);

it('suggests a provider typo while preserving the username and tag', function (): void {
    $suggestions = EmailSuggester::for('unit+tag@gmai.com');
    expect(array_map(strval(...), $suggestions ?? []))->toBe(['unit+tag@gmail.com']);
});

it('keeps valid addresses and suggests suffix typos with the same mailbox', function (): void {
    expect(EmailSuggester::for(new Email('unit', 'gmail.com')))->toBeNull()
        ->and(EmailSuggester::for('unit@example.org'))->toBeNull();
    $suggestions = EmailSuggester::for('unit+tag@example.cmo');
    expect($suggestions)->toHaveCount(3);
    foreach ($suggestions as $email) {
        expect($email->username)->toBe('unit')->and($email->tag)->toBe('tag');
    }
});

it('corrects provider and suffix typos together', function (): void {
    $suggestions = EmailSuggester::for('unit@gmai.cmo');
    expect($suggestions)->toHaveCount(3);
    foreach ($suggestions as $email) {
        expect($email->domain)->toStartWith('gmail.');
    }
});
