<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class SupportMessage extends Model
{
    protected static string $table = 'support_messages';

    public static function forTicket(int $ticketId): array
    {
        return static::where('ticket_id', $ticketId, 'created_at ASC');
    }
}
