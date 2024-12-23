<template>
  <div class="w-full">
    <div class="mb-6 flex justify-between items-center bg-white p-4 rounded-lg shadow">
      <h1 class="text-2xl font-bold text-gray-900">Docker Monitoring</h1>
      <div class="flex items-center space-x-4">
        <span v-if="lastUpdated" class="text-sm text-gray-500">
          Last updated: {{ formatDate(lastUpdated) }}
        </span>
        <button 
          @click="toggleAutoRefresh"
          class="px-4 py-2 rounded-md text-white transition-colors duration-200"
          :class="isAutoRefresh ? 'bg-green-500 hover:bg-green-600' : 'bg-blue-500 hover:bg-blue-600'"
        >
          {{ isAutoRefresh ? 'Auto Refresh On' : 'Auto Refresh Off' }}
        </button>
      </div>
    </div>

    <!-- 로딩 상태 -->
    <div v-if="loading" class="flex justify-center items-center h-32">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
    </div>

    <!-- 에러 상태 -->
    <div v-else-if="error" class="bg-red-50 p-4 rounded-lg">
      <div class="flex">
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Error</h3>
          <div class="mt-2 text-sm text-red-700">
            {{ error }}
          </div>
        </div>
      </div>
    </div>

    <!-- 네트워크 그룹별 컨테이너 목록 -->
    <div v-else class="space-y-8">
      <NetworkGroup
        v-for="(group, networkName) in networkGroups"
        :key="networkName"
        :name="networkName"
        :containers="group.containers"
        :stats="group.stats"
        @select-container="selectContainer"
      />

      <!-- 네트워크가 없는 경우 -->
      <div v-if="Object.keys(networkGroups).length === 0" class="text-center py-8">
        <p class="text-gray-500">No containers found</p>
      </div>
    </div>

    <!-- 스크롤 최상단 버튼 -->
    <transition name="fade">
      <button
        v-show="showScrollTop"
        @click="scrollToTop"
        class="fixed bottom-8 right-8 bg-gray-800 text-white p-3 rounded-full shadow-lg hover:bg-gray-700 transition-colors duration-200"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
        </svg>
      </button>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useContainerStore } from '@/stores/containers'
import NetworkGroup from '../networks/NetworkGroup.vue'

const containerStore = useContainerStore()
const { 
  containers, 
  loading, 
  error, 
  networkGroups, 
  isAutoRefresh,
  lastUpdated 
} = storeToRefs(containerStore)

const showScrollTop = ref(false)
const scrollThreshold = 300
const refreshInterval = ref(null)

const debouncedScroll = (() => {
  let timeout
  return () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
      showScrollTop.value = window.scrollY > scrollThreshold
    }, 100)
  }
})()

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleString()
}

function toggleAutoRefresh() {
  containerStore.toggleAutoRefresh()
  if (containerStore.isAutoRefresh) {
    startRefreshInterval()
  } else {
    stopRefreshInterval()
  }
}

function selectContainer(container) {
  containerStore.selectContainer(container)
}

function scrollToTop() {
  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  })
}

function startRefreshInterval() {
  stopRefreshInterval() // 기존 인터벌 제거
  refreshInterval.value = setInterval(() => {
    if (containerStore.isAutoRefresh && !loading.value) {
      containerStore.fetchContainers()
    }
  }, 60000) // 1분 간격으로 변경
}

function stopRefreshInterval() {
  if (refreshInterval.value) {
    clearInterval(refreshInterval.value)
    refreshInterval.value = null
  }
}

onMounted(() => {
  containerStore.initializeStore()
  window.addEventListener('scroll', debouncedScroll)
  if (containerStore.isAutoRefresh) {
    startRefreshInterval()
  }
})

onUnmounted(() => {
  containerStore.clearStore()
  window.removeEventListener('scroll', debouncedScroll)
  stopRefreshInterval()
})
</script>

<style scoped>
.space-y-8 > * + * {
  margin-top: 2rem;
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.transition-colors {
  transition-property: background-color, border-color, color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}
</style>