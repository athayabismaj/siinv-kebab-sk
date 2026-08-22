<?php

namespace Tests\Unit;

use App\Support\PasswordPolicy;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PasswordPolicyTest extends TestCase
{
    public function test_admin_and_owner_require_a_strong_twelve_character_password(): void
    {
        foreach (['admin', 'owner'] as $role) {
            $weak = Validator::make(
                ['password' => 'password123', 'password_confirmation' => 'password123'],
                ['password' => PasswordPolicy::rulesForRole($role)],
            );
            $this->assertTrue($weak->fails(), "Weak {$role} password should be rejected.");

            $strong = Validator::make(
                ['password' => 'KebabSecure12', 'password_confirmation' => 'KebabSecure12'],
                ['password' => PasswordPolicy::rulesForRole($role)],
            );
            $this->assertFalse($strong->fails(), "Strong {$role} password should be accepted.");
        }

        $this->assertTrue(PasswordPolicy::isPrivilegedRole('ADMIN'));
        $this->assertFalse(PasswordPolicy::isPrivilegedRole('kasir'));
    }
}
