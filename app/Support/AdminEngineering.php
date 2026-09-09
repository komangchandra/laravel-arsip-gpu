<?php

namespace App\Support;

use App\Models\User;

final class AdminEngineering
{
    public const EMAIL = 'admin.engineering@gorbyputrautama.com';

    public static function matches(User $user): bool
    {
        return strcasecmp($user->email, self::EMAIL) === 0;
    }

    private function __construct() {}
}
