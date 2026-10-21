<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    /**
     * RefreshDatabase migrates the in-memory database once per process and every class shares it,
     * so always seed at that point instead of only when the first class to run sets $seed.
     */
    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Tests never talk to Midtrans (or anything else); every request must be faked.
        Http::preventStrayRequests();
    }
}
