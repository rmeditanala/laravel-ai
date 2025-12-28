<script lang="ts">
export default {
  name: 'ChatbotSession',
  data() {
    return {
      messages: [] as Array<{ role: 'user' | 'assistant'; content: string }>,
      currentMessage: '',
      isLoading: false,
      error: '',
      sessions: [] as Array<{ id: string; title: string; timestamp: number }>,
      currentSessionId: null as string | null,
      showSidebar: true,
    }
  },
  mounted() {
    this.loadSessions()
    this.createNewSession()
  },
  methods: {
    loadSessions() {
      const savedSessions = localStorage.getItem('chatSessions')
      if (savedSessions) {
        this.sessions = JSON.parse(savedSessions)
      }
    },

    saveSessions() {
      localStorage.setItem('chatSessions', JSON.stringify(this.sessions))
    },

    createNewSession() {
      const newSession = {
        id: Date.now().toString(),
        title: 'New Chat',
        timestamp: Date.now(),
      }
      this.sessions.unshift(newSession)
      this.saveSessions()
      this.loadSession(newSession.id)
    },

    loadSession(sessionId: string) {
      this.currentSessionId = sessionId
      const savedMessages = localStorage.getItem(`chat_${sessionId}`)
      if (savedMessages) {
        this.messages = JSON.parse(savedMessages)
      } else {
        this.messages = []
      }
    },

    deleteSession(sessionId: string, event: Event) {
      event.stopPropagation()
      const index = this.sessions.findIndex((s) => s.id === sessionId)
      if (index > -1) {
        this.sessions.splice(index, 1)
        this.saveSessions()
        localStorage.removeItem(`chat_${sessionId}`)

        if (this.currentSessionId === sessionId) {
          if (this.sessions.length > 0) {
            this.loadSession(this.sessions[0].id)
          } else {
            this.createNewSession()
          }
        }
      }
    },

    saveCurrentSession() {
      if (this.currentSessionId) {
        localStorage.setItem(`chat_${this.currentSessionId}`, JSON.stringify(this.messages))

        // Update session title based on first user message
        const firstUserMessage = this.messages.find((m) => m.role === 'user')
        if (firstUserMessage) {
          const session = this.sessions.find((s) => s.id === this.currentSessionId)
          if (session && session.title === 'New Chat') {
            session.title = firstUserMessage.content.substring(0, 30) + (firstUserMessage.content.length > 30 ? '...' : '')
            this.saveSessions()
          }
        }
      }
    },

    async sendMessage() {
      if (!this.currentMessage.trim() || this.isLoading) return

      const userMessage = this.currentMessage.trim()
      this.messages.push({
        role: 'user',
        content: userMessage,
      })
      this.currentMessage = ''
      this.error = ''

      this.saveCurrentSession()
      this.scrollToBottom()

      this.isLoading = true

      try {
        const responseStream = await fetch('/api/stream-chat', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'text/event-stream',
          },
          body: JSON.stringify({ prompt: userMessage }),
        })

        if (!responseStream.ok) {
          throw new Error(`HTTP error! status: ${responseStream.status}`)
        }

        // Create a new assistant message that will be filled incrementally
        this.messages.push({
          role: 'assistant',
          content: '',
        })
        const assistantMessageIndex = this.messages.length - 1

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
                  this.messages[assistantMessageIndex].content += data.content
                  this.scrollToBottom()
                }
              } catch (e) {
                console.error('Error parsing SSE data:', e)
              }
            }
          }
        }

        this.saveCurrentSession()
      } catch (err) {
        this.error = err instanceof Error ? err.message : 'An error occurred'
        console.error('Stream error:', err)
      } finally {
        this.isLoading = false
      }
    },

    scrollToBottom() {
      this.$nextTick(() => {
        const container = this.$refs.messagesContainer as HTMLElement
        if (container) {
          container.scrollTop = container.scrollHeight
        }
      })
    },

    formatTime(timestamp: number): string {
      const date = new Date(timestamp)
      const now = new Date()
      const diffMs = now.getTime() - date.getTime()
      const diffMins = Math.floor(diffMs / 60000)

      if (diffMins < 1) return 'Just now'
      if (diffMins < 60) return `${diffMins}m ago`
      if (diffMins < 1440) return `${Math.floor(diffMins / 60)}h ago`
      return date.toLocaleDateString()
    },
  },
}
</script>

<template>
  <div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <div
      v-show="showSidebar"
      class="w-80 bg-gray-900 text-white flex flex-col h-full flex-shrink-0"
    >
      <!-- Sidebar Header -->
      <div class="p-4 border-b border-gray-700">
        <button
          @click="createNewSession"
          class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-lg transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4v16m8-8H4"
            />
          </svg>
          New Chat
        </button>
      </div>

      <!-- Sessions List -->
      <div class="flex-1 overflow-y-auto p-4 space-y-2">
        <div v-if="sessions.length === 0" class="text-gray-400 text-sm text-center py-4">
          No chat history yet
        </div>
        <div
          v-for="session in sessions"
          :key="session.id"
          @click="loadSession(session.id)"
          :class="[
            'p-3 rounded-lg cursor-pointer transition-colors group relative',
            currentSessionId === session.id
              ? 'bg-gray-800'
              : 'hover:bg-gray-800',
          ]"
        >
          <div class="font-medium text-sm truncate">
            {{ session.title }}
          </div>
          <div class="text-xs text-gray-400 mt-1">
            {{ formatTime(session.timestamp) }}
          </div>
          <button
            @click="deleteSession(session.id, $event)"
            class="absolute right-2 top-2 opacity-0 group-hover:opacity-100 p-1 hover:bg-gray-700 rounded transition-opacity"
            title="Delete chat"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
              />
            </svg>
          </button>
        </div>
      </div>

      <!-- Sidebar Footer -->
      <div class="p-4 border-t border-gray-700">
        <button
          @click="showSidebar = false"
          class="w-full flex items-center justify-center gap-2 px-4 py-2 text-sm text-gray-400 hover:text-white transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M11 19l-7-7 7-7m8 14l-7-7 7-7"
            />
          </svg>
          Collapse Sidebar
        </button>
      </div>
    </div>

    <!-- Main Chat Area -->
    <div class="flex-1 flex flex-col h-full">
      <!-- Header -->
      <div class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-900">AI Chatbot</h1>
          <p class="text-sm text-gray-600">Your AI assistant</p>
        </div>
        <button
          v-if="!showSidebar"
          @click="showSidebar = true"
          class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
          title="Show sidebar"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"
            />
          </svg>
        </button>
      </div>

      <!-- Messages Area -->
      <div
        ref="messagesContainer"
        class="flex-1 overflow-y-auto p-6 space-y-4"
      >
        <div v-if="messages.length === 0" class="text-center text-gray-400 py-12">
          <svg
            class="w-16 h-16 mx-auto mb-4 text-gray-300"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
            />
          </svg>
          <p class="text-lg">Start a conversation</p>
          <p class="text-sm mt-2">Ask me anything!</p>
        </div>

        <div
          v-for="(message, index) in messages"
          :key="index"
          :class="[
            'flex',
            message.role === 'user' ? 'justify-end' : 'justify-start',
          ]"
        >
          <div
            :class="[
              'max-w-3xl rounded-lg px-4 py-3',
              message.role === 'user'
                ? 'bg-blue-600 text-white'
                : 'bg-white text-gray-900 border border-gray-200',
            ]"
          >
            <div class="flex items-start gap-3">
              <div
                :class="[
                  'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0',
                  message.role === 'user'
                    ? 'bg-blue-700'
                    : 'bg-gray-200 text-gray-600',
                ]"
              >
                <svg
                  v-if="message.role === 'user'"
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                  />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                  />
                </svg>
              </div>
              <div class="flex-1">
                <div class="font-medium text-sm mb-1">
                  {{ message.role === 'user' ? 'You' : 'AI Assistant' }}
                </div>
                <div class="whitespace-pre-wrap text-sm leading-relaxed">
                  {{ message.content }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Loading Indicator -->
        <div v-if="isLoading" class="flex justify-start">
          <div class="bg-white border border-gray-200 rounded-lg px-4 py-3">
            <div class="flex items-center gap-2">
              <div class="flex space-x-1">
                <div
                  class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                  style="animation-delay: 0ms"
                ></div>
                <div
                  class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                  style="animation-delay: 150ms"
                ></div>
                <div
                  class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"
                  style="animation-delay: 300ms"
                ></div>
              </div>
              <span class="text-sm text-gray-600">AI is thinking...</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Error Display -->
      <div
        v-if="error"
        class="mx-6 mb-4 p-4 bg-red-50 border border-red-200 rounded-lg"
      >
        <p class="text-red-800 text-sm">
          <strong>Error:</strong> {{ error }}
        </p>
      </div>

      <!-- Input Area -->
      <div class="bg-white border-t border-gray-200 p-6">
        <div class="max-w-4xl mx-auto">
          <div class="flex gap-3">
            <textarea
              v-model="currentMessage"
              @keydown.enter.prevent="sendMessage"
              :disabled="isLoading"
              rows="1"
              class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
              placeholder="Type your message here..."
              style="min-height: 48px; max-height: 200px"
            ></textarea>
            <button
              @click="sendMessage"
              :disabled="isLoading || !currentMessage.trim()"
              class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors self-end"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
                />
              </svg>
            </button>
          </div>
          <p class="text-xs text-gray-500 mt-2 text-center">
            Press Enter to send • Shift+Enter for new line
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes bounce {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-4px);
  }
}
</style>
