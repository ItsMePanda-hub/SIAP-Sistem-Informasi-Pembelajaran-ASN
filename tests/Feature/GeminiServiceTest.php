<?php

namespace Tests\Feature;

use App\Services\GeminiService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GeminiServiceTest extends TestCase
{
    public function test_connection_failure_returns_graceful_response_without_logging_secret(): void
    {
        $apiKey = 'test-gemini-api-key';
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
        putenv("GEMINI_API_KEY={$apiKey}");

        $loggedContext = [];
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context) use (&$loggedContext) {
                $loggedContext = $context;

                return $message === 'Koneksi ke Gemini API gagal';
            });

        Http::fake(function (Request $request) use ($apiKey, $url) {
            $this->assertStringNotContainsString('key=', $request->url());

            throw new ConnectionException("cURL error 28: Connection timed out for {$url}?key={$apiKey}");
        });

        $reply = app(GeminiService::class)->ask('Instruksi sistem', [], 'Halo');

        $this->assertSame('Maaf, terjadi kesalahan saat menghubungi asisten AI.', $reply);
        $this->assertStringNotContainsString($apiKey, json_encode($loggedContext));
        $this->assertStringNotContainsString($url, json_encode($loggedContext));
    }
}
