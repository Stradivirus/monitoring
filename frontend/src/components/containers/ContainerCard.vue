<template>
  <div 
    class="bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden cursor-pointer"
    :class="{
      'border-l-4 border-green-500': isRunning,
      'border-l-4 border-red-500': !isRunning
    }"
  >
    <!-- 헤더 섹션 - 정적 내용은 v-once 사용 -->
    <div class="p-5 border-b border-gray-100" v-once>
      <div class="flex justify-between items-start">
        <div class="flex-1 min-w-0">
          <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-2" viewBox="0 0 20 20" fill="currentColor">
              <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 truncate" :title="container.name">
              {{ container.name }}
            </h3>
          </div>
          <p class="mt-1 text-sm text-gray-500 truncate" :title="container.image">
            {{ container.image }}
          </p>
        </div>
      </div>
    </div>

    <!-- 메트릭 섹션 -->
    <div class="p-5">
      <div class="grid grid-cols-2 gap-4">
        <!-- CPU 사용량 -->
        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-500">CPU</span>
            <span class="p-1.5 bg-blue-50 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
              </svg>
            </span>
          </div>
          <div class="flex items-baseline">
            <AnimatedNumber
              :value="cpuPercentage"
              format="0.00"
              class="text-2xl font-bold text-gray-900"
            />
            <span class="ml-1 text-sm text-gray-500">%</span>
          </div>
          <div class="mt-3">
            <div class="w-full bg-gray-200 rounded-full h-1.5">
              <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-500"
                   :style="{ width: `${Math.min(cpuPercentage, 100)}%` }">
              </div>
            </div>
          </div>
        </div>

        <!-- 메모리 사용량 -->
        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-500">Memory</span>
            <span class="p-1.5 bg-purple-50 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-500" viewBox="0 0 20 20" fill="currentColor">
                <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
              </svg>
            </span>
          </div>
          <div class="flex items-baseline">
            <AnimatedNumber
              :value="memoryInMB"
              format="0.00"
              class="text-2xl font-bold text-gray-900"
            />
            <span class="ml-1 text-sm text-gray-500">MB</span>
          </div>
          <div class="mt-3">
            <div class="w-full bg-gray-200 rounded-full h-1.5">
              <div class="bg-purple-600 h-1.5 rounded-full transition-all duration-500"
                   :style="{ width: `${Math.min((memoryInMB / (maxMemoryMB || 1) * 100), 100)}%` }">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 네트워크 & 디스크 I/O -->
      <div class="mt-4 grid grid-cols-2 gap-4">
        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-500">Network I/O</span>
            <span class="p-1.5 bg-green-50 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027c.33-.314.158-.888-.283-.95l-1.09-.17c-.478-.075-.91.315-.836.793l.17 1.09c.062.44.636.613.95.283L4.332 8.027zm3.442-3.442c-.314-.33-.888-.158-.95.283l-.17 1.09c-.075.478.315.91.793.836l1.09-.17c.44-.062.613-.636.283-.95l-1.046-1.089zm7.557 7.557c.314.33.158.888-.283.95l-1.09.17c-.478.075-.91-.315-.836-.793l.17-1.09c.062-.44.636-.613.95-.283l1.089 1.046zm-3.442 3.442c.33.314.888.158.95-.283l.17-1.09c.075-.478-.315-.91-.793-.836l-1.09.17c-.44.062-.613.636-.283.95l1.046 1.089z" clip-rule="evenodd" />
              </svg>
            </span>
          </div>
          <div class="text-xs text-gray-500">
            <div class="flex justify-between items-center">
              <span>RX:</span>
              <span class="font-medium">{{ formatBytes(networkRx) }}</span>
            </div>
            <div class="flex justify-between items-center mt-1">
              <span>TX:</span>
              <span class="font-medium">{{ formatBytes(networkTx) }}</span>
            </div>
          </div>
        </div>

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
              <span class="font-medium">{{ formatBytes(diskRead) }}</span>
            </div>
            <div class="flex justify-between items-center mt-1">
              <span>Write:</span>
              <span class="font-medium">{{ formatBytes(diskWrite) }}</span>
            </div>
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
  container: {
    type: Object,
    required: true
  }
})

const isRunning = computed(() => {
  return props.container.status.toLowerCase() === 'running'
})

const cpuPercentage = computed(() => {
  return props.container.metrics?.cpu?.percentage || 0
})

const memoryInMB = computed(() => {
  const bytes = props.container.metrics?.memory?.used || 0
  return Number((bytes / (1024 * 1024)).toFixed(2))
})

const maxMemoryMB = computed(() => {
  const bytes = props.container.metrics?.memory?.total || 0
  return bytes / (1024 * 1024)
})

const networkRx = computed(() => {
  return props.container.metrics?.network?.total?.rx_bytes || 0
})

const networkTx = computed(() => {
  return props.container.metrics?.network?.total?.tx_bytes || 0
})

const diskRead = computed(() => {
  return props.container.metrics?.disk?.read_bytes || 0
})

const diskWrite = computed(() => {
  return props.container.metrics?.disk?.write_bytes || 0
})

function formatBytes(bytes) {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return `${(bytes / Math.pow(k, i)).toFixed(2)} ${sizes[i]}`
}
</script>

<style scoped>
.truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>