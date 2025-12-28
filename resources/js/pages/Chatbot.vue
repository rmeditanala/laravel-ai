<script>
import { Head } from '@inertiajs/vue3'
import ChatbotSession from '@/components/ChatbotSession.vue'

export default {
  name: 'ChatbotPage',
  components: {
    Head,
    ChatbotSession,
  },
}
</script>

<template>
  <Head title="AI Chatbot" />
  <ChatbotSession />
</template>
