# System Prompts Usage Guide

This guide shows how to use predefined system prompts in the AI Service.

## Available System Prompts

The `AIService` class includes these predefined system prompt constants:

| Constant | Description |
|----------|-------------|
| `AIService::SYSTEM_DEFAULT` | Helpful, friendly, and accurate assistant |
| `AIService::SYSTEM_TUTOR` | Patient tutor with clear explanations |
| `AIService::SYSTEM_CODE_EXPERT` | Programming expert with code examples |
| `AIService::SYSTEM_WRITER` | Creative writing assistant |
| `AIService::SYSTEM_BUSINESS` | Business consultant with strategic advice |
| `AIService::SYSTEM_SCIENTIST` | Scientific explanations with principles |
| `AIService::SYSTEM_TRANSLATOR` | Professional translator |
| `AIService::SYSTEM_SUMMARIZER` | Clear, concise summarization |
| `AIService::SYSTEM_CREATIVE` | Innovative and creative solutions |

## Usage in Controller

### Method 1: Use Default System Prompt (from config or constant)

If no system prompt is provided, it uses the default from config or `SYSTEM_DEFAULT` constant:

```php
// In AIController.php - uses default system prompt automatically
$result = $this->aiService->chat($prompt);
```

### Method 2: Use Predefined Constant

```php
use App\Services\AIService;

// In AIController.php
public function specializedChat(Request $request)
{
    $prompt = $request->input('prompt');

    // Use tutor system prompt
    $result = $this->aiService->chat(
        $prompt,
        AIService::SYSTEM_TUTOR
    );

    return response()->json($result);
}
```

### Method 3: Use Custom System Prompt

```php
// Override with custom system prompt
$customPrompt = 'You are a legal assistant. Provide accurate legal information.';

$result = $this->aiService->chat(
    $prompt,
    $customPrompt
);
```

### Method 4: Set Default System Prompt Dynamically

```php
// Change the default for this instance
$this->aiService->setDefaultSystemPrompt(
    AIService::SYSTEM_CODE_EXPERT
);

// Now all requests use code expert by default
$result = $this->aiService->chat($prompt);
```

## Multiple Controller Examples

### ChatController with Different Modes

```php
<?php

namespace App\Http\Controllers;

use App\Services\AIService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * General chat with default system prompt
     */
    public function chat(Request $request)
    {
        $result = $this->aiService->chat($request->input('prompt'));
        return response()->json($result);
    }

    /**
     * Tutor mode
     */
    public function tutor(Request $request)
    {
        $result = $this->aiService->chat(
            $request->input('prompt'),
            AIService::SYSTEM_TUTOR
        );
        return response()->json($result);
    }

    /**
     * Code expert mode
     */
    public function codeHelp(Request $request)
    {
        $result = $this->aiService->chat(
            $request->input('prompt'),
            AIService::SYSTEM_CODE_EXPERT
        );
        return response()->json($result);
    }

    /**
     * Writing assistant mode
     */
    public function writingHelp(Request $request)
    {
        $result = $this->aiService->chat(
            $request->input('prompt'),
            AIService::SYSTEM_WRITER
        );
        return response()->json($result);
    }

    /**
     * Custom mode with user-defined system prompt
     */
    public function customMode(Request $request)
    {
        $result = $this->aiService->chat(
            $request->input('prompt'),
            $request->input('system_prompt')
        );
        return response()->json($result);
    }
}
```

## Configure Default System Prompt

### Option 1: Use Hardcoded Default (SYSTEM_DEFAULT)

The default system prompt is `AIService::SYSTEM_DEFAULT`:
> "You are a helpful AI assistant. Be friendly, concise, and accurate."

This is automatically used when no system prompt is provided.

### Option 2: Change Default Programmatically

You can change the default at runtime in your controller:

```php
// In your controller's constructor or method
$this->aiService->setDefaultSystemPrompt(
    AIService::SYSTEM_TUTOR  // Now all requests use tutor mode by default
);
```

### Option 3: Per-Request Override

Provide a custom system prompt for specific requests:

```php
// Use constant
$result = $this->aiService->chat($prompt, AIService::SYSTEM_CODE_EXPERT);

// Or custom string
$result = $this->aiService->chat($prompt, 'Custom system prompt here');
```

## Get All Available Prompts

```php
// Get array of all system prompts
$prompts = AIService::getAvailableSystemPrompts();

/*
Returns:
[
    'default' => 'You are a helpful AI assistant...',
    'tutor' => 'You are a patient tutor...',
    'code_expert' => 'You are a programming expert...',
    ...
]
*/
```

## Streaming with System Prompts

```php
// Streaming with predefined system prompt
public function streamTutor(Request $request)
{
    return response()->stream(function () use ($request) {
        $stream = $this->aiService->streamChat(
            $request->input('prompt'),
            AIService::SYSTEM_TUTOR
        );

        foreach ($stream as $chunk) {
            $data = json_encode($chunk);
            echo "data: {$data}\n\n";
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
```

## API Usage Examples

### Using Default System Prompt (SYSTEM_DEFAULT)

```bash
curl -X POST http://localhost:8000/api/chat \
  -H "Content-Type: application/json" \
  -d '{"prompt":"Explain recursion"}'
```

### Using Custom System Prompt (Override Default)

```bash
curl -X POST http://localhost:8000/api/chat \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "Explain recursion",
    "system_prompt": "You are a computer science professor. Use academic examples."
  }'
```

## Best Practices

1. **Use predefined constants** for consistency across your application
2. **Set default programmatically** for specialized controllers
3. **Create specialized endpoints** for different AI behaviors
4. **Document system prompts** in API docs for frontend developers
5. **Test with your AI model** - not all models follow system prompts equally

## Model Compatibility

System prompts work best with:
- ✅ GPT-4, GPT-3.5 (OpenAI)
- ✅ Claude 3, Claude 2 (Anthropic)
- ⚠️ Gemma models (limited support)
- ⚠️ Llama models (varies by version)

Test with your specific model to ensure desired behavior.
