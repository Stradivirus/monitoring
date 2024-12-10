<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <!-- 전체 컨테이너 상태 -->
      <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-all duration-300">
        <div class="flex flex-col">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-500">Total Containers</span>
            <span class="p-2 bg-blue-50 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
              </svg>
            </span>
          </div>
          <div class="flex items-baseline">
            <div class="text-3xl font-bold text-gray-900">
              <AnimatedNumber
                :value="stats.totalContainers"
                format="0"
              />
            </div>
          </div>
          <div class="mt-4 flex items-center justify-between text-sm">
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-full bg-green-400 mr-2"></div>
              <span class="text-gray-600">
                <AnimatedNumber
                  :value="stats.runningContainers"
                  format="0"
                />
                Running
              </span>
            </div>
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-full bg-red-400 mr-2"></div>
              <span class="text-gray-600">
                <AnimatedNumber
                  :value="stats.stoppedContainers"
                  format="0"
                />
                Stopped
              </span>
            </div>
          </div>
        </div>
      </div>
   
      <!-- 헬스 상태 -->
      <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-all duration-300">
        <div class="flex flex-col">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-500">Health Status</span>
            <span class="p-2 rounded-lg" :class="healthIconBgColor">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" :class="healthIconColor" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
            </span>
          </div>
          <div class="text-3xl font-bold" :class="healthStatusColor">
            {{ healthStatus }}
          </div>
          <div class="mt-4">
            <div class="w-full bg-gray-200 rounded-full h-2.5">
              <div class="h-2.5 rounded-full transition-all duration-500" 
                   :class="healthBarColor"
                   :style="{ width: `${healthPercentage}%` }">
              </div>
            </div>
            <div class="mt-2 text-sm text-gray-600">
              <AnimatedNumber
                :value="healthPercentage"
                format="0"
              />% healthy
            </div>
          </div>
        </div>
      </div>
   
      <!-- CPU 사용량 -->
      <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-all duration-300">
        <div class="flex flex-col">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-500">CPU Usage</span>
            <span class="p-2 bg-blue-50 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5v1.5H4a1 1 0 00-1 1v10a1 1 0 001 1h12a1 1 0 001-1v-10a1 1 0 00-1-1h-1.5V5.5A4.5 4.5 0 0010 1zm3 6V5.5a3 3 0 10-6 0V7h6z" clip-rule="evenodd" />
              </svg>
            </span>
          </div>
          <div class="flex items-baseline">
            <div class="text-3xl font-bold text-blue-600">
              <AnimatedNumber
                :value="stats.totalCpu"
                format="0.0"
              />
            </div>
            <div class="ml-1 text-lg text-blue-600">%</div>
          </div>
          <div class="mt-4">
            <div class="w-full bg-gray-200 rounded-full h-2.5">
              <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-500"
                   :style="{ width: `${Math.min(stats.totalCpu, 100)}%` }">
              </div>
            </div>
            <div class="mt-2 text-sm text-gray-600">
              Across all containers
            </div>
          </div>
        </div>
      </div>
   
      <!-- 메모리 사용량 -->
      <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-all duration-300">
        <div class="flex flex-col">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-500">Memory Usage</span>
            <span class="p-2 bg-purple-50 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-500" viewBox="0 0 20 20" fill="currentColor">
                <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
              </svg>
            </span>
          </div>
          <div class="flex items-baseline">
            <div class="text-3xl font-bold text-purple-600">
              <AnimatedNumber
                :value="memoryInMB"
                format="0.0"
              />
            </div>
            <div class="ml-1 text-lg text-purple-600">MB</div>
          </div>
          <div class="mt-4">
            <div class="w-full bg-gray-200 rounded-full h-2.5">
              <div class="bg-purple-600 h-2.5 rounded-full transition-all duration-500"
                   :style="{ width: `${(memoryInMB / (totalMemoryInMB || 1)) * 100}%` }">
              </div>
            </div>
            <div class="mt-2 text-sm text-gray-600">
              Total allocated memory
            </div>
          </div>
        </div>
      </div>
    </div>
   </template>
   
   <script setup>
   import { computed } from 'vue'
   import AnimatedNumber from '../metrics/AnimatedNumber.vue'
   
   const props = defineProps({
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
   
   const healthStatus = computed(() => {
    if (props.stats.totalContainers === 0) return 'No Containers'
    if (props.stats.runningContainers === props.stats.totalContainers) return 'Healthy'
    if (props.stats.runningContainers === 0) return 'Critical'
    return 'Degraded'
   })
   
   const healthPercentage = computed(() => {
    if (props.stats.totalContainers === 0) return 0
    return Math.round((props.stats.runningContainers / props.stats.totalContainers) * 100)
   })
   
   const healthStatusColor = computed(() => {
    switch (healthStatus.value) {
      case 'Healthy': return 'text-green-600'
      case 'Degraded': return 'text-yellow-600'
      case 'Critical': return 'text-red-600'
      default: return 'text-gray-600'
    }
   })
   
   const healthIconColor = computed(() => {
    switch (healthStatus.value) {
      case 'Healthy': return 'text-green-500'
      case 'Degraded': return 'text-yellow-500'
      case 'Critical': return 'text-red-500'
      default: return 'text-gray-500'
    }
   })
   
   const healthIconBgColor = computed(() => {
    switch (healthStatus.value) {
      case 'Healthy': return 'bg-green-50'
      case 'Degraded': return 'bg-yellow-50'
      case 'Critical': return 'bg-red-50'
      default: return 'bg-gray-50'
    }
   })
   
   const healthBarColor = computed(() => {
    switch (healthStatus.value) {
      case 'Healthy': return 'bg-green-500'
      case 'Degraded': return 'bg-yellow-500'
      case 'Critical': return 'bg-red-500'
      default: return 'bg-gray-500'
    }
   })
   
   const memoryInMB = computed(() => {
    return Math.round(props.stats.totalMemory / (1024 * 1024) * 10) / 10
   })
   
   // 시스템 전체 메모리 (예시: 16GB)
   const totalMemoryInMB = computed(() => {
    return 16 * 1024 // 16GB in MB
   })
   </script>