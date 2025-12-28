<?php

namespace App\Exceptions;

use Exception;

class AIServiceException extends Exception
{
    protected string $userMessage;

    /**
     * Create a new AIServiceException instance.
     */
    public function __construct(string $message, string $userMessage = 'An error occurred while processing your request.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->userMessage = $userMessage;
    }

    /**
     * Get the user-friendly error message.
     */
    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    /**
     * Report the exception.
     */
    public function report(): void
    {
        \Log::error('AI Service Error: ' . $this->getMessage(), [
            'exception' => $this,
            'user_message' => $this->userMessage,
        ]);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render($request)
    {
        return response()->json([
            'error' => $this->userMessage,
            'message' => config('app.debug') ? $this->getMessage() : null,
        ], 500);
    }
}
