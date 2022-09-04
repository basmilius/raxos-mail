<?php
declare(strict_types=1);

namespace Raxos\Mail;

use JetBrains\PhpStorm\ArrayShape;

/**
 * Class EmailAddressResultSeemsOkay
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail
 * @since 1.0.0
 */
final class EmailAddressResultSeemsOkay extends EmailAddressResult
{

    /**
     * EmailAddressResultSeemsOkay constructor.
     *
     * @param EmailAddress $address
     *
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function __construct(public readonly EmailAddress $address)
    {
    }

    /**
     * {@inheritdoc}
     * @author Bas Milius <bas@glybe.nl>
     * @since 1.0.0
     */
    #[ArrayShape([
        'email' => EmailAddress::class
    ])]
    public final function jsonSerialize(): array
    {
        return [
            'email' => $this->address
        ];
    }

}
