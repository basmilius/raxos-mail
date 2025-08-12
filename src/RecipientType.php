<?php
declare(strict_types=1);

namespace Raxos\Mail;

/**
 * Enum RecipientType
 *
 * @author Bas Milius <bas@mili.us>
 * @package Raxos\Mail
 * @since 2.0.0
 */
enum RecipientType
{
    case TO;
    case CC;
    case BCC;
}
