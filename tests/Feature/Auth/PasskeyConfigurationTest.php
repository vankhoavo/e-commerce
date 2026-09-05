<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class PasskeyConfigurationTest extends TestCase
{
    public function test_passkeys_follow_the_application_origin_by_default(): void
    {
        config([
            'app.url' => 'http://localhost:8000',
            'fortify.passkeys.relying_party_id' => 'localhost',
            'fortify.passkeys.allowed_origins' => ['http://localhost:8000'],
        ]);

        $this->assertSame('localhost', config('fortify.passkeys.relying_party_id'));
        $this->assertSame(['http://localhost:8000'], config('fortify.passkeys.allowed_origins'));
    }

    public function test_passkey_origin_can_be_explicitly_overridden_for_deployment(): void
    {
        config([
            'fortify.passkeys.relying_party_id' => 'shop.example.com',
            'fortify.passkeys.allowed_origins' => ['https://shop.example.com'],
        ]);

        $this->assertSame('shop.example.com', config('fortify.passkeys.relying_party_id'));
        $this->assertSame(['https://shop.example.com'], config('fortify.passkeys.allowed_origins'));
    }
}
