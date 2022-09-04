<?php
declare(strict_types=1);

namespace Raxos\Mail;

use JetBrains\PhpStorm\ArrayShape;

/**
 * Class EmailAddressResultWithSuggestions
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail
 * @since
 */
final class EmailAddressResultWithSuggestions extends EmailAddressResult
{

    /**
     * EmailAddressResultWithSuggestions constructor.
     *
     * @param EmailAddress $address
     * @param EmailAddress[] $suggestions
     *
     * @author Bas Milius <bas@mili.us>
     * @since 1.0.0
     */
    public function __construct(
        public readonly EmailAddress $address,
        public readonly array $suggestions
    )
    {
    }

    /**
     * {@inheritdoc}
     * @author Bas Milius <bas@glybe.nl>
     * @since 1.0.0
     */
    #[ArrayShape([
        'email' => 'Raxos\Mail\EmailAddress',
        'suggestions' => 'Raxos\Mail\EmailAddress[]'
    ])]
    public final function jsonSerialize(): array
    {
        return [
            'email' => $this->address,
            'suggestions' => $this->suggestions
        ];
    }

}
