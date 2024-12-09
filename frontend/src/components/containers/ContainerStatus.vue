<template>
    <span
      class="px-2 py-1 text-xs font-medium rounded-full"
      :class="statusClass"
    >
      {{ status }}
    </span>
  </template>
  
  <script setup>
  import { computed } from 'vue'
  
  const props = defineProps({
    status: {
      type: String,
      required: true
    }
  })
  
  const statusClass = computed(() => {
    switch (props.status.toLowerCase()) {
      case 'running':
        return 'bg-green-100 text-green-800'
      case 'paused':
        return 'bg-yellow-100 text-yellow-800'
      case 'stopped':
        return 'bg-red-100 text-red-800'
      default:
        return 'bg-gray-100 text-gray-800'
    }
  })
  </script>