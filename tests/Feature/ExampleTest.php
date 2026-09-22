<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_application_container_boots(): void
    {
        $this->assertTrue(app()->bound('db'));
    }
}
