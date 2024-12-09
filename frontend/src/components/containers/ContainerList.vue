<template>
    <div>
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Containers</h2>
        <button 
          @click="refreshContainers"
          class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
        >
          Refresh
        </button>
      </div>
      
      <div v-if="loading" class="text-center py-8">
        <p class="text-gray-500">Loading containers...</p>
      </div>
      
      <div v-else-if="error" class="text-center py-8">
        <p class="text-red-500">{{ error }}</p>
      </div>
      
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <ContainerCard
          v-for="container in containers"
          :key="container.container_id"
          :container="container"
          @click="$emit('select-container', container)"
          class="cursor-pointer"
        />
      </div>
    </div>
  </template>
  
  <script setup>
  import { onMounted } from 'vue'
  import { storeToRefs } from 'pinia'
  import { useContainerStore } from '@/stores/containers'
  import ContainerCard from './ContainerCard.vue'
  
  const containerStore = useContainerStore()
  const { containers, loading, error } = storeToRefs(containerStore)
  
  const refreshContainers = () => {
    containerStore.fetchContainers()
  }
  
  onMounted(() => {
    refreshContainers()
  })
  </script>