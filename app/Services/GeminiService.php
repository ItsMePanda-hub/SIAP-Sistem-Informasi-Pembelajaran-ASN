<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    public function ask(string $systemInstruction, array $contextData, string $question): string
    {
        $apiKey = env('GEMINI_API_KEY');
        $model = env('GEMINI_MODEL', 'gemini-2.0-flash');

        $contextJson = json_encode($contextData);

        $prompt = "Context data:\n{$contextJson}\n\nQuestion:\n{$question}";

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $apiKey])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => $systemInstruction]
                        ]
                    ],
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]);
        } catch (ConnectionException $exception) {
            Log::error('Koneksi ke Gemini API gagal', [
                'exception' => class_basename($exception),
                'message' => $this->safeErrorMessage($exception->getMessage(), $apiKey),
                'connection' => 'failed',
            ]);

            return 'Maaf, terjadi kesalahan saat menghubungi asisten AI.';
        } catch (\Exception $exception) {
            Log::error('Permintaan ke Gemini API gagal', [
                'exception' => class_basename($exception),
                'message' => $this->safeErrorMessage($exception->getMessage(), $apiKey),
            ]);

            return 'Maaf, terjadi kesalahan saat menghubungi asisten AI.';
        }

        if ($response->successful()) {
            $data = $response->json();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, saya tidak dapat menjawab saat ini.';
        }

        Log::error('Gemini API gagal', [
            'status' => $response->status(),
        ]);

        return 'Maaf, terjadi kesalahan saat menghubungi asisten AI.';
    }

    private function safeErrorMessage(string $message, ?string $apiKey): string
    {
        $safeMessage = preg_replace('#https?://[^\s]+#', '[redacted URL]', $message) ?? 'Unknown error';

        return $apiKey ? str_replace($apiKey, '[redacted API key]', $safeMessage) : $safeMessage;
    }
}
