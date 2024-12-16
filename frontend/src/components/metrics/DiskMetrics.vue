<template>
  <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
    <div class="flex items-center justify-between mb-2">
      <span class="text-sm font-medium text-gray-500">Disk I/O</span>
      <span class="p-1.5 bg-yellow-50 rounded-lg">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-500" viewBox="0 0 20 20" fill="currentColor">
          <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" />
          <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" />
        </svg>
      </span>
    </div>
    <div class="text-xs text-gray-500">
      <div class="flex justify-between items-center">
        <span>Read:</span>
        <span class="font-medium">{{ formatBytes(readBytes) }}</span>
      </div>
      <div class="flex justify-between items-center mt-1">
        <span>Write:</span>
        <span class="font-medium">{{ formatBytes(writeBytes) }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { formatBytes } from '@/utils/format'

defineProps({
  readBytes: {
    type: Number,
    default: 0
  },
  writeBytes: {
    type: Number,
    default: 0
  }
})
</script>