<?php
declare(strict_types=1);

use Raxos\Mail\{Attachment};

covers(Attachment::class);

it('preserves the mail value and its wire representation', function (): void {
    $value = new Attachment('unit.txt', 'bytes' . chr(0));
    expect($value->name)->toBe('unit.txt')->and($value->content)->toBe('bytes' . chr(0));
});
