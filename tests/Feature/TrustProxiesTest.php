<?php

namespace Tests\Feature;

use Tests\TestCase;

class TrustProxiesTest extends TestCase
{
    private ?string $environment = null;

    public function createApplication()
    {
        if ($this->environment) {
            $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = $this->environment;
            putenv('APP_ENV='.$this->environment);
        }

        return parent::createApplication();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
        putenv('APP_ENV=testing');
    }

    private function bootIn(string $environment): void
    {
        $this->environment = $environment;
        $this->refreshApplication();
    }

    public function test_forwarded_https_is_trusted_in_local(): void
    {
        $this->bootIn('local');

        $this->assertTrue($this->app->isLocal());
        $this->get('/login', ['X-Forwarded-Proto' => 'https'])
            ->assertOk()
            ->assertSee('action="https://', false);
    }

    public function test_forwarded_headers_are_ignored_outside_local(): void
    {
        foreach (['production', 'testing'] as $environment) {
            $this->bootIn($environment);

            $this->get('/login', ['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'evil.example'])
                ->assertOk()
                ->assertSee('action="http://', false)
                ->assertDontSee('evil.example');
        }
    }
}
