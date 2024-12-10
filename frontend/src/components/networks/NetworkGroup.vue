<template>
    <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-lg overflow-hidden mb-8">
      <!-- 네트워크 그룹 헤더 -->
      <div class="p-6 border-b border-gray-100">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center">
            <span class="p-2 bg-blue-50 rounded-lg mr-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 00-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
              </svg>
            </span>
            <h3 class="text-xl font-semibold text-gray-900">
              {{ name }}
            </h3>
          </div>
          <div class="flex items-center">
            <div class="text-sm text-gray-500">
              Last updated: {{ formatDate(new Date()) }}
            </div>
          </div>
        </div>
        <NetworkSummary :stats="stats" />
      </div>
   
      <!-- 컨테이너 목록 -->
      <div class="p-6 bg-gray-50">
        <transition-group
          name="container-list"
          tag="div"
          class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
        >
          <ContainerCard
            v-for="container in sortedContainers"
            :key="container.container_id"
            :container="container"
            @click="$emit('select-container', container)"
          />
        </transition-group>
   
        <!-- 컨테이너가 없는 경우 -->
        <div 
          v-if="containers.length === 0" 
          class="flex flex-col items-center justify-center py-12"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mb-4" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
          </svg>
          <p class="text-gray-500 text-lg">No containers in this network</p>
        </div>
      </div>
    </div>
   </template>
   
   <script setup>
   import { computed } from 'vue'
   import NetworkSummary from './NetworkSummary.vue'
   import ContainerCard from '../containers/ContainerCard.vue'
   
   const props = defineProps({
    name: {
      type: String,
      required: true
    },
    containers: {
      type: Array,
      required: true
    },
    stats: {
      type: Object,
      required: true,
      default: () => ({
        totalContainers: 0,
        runningContainers: 0,
        stoppedContainers: 0,
        totalCpu: 0,
        totalMemory: 0
      })
    }
   })
   
   defineEmits(['select-container'])
   
   const sortedContainers = computed(() => {
    return [...props.containers].sort((a, b) => {
      // 실행 중인 컨테이너를 먼저 보여줌
      if (a.status.toLowerCase() === 'running' && b.status.toLowerCase() !== 'running') return -1
      if (a.status.toLowerCase() !== 'running' && b.status.toLowerCase() === 'running') return 1
      // CPU 사용량으로 정렬
      return (b.metrics?.cpu?.percentage || 0) - (a.metrics?.cpu?.percentage || 0)
    })
   })
   
   function formatDate(date) {
    return new Intl.DateTimeFormat('default', {
      hour: 'numeric',
      minute: 'numeric',
      second: 'numeric'
    }).format(date)
   }
   </script>
   
   <style scoped>
   .container-list-move,
   .container-list-enter-active,
   .container-list-leave-active {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
   }
   
   .container-list-enter-from,
   .container-list-leave-to {
    opacity: 0;
    transform: scale(0.9);
   }
   
   .container-list-leave-active {
    position: absolute;
   }
   </style>