<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    public function ask(string $systemInstruction, array $contextData, string $question): string
    {
        $apiKey = env('GEMINI_API_KEY');
        $model = env('GEMINI_MODEL', 'gemini-2.0-flash');

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $contextJson = json_encode($contextData);

        $prompt = "Context data:\n{$contextJson}\n\nQuestion:\n{$question}";

        $response = Http::post($url, [
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

        if ($response->successful()) {
            $data = $response->json();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, saya tidak dapat menjawab saat ini.';
        }

        return 'Maaf, terjadi kesalahan saat menghubungi asisten AI.';
    }
}
