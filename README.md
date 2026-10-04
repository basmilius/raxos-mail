<a href="https://bas.dev">
    <img src="https://bmcdn.nl/assets/branding/logo.svg" alt="Bas Milius" height="48" />
</a>

---

# Raxos Mail

Compose mail with typed addresses and send it through SMTP, Mailgun or Postmark.

[Documentation](https://raxos.dev/mail/) | [Packagist](https://packagist.org/packages/raxos/mail) | [Raxos](https://github.com/basmilius/raxos)

- Shared mail, sender, recipient and attachment objects.
- Interchangeable adapters through `MailerInterface`.
- Email parsing, domain helpers and address suggestions.

## Installation

Requires PHP 8.5 or later. Composer checks the remaining package and extension dependencies declared in [composer.json](composer.json).

```sh
composer require "raxos/mail:^3.3"
```

## Usage

```php
<?php
declare(strict_types=1);

use Raxos\Contract\Mail\MailerInterface;
use Raxos\Mail\Email;
use Raxos\Mail\Mail;
use Raxos\Mail\Recipient;
use Raxos\Mail\Sender;

require __DIR__ . '/vendor/autoload.php';

function sendWelcome(MailerInterface $mailer): bool
{
    $message = new Mail(
        subject: 'Welcome',
        html: '<p>Your account is ready.</p>',
        text: 'Your account is ready.',
        sender: new Sender(Email::fromString('hello@example.com'), 'Example'),
        recipients: [new Recipient('reader@example.com', 'Reader')]
    );

    return $mailer->send($message);
}
```

Pass a configured `SMTP`, `Mailgun` or `Postmark` adapter to `sendWelcome()`. Providers require their own SMTP credentials or API configuration. Supply both HTML and plain-text bodies. Recipient types support TO, CC and BCC; provider SDKs are installed by Composer.

## Documentation

- [Composing a mail](https://raxos.dev/mail/composing-mail)
- [Sending mail](https://raxos.dev/mail/sending-mail)
- [Email addresses and suggestions](https://raxos.dev/mail/email-addresses)

## Testing

Run this library's Pest suite from the Raxos workspace:

```sh
git clone --recurse-submodules https://github.com/basmilius/raxos.git
cd raxos
composer install
vendor/bin/pest --testsuite=mail
```

See [Testing Raxos](https://github.com/basmilius/raxos/blob/main/TESTING.md) for PHP extensions, integration services and coverage commands. The library's [Tests workflow](.github/workflows/tests.yml) also runs in GitHub Actions.

## License

[MIT](LICENSE). Copyright (c) 2017 - present Bas Milius.

See [reply-to and submission results](https://raxos.dev/mail/submission-results) for the optional APIs and their lifetime or transport guarantees.
