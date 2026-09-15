<?php

namespace App\Support;

use App\Models\User;

final class PersonalSignatureAssets
{
    /** @var array<string, array{owner: string, assets: array{sign: string, paraf: string}}> */
    private const USERS = [
        'ananda.wahyu@gorbyputrautama.com' => [
            'owner' => 'Wahyu',
            'assets' => [
                'sign' => 'sign-wahyu.png',
                'paraf' => 'paraf-wahyu.png',
            ],
        ],
        'arif.rahman@gorbyputrautama.com' => [
            'owner' => 'Arif',
            'assets' => [
                'sign' => 'sign-arif.png',
                'paraf' => 'paraf-arif.png',
            ],
        ],
    ];

    /** @return array{owner: string, assets: array{sign: string, paraf: string}}|null */
    public static function forUser(User $user): ?array
    {
        return self::USERS[strtolower(trim($user->email))] ?? null;
    }

    public static function supports(User $user): bool
    {
        return self::forUser($user) !== null;
    }

    public static function belongsTo(User $user, string $asset): bool
    {
        $configuration = self::forUser($user);

        return $configuration !== null
            && in_array($asset, $configuration['assets'], true);
    }

    public static function isPersonalAsset(string $asset): bool
    {
        foreach (self::USERS as $configuration) {
            if (in_array($asset, $configuration['assets'], true)) {
                return true;
            }
        }

        return false;
    }
}
