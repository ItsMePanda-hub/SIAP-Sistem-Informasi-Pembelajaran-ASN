<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Announcement;
use App\Models\Exam;
use App\Models\Training;
use App\Models\Letter;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery\MockInterface;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    public function test_chatbot_endpoint_scopes_data_correctly()
    {
        $user = User::create(['name' => 'Test', 'email' => 'test@it.com', 'password' => 'password', 'role' => 'pegawai', 'unit_kerja' => 'IT']);

        // Data that user CAN see
        $announcement1 = Announcement::create(['title' => 'T1', 'body' => 'C', 'category' => 'rutin', 'target_unit_kerja' => 'IT', 'created_by' => $user->id]);
        $announcement2 = Announcement::create(['title' => 'T2', 'body' => 'C', 'category' => 'rutin', 'target_unit_kerja' => null, 'created_by' => $user->id]);

        // Data that user CANNOT see
        $announcement3 = Announcement::create(['title' => 'T3', 'body' => 'C', 'category' => 'rutin', 'target_unit_kerja' => 'HR', 'created_by' => $user->id]);

        $this->mock(GeminiService::class, function (MockInterface $mock) use ($announcement1, $announcement2, $announcement3) {
            $mock->shouldReceive('ask')
                ->once()
                ->withArgs(function ($systemInstruction, $contextData, $question) use ($announcement1, $announcement2, $announcement3) {
                    $announcements = collect($contextData['announcements']);
                    
                    // Assert it contains announcement1 and announcement2
                    $contains1 = $announcements->contains('id', $announcement1->id);
                    $contains2 = $announcements->contains('id', $announcement2->id);
                    
                    // Assert it DOES NOT contain announcement3
                    $contains3 = $announcements->contains('id', $announcement3->id);
                    
                    return $contains1 && $contains2 && !$contains3 && $question === 'Halo';
                })
                ->andReturn('Mocked Response');
        });

        $response = $this->actingAs($user)->postJson('/chatbot/tanya', [
            'message' => 'Halo'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['reply' => 'Mocked Response']);
    }

    public function test_chatbot_throttles_requests()
    {
        $user = User::create(['name' => 'Test2', 'email' => 'test2@it.com', 'password' => 'password', 'role' => 'pegawai', 'unit_kerja' => 'IT']);

        $this->mock(GeminiService::class, function (MockInterface $mock) {
            $mock->shouldReceive('ask')->andReturn('Response');
        });

        // Loop to hit rate limit (20 per minute)
        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($user)->postJson('/chatbot/tanya', [
                'message' => 'Halo'
            ]);
        }

        // The 21st request should be throttled
        $response = $this->actingAs($user)->postJson('/chatbot/tanya', [
            'message' => 'Halo'
        ]);

        $response->assertStatus(429);
    }
}
