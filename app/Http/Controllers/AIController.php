<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatRequest;
use App\Http\Resources\ChatResponseResource;
use App\Services\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AIController extends Controller
{
    protected AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Handle chat requests using AI service
     *
     * @param ChatRequest $request
     * @return JsonResponse
     */
    public function chat(ChatRequest $request): JsonResponse
    {
        try {
            // System prompts are predefined constants in AIService
            // DO NOT accept system prompts from user requests for security reasons
            $result = $this->aiService->chat($request->input('prompt'));

            // Example: To use a different system prompt, uncomment below:
            // $result = $this->aiService->chat(
            //     $request->input('prompt'),
            //     AIService::SYSTEM_TUTOR  // or SYSTEM_CODE_EXPERT, SYSTEM_WRITER, etc.
            // );

            return (new ChatResponseResource($result))
                ->response()
                ->setStatusCode(200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to process chat request',
                'message' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Handle streaming chat requests using AI service
     *
     * @param ChatRequest $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function streamChat(ChatRequest $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->stream(function () use ($request) {
            try {
                // System prompts are predefined constants in AIService
                // DO NOT accept system prompts from user requests for security reasons
                $stream = $this->aiService->streamChat($request->input('prompt'));

                // Example: To use a different system prompt, uncomment below:
                // $stream = $this->aiService->streamChat(
                //     $request->input('prompt'),
                //     AIService::SYSTEM_CODE_EXPERT  // or SYSTEM_TUTOR, SYSTEM_WRITER, etc.
                // );

                foreach ($stream as $chunk) {
                    $data = json_encode($chunk);
                    echo "data: {$data}\n\n";
                    ob_flush();
                    flush();
                }
            } catch (\Exception $e) {
                $errorData = json_encode([
                    'error' => 'Stream interrupted',
                    'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
                    'done' => true,
                ]);
                echo "data: {$errorData}\n\n";
                ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
