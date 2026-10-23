<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Test tidak boleh memanggil internet sungguhan. Request HTTP yang tidak
        // di-fake akan langsung gagal, jadi CI tetap stabil dan cepat.
        Http::preventStrayRequests();
    }
}
