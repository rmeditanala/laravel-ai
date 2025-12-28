<?php

namespace App\Services;

use OpenAI;
use OpenAI\Contracts\ResponseContract;
use Illuminate\Support\ServiceProvider;

class AIService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.ai.key');
        $this->baseUrl = config('services.ai.base_url');
        $this->model = config('services.ai.model');
    }

    /**
     * Create an OpenAI client instance
     */
    protected function client(): \OpenAI\Contracts\ClientContract
    {
        return OpenAI::factory()
            ->withApiKey($this->apiKey)
            ->withBaseUri($this->baseUrl)
            ->make();
    }

    /**
     * Send a chat request and get the complete response
     *
     * @param string $prompt
     * @return array{response: string, model: string, usage: array}
     * @throws \Exception
     */
    public function chat(string $prompt): array
    {
        try {
            $response = $this->client()->chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            return [
                'response' => $response->choices[0]->message->content,
                'model' => $response->model,
                'usage' => [
                    'prompt_tokens' => $response->usage->promptTokens,
                    'completion_tokens' => $response->usage->completionTokens,
                    'total_tokens' => $response->usage->totalTokens,
                ],
            ];
        } catch (\Exception $e) {
            throw new \Exception("AI chat request failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Send a streaming chat request
     * Returns a generator that yields response chunks
     *
     * @param string $prompt
     * @return \Generator
     * @throws \Exception
     */
    public function streamChat(string $prompt): \Generator
    {
        try {
            $stream = $this->client()->chat()->createStreamed([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            foreach ($stream as $chunk) {
                $content = $chunk->choices[0]->delta->content ?? '';

                if (!empty($content)) {
                    yield [
                        'content' => $content,
                        'done' => false,
                    ];
                }
            }

            // Send completion signal
            yield [
                'content' => '',
                'done' => true,
            ];
        } catch (\Exception $e) {
            throw new \Exception("AI stream chat request failed: {$e->getMessage()}", 0, $e);
        }
    }
}
