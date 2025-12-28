# Laravel AI Chat Application

A modern Laravel-based AI chat application with real-time streaming capabilities, built with Vue 3, Inertia.js, and OpenRouter API integration.

## 🌟 Features

- **Real-time Streaming** - Watch AI responses stream character-by-character
- **Multiple AI Providers** - Provider-agnostic architecture (OpenRouter, OpenAI, Anthropic, etc.)
- **Two Chat Interfaces**
  - Simple Streaming Chat UI
  - Full-featured Chatbot with session management
- **Chat History** - LocalStorage-based session persistence
- **Beautiful UI** - Modern, responsive design with Tailwind CSS
- **RESTful API** - Clean API endpoints for chat and streaming
- **Server-Sent Events (SSE)** - Efficient streaming implementation

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & NPM
- OpenRouter API key (or other AI provider)

## 🚀 Installation

### 1. Clone and Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 2. Environment Configuration

Copy the example environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

### 3. Configure AI Service

Edit your `.env` file and add your AI provider credentials.

#### For OpenRouter (Recommended - Free Tier Available):

1. **Get OpenRouter API Key:**
   - Visit [https://openrouter.ai/](https://openrouter.ai/)
   - Sign up for a free account
   - Get your API key from the dashboard

2. **Add to `.env`:**

```env
# AI Service Configuration
AI_API_KEY=sk-or-v1-your-openrouter-api-key-here
AI_BASE_URL=https://openrouter.ai/api/v1
AI_MODEL=google/gemma-3-4b-it:free
```

**Popular Free Models on OpenRouter:**
- `google/gemma-3-4b-it:free` - Google's Gemma (recommended)
- `google/gemma-2-9b-it:free` - Larger Gemma model
- `mistralai/mistral-7b-instruct:free` - Mistral 7B

#### For OpenAI:

```env
AI_API_KEY=sk-your-openai-api-key-here
AI_BASE_URL=https://api.openai.com/v1
AI_MODEL=gpt-4
```

#### For Anthropic Claude:

```env
AI_API_KEY=sk-ant-your-anthropic-api-key-here
AI_BASE_URL=https://api.anthropic.com/v1
AI_MODEL=claude-3-haiku-20240307
```

### 4. Database Setup

```bash
# Create sqlite database (default)
touch database/database.sqlite

# Or configure your MySQL/PostgreSQL in .env
# Run migrations
php artisan migrate
```

### 5. Build Frontend Assets

```bash
npm run build
```

## 🎬 Running the Application

### Development Mode

Start all services (Laravel server, queue worker, logs, Vite):

```bash
npm run dev
```

Or start individually:

```bash
# Terminal 1 - Laravel server
php artisan serve

# Terminal 2 - Vite dev server
npm run dev
```

### Production Mode

```bash
# Build assets
npm run build

# Start Laravel server
php artisan serve --host=0.0.0.0 --port=8000
```

## 🧪 Testing the Application

### Method 1: Web Interface (Recommended)

#### Simple Chat UI:
```
http://localhost:8000/chat
```

#### Chatbot UI with Session History:
```
http://localhost:8000/chatbot
```

**Features:**
- Type messages and see AI responses stream in real-time
- Create new chat sessions
- View chat history in sidebar
- Delete individual sessions
- All chats persist in localStorage

### Method 2: API Testing with cURL

#### Test Regular Chat Endpoint:

```bash
curl -X POST http://localhost:8000/api/chat \
  -H "Content-Type: application/json" \
  -d '{"prompt":"What is 2+2?"}'
```

**Response:**
```json
{
  "data": {
    "response": "2 + 2 = 4\n",
    "model": "google/gemma-3-4b-it:free",
    "usage": {
      "prompt_tokens": 8,
      "completion_tokens": 5,
      "total_tokens": 13
    }
  }
}
```

#### Test Streaming Chat Endpoint:

```bash
curl -X POST http://localhost:8000/api/stream-chat \
  -H "Content-Type: application/json" \
  -H "Accept: text/event-stream" \
  -d '{"prompt":"Tell me a joke"}' \
  --no-buffer
```

**Response (Server-Sent Events):**
```
data: {"content":"Why","done":false}

data: {"content":" did","done":false}

data: {"content":" the","done":false}

data: {"content":" chicken","done":false}

data: {"content":" cross","done":false}

data: {"content":" the","done":false}

data: {"content":" road","done":false}

data: {"content":"?","done":false}

data: {"content":"\n\n","done":false}

data: {"content":"To","done":false}

data: {"content":" get","done":false}

data: {"content":" to","done":false}

data: {"content":" the","done":false}

data: {"content":" other","done":false}

data: {"content":" side","done":false}

data: {"content":"!","done":false}

data: {"content":"","done":true}
```

### Method 3: Using Postman

**Note:** Postman has limited SSE support. Use the web interface or cURL for testing streaming.

1. Create POST request to `http://localhost:8000/api/chat`
2. Set Headers:
   - `Content-Type: application/json`
3. Body (raw JSON):
   ```json
   {
     "prompt": "What is the capital of France?"
   }
   ```
4. Send request

## 📡 API Endpoints

### Public Routes (No Authentication Required)

#### Chat - Regular (Non-streaming)
```http
POST /api/chat
Content-Type: application/json

{
  "prompt": "Your question here"
}
```

**Response:** Complete JSON response with usage statistics

#### Chat - Streaming
```http
POST /api/stream-chat
Content-Type: application/json
Accept: text/event-stream

{
  "prompt": "Your question here"
}
```

**Response:** Server-Sent Events stream

### Web Routes

| Route | URL | Description |
|-------|-----|-------------|
| Home | `/` | Application homepage |
| Simple Chat | `/chat` | Basic streaming chat UI |
| Chatbot | `/chatbot` | Full chatbot with sessions |
| Dashboard | `/dashboard` | Protected dashboard (requires auth) |

## 🏗️ Project Structure

```
laravel-ai/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── AIController.php          # Main AI controller
│   │   ├── Requests/
│   │   │   └── ChatRequest.php            # Form validation
│   │   └── Resources/
│   │       └── ChatResponseResource.php   # API resource formatting
│   ├── Services/
│   │   └── AIService.php                  # AI business logic
│   └── Exceptions/
│       └── AIServiceException.php         # Custom exception
├── config/
│   └── services.php                       # AI service configuration
├── resources/
│   └── js/
│       ├── components/
│       │   ├── StreamChat.vue            # Simple chat component
│       │   └── ChatbotSession.vue        # Chatbot with sessions
│       └── pages/
│           ├── Chat.vue                  # Simple chat page
│           └── Chatbot.vue               # Chatbot page
├── routes/
│   ├── api.php                           # API routes
│   └── web.php                           # Web routes
└── .env.example                          # Environment template
```

## 🎨 Frontend Technologies

- **Vue 3** - Progressive JavaScript framework
- **Vite** - Next-generation frontend tooling
- **Inertia.js** - Modern monolith SPA
- **Tailwind CSS** - Utility-first CSS framework
- **TypeScript** - Type-safe JavaScript

## 🔧 Configuration

### AI Service Configuration (`config/services.php`)

```php
'ai' => [
    'key' => env('AI_API_KEY'),
    'base_url' => env('AI_BASE_URL', 'https://openrouter.ai/api/v1'),
    'model' => env('AI_MODEL', 'google/gemma-3-4b-it:free'),
],
```

### Switching AI Providers

Simply update your `.env` file:

```env
# Switch from OpenRouter to OpenAI
AI_API_KEY=sk-your-openai-key
AI_BASE_URL=https://api.openai.com/v1
AI_MODEL=gpt-4
```

No code changes required!

## 🔒 Security Considerations

- **Never commit** `.env` file to version control
- **Rotate API keys** regularly
- **Use HTTPS** in production
- **Implement rate limiting** for production use
- **Add authentication** for production routes
- **Validate and sanitize** all user inputs

## 🐛 Troubleshooting

### Issue: "AI Service Error"

**Solution:** Check your `AI_API_KEY` in `.env` file and ensure it's valid.

### Issue: Streaming not working in Postman

**Solution:** Postman doesn't support SSE well. Use:
- Web interface: `http://localhost:8000/chatbot`
- cURL command
- Browser console

### Issue: CORS errors

**Solution:** Ensure your Laravel server is running and Vite is configured correctly.

### Issue: Messages not saving

**Solution:** Check browser localStorage. Ensure you're not in private/incognito mode.

## 📦 Building for Production

```bash
# Optimize frontend
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear caches if needed
php artisan cache:clear
php artisan view:clear
```

## 🚀 Deployment

### Recommended Deployment Platforms

- **Laravel Forge** - Official Laravel deployment
- **Vapor** - Serverless Laravel deployment
- **DigitalOcean** - VPS deployment
- **Heroku** - Easy deployment

### Environment Variables

Set these in your production environment:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

AI_API_KEY=your-production-key
AI_BASE_URL=https://openrouter.ai/api/v1
AI_MODEL=your-production-model
```

## 📝 License

This project is open-sourced software licensed under the MIT license.

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📧 Support

For support, email support@example.com or open an issue in the repository.

## 🙏 Acknowledgments

- [Laravel](https://laravel.com/) - The PHP Framework for Web Artisans
- [Vue.js](https://vuejs.org/) - The Progressive JavaScript Framework
- [OpenRouter](https://openrouter.ai/) - Unified API for AI models
- [Inertia.js](https://inertiajs.com/) - Build modern SPAs with classic server-side routing

---

Made with ❤️ using Laravel and Vue.js
