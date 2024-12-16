<template>
  <div 
    class="bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden cursor-pointer"
    :class="{
      'border-l-4 border-green-500': isRunning,
      'border-l-4 border-red-500': !isRunning
    }"
  >
    <!-- 헤더 섹션 -->
    <div class="p-5 border-b border-gray-100">
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
        <MetricBox
          title="CPU"
          :value="cpuPercentage"
          unit="%"
          :color="'blue'"
          :icon="'cpu'"
        />

        <!-- 메모리 사용량 -->
        <MetricBox
          title="Memory"
          :value="memoryInMB"
          unit="MB"
          :color="'purple'"
          :icon="'memory'"
          :percentage="memoryPercentage"
        />
      </div>

      <!-- 네트워크 & 디스크 I/O -->
      <div class="mt-4 grid grid-cols-2 gap-4">
        <NetworkMetrics
          :rx-bytes="networkRx"
          :tx-bytes="networkTx"
        />
        <DiskMetrics
          :read-bytes="diskRead"
          :write-bytes="diskWrite"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import MetricBox from '../metrics/MetricBox.vue'
import NetworkMetrics from '../metrics/NetworkMetrics.vue'
import DiskMetrics from '../metrics/DiskMetrics.vue'

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

const memoryPercentage = computed(() => {
  return props.container.metrics?.memory?.percentage || 0
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
</script>

<style scoped>
.truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>