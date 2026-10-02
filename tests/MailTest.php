<?php
declare(strict_types=1);

use Raxos\Mail\{Attachment, Mail, Recipient, Sender};

covers(Mail::class);

it('preserves the mail value and its wire representation', function (): void {
    $sender = new Sender('unit@example.org', 'Sender');
    $recipient = new Recipient('to@example.org', 'To');
    $attachment = new Attachment('unit.txt', 'bytes');
    $mail = new Mail('subject', 'html', 'text', $sender, [$recipient], [$attachment]);
    expect($mail->subject)->toBe('subject')->and($mail->html)->toBe('html')->and($mail->text)->toBe('text')
        ->and($mail->sender)->toBe($sender)->and($mail->recipients)->toBe([$recipient])->and($mail->attachments)->toBe([$attachment])
        ->and(new Mail('subject', 'html', 'text', $sender, [])->attachments)->toBe([]);
});
