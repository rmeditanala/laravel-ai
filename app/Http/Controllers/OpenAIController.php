<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenAI;

class OpenAIController extends Controller
{
    /**
     * Handle chat requests using OpenRouter API
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string|max:4000',
        ]);

        $apiKey = config('services.openrouter.key');
        $baseUrl = config('services.openrouter.base_url');

        $client = OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri($baseUrl)
            ->make();

        $response = $client->chat()->create([
            'model' => 'google/gemma-3-4b-it:free',
            'messages' => [
                ['role' => 'user', 'content' => $request->input('prompt')],
            ],
        ]);

        // dd($response);

        return response()->json([
            'response' => $response->choices[0]->message->content,
            // 'model' => $response->model,
            // 'usage' => [
            //     'prompt_tokens' => $response->usage->promptTokens,
            //     'completion_tokens' => $response->usage->completionTokens,
            //     'total_tokens' => $response->usage->totalTokens,
            // ],
        ]);
    }

    /**
     * Handle streaming chat requests using OpenRouter API
     */
    public function streamChat(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $request->validate([
            'prompt' => 'required|string|max:4000',
        ]);

        $apiKey = config('services.openrouter.key');
        $baseUrl = config('services.openrouter.base_url');

        $client = OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri($baseUrl)
            ->make();

        return response()->stream(function () use ($client, $request) {
            $stream = $client->chat()->createStreamed([
                'model' => 'google/gemma-3-4b-it:free',
                'messages' => [
                    ['role' => 'user', 'content' => $request->input('prompt')],
                ],
            ]);

            foreach ($stream as $chunk) {
                $content = $chunk->choices[0]->delta->content ?? '';

                if (!empty($content)) {
                    // Format as Server-Sent Event
                    $data = json_encode([
                        'content' => $content,
                        'done' => false,
                    ]);

                    echo "data: {$data}\n\n";
                    ob_flush();
                    flush();
                }
            }

            // Send final completion signal
            $finalData = json_encode([
                'content' => '',
                'done' => true,
            ]);
            echo "data: {$finalData}\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
