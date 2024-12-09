<template>
    <div class="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-lg font-medium text-gray-900">
            {{ container.name }}
          </h3>
          <p class="text-sm text-gray-500">{{ container.image }}</p>
        </div>
        <ContainerStatus :status="container.status" />
      </div>
      
      <div class="mt-4 grid grid-cols-2 gap-4">
        <div class="border rounded p-3">
          <p class="text-sm text-gray-500">CPU</p>
          <p class="text-lg font-semibold">
            {{ container.metrics?.cpu?.percentage.toFixed(1) }}%
          </p>
        </div>
        <div class="border rounded p-3">
          <p class="text-sm text-gray-500">Memory</p>
          <p class="text-lg font-semibold">
            {{ formatMemory(container.metrics?.memory?.used) }}
          </p>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { defineProps } from 'vue'
  import ContainerStatus from './ContainerStatus.vue'
  
  const props = defineProps({
    container: {
      type: Object,
      required: true
    }
  })
  
  function formatMemory(bytes) {
    if (!bytes) return '0 MB'
    const mb = bytes / (1024 * 1024)
    return `${mb.toFixed(1)} MB`
  }
  </script>