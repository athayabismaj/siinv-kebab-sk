<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\Rules\Password;

final class PasswordPolicy
{
    private const PRIVILEGED_ROLES = ['admin', 'owner', 'superadmin', 'developer'];

    /** @return array<int, mixed> */
    public static function rulesForUser(User $user, bool $required = true, bool $confirmed = true): array
    {
        return self::rulesForRole((string) $user->role?->name, $required, $confirmed);
    }

    /** @return array<int, mixed> */
    public static function rulesForRole(string $roleName, bool $required = true, bool $confirmed = true): array
    {
        $privileged = self::isPrivilegedRole($roleName);
        $password = $privileged
            ? Password::min(12)->letters()->mixedCase()->numbers()
            : Password::min(6);

        $rules = [$required ? 'required' : 'nullable', 'string', $password];
        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }

    public static function isPrivilegedRole(string $roleName): bool
    {
        return in_array(strtolower(trim($roleName)), self::PRIVILEGED_ROLES, true);
    }
}
