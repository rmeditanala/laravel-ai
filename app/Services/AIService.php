<?php

namespace App\Services;

use OpenAI;
use OpenAI\Contracts\ResponseContract;
use Illuminate\Support\ServiceProvider;

class AIService
{
    // Predefined system prompts
    public const SYSTEM_DEFAULT = 'You are a helpful AI assistant. Be friendly, concise, and accurate.';
    public const SYSTEM_TUTOR = 'You are a patient tutor. Explain concepts clearly and provide examples. Be encouraging.';
    public const SYSTEM_CODE_EXPERT = 'You are a programming expert. Provide clean, well-commented code examples. Explain technical concepts clearly.';
    public const SYSTEM_WRITER = 'You are a creative writing assistant. Help with brainstorming, editing, and improving written content.';
    public const SYSTEM_BUSINESS = 'You are a business consultant. Provide professional, strategic advice with practical recommendations.';
    public const SYSTEM_SCIENTIST = 'You are a scientist. Explain scientific concepts accurately with appropriate detail. Cite scientific principles.';
    public const SYSTEM_TRANSLATOR = 'You are a professional translator. Provide accurate translations while preserving meaning and tone.';
    public const SYSTEM_SUMMARIZER = 'You are a summarization expert. Create clear, concise summaries highlighting key points.';
    public const SYSTEM_CREATIVE = 'You are a creative assistant. Think outside the box and provide innovative ideas and solutions.';

    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected ?string $defaultSystemPrompt = null;

    public function __construct()
    {
        $this->apiKey = config('services.ai.key');
        $this->baseUrl = config('services.ai.base_url');
        $this->model = config('services.ai.model');

        // Use the SYSTEM_DEFAULT constant as the default
        $this->defaultSystemPrompt = self::SYSTEM_DEFAULT;
    }

    /**
     * Set a custom default system prompt
     */
    public function setDefaultSystemPrompt(string $prompt): void
    {
        $this->defaultSystemPrompt = $prompt;
    }

    /**
     * Get the current default system prompt
     */
    public function getDefaultSystemPrompt(): string
    {
        return $this->defaultSystemPrompt;
    }

    /**
     * Get all available system prompt constants
     */
    public static function getAvailableSystemPrompts(): array
    {
        return [
            'default' => self::SYSTEM_DEFAULT,
            'tutor' => self::SYSTEM_TUTOR,
            'code_expert' => self::SYSTEM_CODE_EXPERT,
            'writer' => self::SYSTEM_WRITER,
            'business' => self::SYSTEM_BUSINESS,
            'scientist' => self::SYSTEM_SCIENTIST,
            'translator' => self::SYSTEM_TRANSLATOR,
            'summarizer' => self::SYSTEM_SUMMARIZER,
            'creative' => self::SYSTEM_CREATIVE,
        ];
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
     * @param string|null $systemPrompt
     * @return array{response: string, model: string, usage: array}
     * @throws \Exception
     */
    public function chat(string $prompt, ?string $systemPrompt = null): array
    {
        try {
            $messages = [];

            // Only add system prompt if explicitly provided (not default)
            // This ensures models that don't support system prompts work properly
            if ($systemPrompt && $systemPrompt !== $this->defaultSystemPrompt) {
                $messages[] = ['role' => 'system', 'content' => $systemPrompt];
            }

            // Add user message
            $messages[] = ['role' => 'user', 'content' => $prompt];

            $response = $this->client()->chat()->create([
                'model' => $this->model,
                'messages' => $messages,
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
     * @param string|null $systemPrompt
     * @return \Generator
     * @throws \Exception
     */
    public function streamChat(string $prompt, ?string $systemPrompt = null): \Generator
    {
        try {
            $messages = [];

            // Only add system prompt if explicitly provided (not default)
            // This ensures models that don't support system prompts work properly
            if ($systemPrompt && $systemPrompt !== $this->defaultSystemPrompt) {
                $messages[] = ['role' => 'system', 'content' => $systemPrompt];
            }

            // Add user message
            $messages[] = ['role' => 'user', 'content' => $prompt];

            $stream = $this->client()->chat()->createStreamed([
                'model' => $this->model,
                'messages' => $messages,
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
