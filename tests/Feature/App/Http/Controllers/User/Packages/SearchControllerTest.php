<?php

namespace Tests\Feature\App\Http\Controllers\User\Packages;

use App\Models\Hashtag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_searchHashtag_returns_false_if_request_not_ajax()
    {
        $response = $this->post(route('search.hashtag'));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => false
        ]);
    }

    public function test_searchHashtag_returns_false_if_request_not_has_search()
    {
        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('search.hashtag'), []);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => false
        ]);
    }

    public function test_searchHashtag_returns_false_if_user_has_no_hashtags()
    {
        $response = $this->actingAs($this->user)->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('search.hashtag'), ['search' => 'fff']);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => false
        ]);
    }

    public function test_searchHashtag_returns_false_if_user_has_()
    {
        $hashtag = Hashtag::factory(['user_id' => $this->user->id])->create(); //TODO

        $response = $this->actingAs($this->user)->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('search.hashtag'), ['search' => 'fff']);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'status' => false
        ]);
    }
}
