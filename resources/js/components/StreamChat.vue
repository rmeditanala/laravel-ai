<script lang="ts">
export default {
  name: 'StreamChat',
  data() {
    return {
      prompt: '',
      response: '',
      isLoading: false,
      error: '',
    }
  },
  methods: {
    async sendMessage() {
      if (!this.prompt.trim() || this.isLoading) return

      this.isLoading = true
      this.error = ''
      this.response = ''

      try {
        const responseStream = await fetch('/api/stream-chat', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'text/event-stream',
          },
          body: JSON.stringify({ prompt: this.prompt }),
        })

        if (!responseStream.ok) {
          throw new Error(`HTTP error! status: ${responseStream.status}`)
        }

        const reader = responseStream.body?.getReader()
        const decoder = new TextDecoder()

        if (!reader) {
          throw new Error('Response body is not readable')
        }

        while (true) {
          const { done, value } = await reader.read()

          if (done) break

          const chunk = decoder.decode(value, { stream: true })
          const lines = chunk.split('\n')

          for (const line of lines) {
            if (line.startsWith('data: ')) {
              try {
                const jsonStr = line.slice(6)
                if (!jsonStr.trim()) continue

                const data = JSON.parse(jsonStr)

                if (data.done) {
                  this.isLoading = false
                  break
                }

                if (data.content) {
                  this.response += data.content
                }
              } catch (e) {
                console.error('Error parsing SSE data:', e)
              }
            }
          }
        }
      } catch (err) {
        this.error = err instanceof Error ? err.message : 'An error occurred'
        console.error('Stream error:', err)
      } finally {
        this.isLoading = false
      }
    },
    clearChat() {
      this.prompt = ''
      this.response = ''
      this.error = ''
    },
  },
}
</script>

<template>
  <div class="max-w-4xl mx-auto p-6">
    <div class="bg-white rounded-lg shadow-lg">
      <div class="p-6 border-b border-gray-200">
        <h2 class="text-2xl font-bold text-gray-900">AI Streaming Chat</h2>
        <p class="text-gray-600 mt-1">Chat with AI using real-time streaming</p>
      </div>

      <div class="p-6">
        <!-- Response Display -->
        <div
          class="mb-6 p-4 bg-gray-50 rounded-lg min-h-[200px] border border-gray-200"
        >
          <div v-if="response" class="prose max-w-none">
            <p class="whitespace-pre-wrap">{{ response }}</p>
          </div>
          <div v-else-if="isLoading" class="flex items-center space-x-2">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span class="text-gray-600">AI is thinking...</span>
          </div>
          <div v-else class="text-gray-400 italic">
            Your conversation will appear here...
          </div>
        </div>

        <!-- Error Display -->
        <div v-if="error" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
          <p class="text-red-800">
            <strong>Error:</strong> {{ error }}
          </p>
        </div>

        <!-- Input Form -->
        <div class="space-y-4">
          <div>
            <label
              for="prompt"
              class="block text-sm font-medium text-gray-700 mb-2"
            >
              Your Message
            </label>
            <textarea
              id="prompt"
              v-model="prompt"
              rows="4"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
              placeholder="Type your message here..."
              :disabled="isLoading"
              @keydown.enter.prevent="sendMessage"
            ></textarea>
          </div>

          <div class="flex space-x-3">
            <button
              @click="sendMessage"
              :disabled="isLoading || !prompt.trim()"
              class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
            >
              <span v-if="isLoading">Sending...</span>
              <span v-else>Send Message</span>
            </button>

            <button
              @click="clearChat"
              :disabled="isLoading"
              class="px-6 py-2 bg-gray-200 text-gray-800 font-medium rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:bg-gray-100 disabled:cursor-not-allowed transition-colors"
            >
              Clear
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.prose {
  color: #374151;
  line-height: 1.75;
}
</style>
