<?php

namespace Tests\Feature;

<<<<<<< HEAD
=======
use Illuminate\Foundation\Testing\RefreshDatabase;
>>>>>>> 80e3dc5 (First commit)
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_example()
    {
        $response = $this->get('/');

<<<<<<< HEAD
        //the page redirects to the dashboard
        $response->assertStatus(302);
=======
        $response->assertStatus(200);
>>>>>>> 80e3dc5 (First commit)
    }
}
