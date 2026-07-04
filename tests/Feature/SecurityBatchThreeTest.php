<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityBatchThreeTest extends TestCase
{
    public function test_web_response_includes_basic_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_cors_defaults_do_not_allow_wildcard_origins(): void
    {
        $this->assertSame(['api/*'], config('cors.paths'));
        $this->assertSame([], config('cors.allowed_origins'));
        $this->assertFalse(config('cors.supports_credentials'));
    }

    public function test_session_cookie_security_defaults_are_safe(): void
    {
        $this->assertTrue(config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
        $this->assertNull(config('session.domain'));
    }

    public function test_web_controllers_do_not_flash_raw_exception_messages(): void
    {
        $files = collect([
            app_path('Http/Controllers/Admin'),
            app_path('Http/Controllers/Owner'),
            app_path('Http/Controllers/Developer'),
            app_path('Http/Controllers/ProfileController.php'),
        ])->flatMap(function (string $path) {
            if (is_file($path)) {
                return [$path];
            }

            return glob($path . DIRECTORY_SEPARATOR . '*.php') ?: [];
        });

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            $this->assertStringNotContainsString("with('error', \$e->getMessage())", $contents, $file);
            $this->assertStringNotContainsString('with("error", $e->getMessage())', $contents, $file);
            $this->assertDoesNotMatchRegularExpression('/response\\(\\)->json\\([^;]*\\$e->getMessage\\(\\)/s', $contents, $file);
        }
    }

    public function test_no_debug_dump_calls_left_in_web_controllers(): void
    {
        $files = collect([
            app_path('Http/Controllers/Admin'),
            app_path('Http/Controllers/Owner'),
            app_path('Http/Controllers/Developer'),
            app_path('Http/Controllers/ProfileController.php'),
        ])->flatMap(function (string $path) {
            if (is_file($path)) {
                return [$path];
            }

            return glob($path . DIRECTORY_SEPARATOR . '*.php') ?: [];
        });

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            $this->assertDoesNotMatchRegularExpression('/\\b(dd|dump|var_dump)\\s*\\(/', $contents, $file);
        }
    }
}
