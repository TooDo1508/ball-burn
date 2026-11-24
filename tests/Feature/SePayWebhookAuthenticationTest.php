<?php

namespace Tests\Feature;

use Tests\TestCase;

class SePayWebhookAuthenticationTest extends TestCase
{
    public function test_webhook_fails_closed_when_secret_is_missing(): void
    {
        config(['sepay.webhook_api_key' => null]);

        $this->postJson('/api/webhooks/sepay', [])->assertUnauthorized();
    }

    public function test_webhook_rejects_an_incorrect_api_key_before_validation(): void
    {
        config(['sepay.webhook_api_key' => 'expected-secret']);

        $this->withHeader('Authorization', 'Apikey wrong-secret')
            ->postJson('/api/webhooks/sepay', [])
            ->assertUnauthorized();
    }

    public function test_authenticated_webhook_payload_is_validated(): void
    {
        config(['sepay.webhook_api_key' => 'expected-secret']);

        $this->withHeader('Authorization', 'Apikey expected-secret')
            ->postJson('/api/webhooks/sepay', [])
            ->assertUnprocessable();
    }
}
