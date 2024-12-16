<template>
  <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
    <div class="flex items-center justify-between mb-2">
      <span class="text-sm font-medium text-gray-500">Network I/O</span>
      <span class="p-1.5 bg-green-50 rounded-lg">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-500" viewBox="0 0 20 20" fill="currentColor">
          <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
        </svg>
      </span>
    </div>
    <div class="text-xs text-gray-500">
      <div class="flex justify-between items-center">
        <span>RX:</span>
        <span class="font-medium">{{ formatBytes(rxBytes) }}</span>
      </div>
      <div class="flex justify-between items-center mt-1">
        <span>TX:</span>
        <span class="font-medium">{{ formatBytes(txBytes) }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { formatBytes } from '@/utils/format'

defineProps({
  rxBytes: {
    type: Number,
    default: 0
  },
  txBytes: {
    type: Number,
    default: 0
  }
})
</script>