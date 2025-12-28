<script>
import { Head } from '@inertiajs/vue3'
import StreamChat from '@/components/StreamChat.vue'

export default {
  name: 'ChatPage',
  components: {
    Head,
    StreamChat,
  },
}
</script>

<template>
  <Head title="AI Chat" />

  <div class="min-h-screen bg-gray-100">
    <div class="max-w-4xl mx-auto py-8 px-4">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">AI Streaming Chat</h1>
        <p class="text-gray-600 mt-2">AI-powered streaming chat</p>
        <a href="/" class="text-blue-600 hover:text-blue-800 text-sm mt-2 inline-block">
          ← Back to Home
        </a>
      </div>

      <StreamChat />
    </div>
  </div>
</template>

