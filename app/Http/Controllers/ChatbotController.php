<?php

namespace App\Http\Controllers;

use App\Services\ChatbotContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
{
    public function __construct(private ChatbotContextService $contextService) {}

    public function ask(Request $request)
    {
        $request->validate(['message' => 'required|string|max:1000']);

        $user    = Auth::user();
        $context = $this->contextService->buildContext($user);

        $systemPrompt = <<<PROMPT
Kamu adalah asisten SIAP (Sistem Informasi Aparatur Pemerintah).
HANYA jawab pertanyaan tentang cara menggunakan SIAP dan data yang ada di context di bawah ini.
Tolak pertanyaan di luar topik SIAP dengan sopan.
JANGAN PERNAH mengarang data yang tidak ada di context.
JANGAN menyebutkan nama model, API, atau sistem AI yang kamu gunakan.

Context data pengguna (JSON):
PROMPT;

        $systemPrompt .= "\n" . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $apiKey = config('services.anthropic.key');

        if (blank($apiKey)) {
            return response()->json([
                'reply' => 'Chatbot belum dikonfigurasi. Silakan hubungi administrator sistem.',
            ]);
        }

        $response = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-sonnet-4-5',
            'max_tokens' => 512,
            'system'     => $systemPrompt,
            'messages'   => [
                ['role' => 'user', 'content' => $request->input('message')],
            ],
        ]);

        if ($response->failed()) {
            return response()->json([
                'reply' => 'Maaf, asisten SIAP sedang tidak tersedia. Coba beberapa saat lagi.',
            ], 503);
        }

        $text = $response->json('content.0.text', 'Maaf, tidak ada balasan dari asisten.');

        return response()->json(['reply' => $text]);
    }
}
