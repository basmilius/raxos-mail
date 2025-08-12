<?php
declare(strict_types=1);

namespace Raxos\Mail;

/**
 * Class Attachment
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail
 * @since 2.0.0
 */
final readonly class Attachment
{

    /**
     * Attachment constructor.
     *
     * @param string $name
     * @param string $content
     *
     * @author Bas Milius <bas@mili.us>
     * @since 2.0.0
     */
    public function __construct(
        public string $name,
        public string $content
    ) {}

}
