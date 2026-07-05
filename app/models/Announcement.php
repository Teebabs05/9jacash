<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Announcement extends Model
{
    protected static string $table = 'announcements';

    public static function activeOnes(): array
    {
        return static::where('is_active', 1, 'created_at DESC');
    }
}
