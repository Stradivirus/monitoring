<template>
    <MainLayout>
      <div v-if="loading" class="text-center py-8">
        <p class="text-gray-500">Loading container details...</p>
      </div>
      
      <div v-else class="container mx-auto">
        <div class="mb-6">
          <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">
              {{ containerDetails?.name }}
              <ContainerStatus :status="containerDetails?.status" class="ml-2" />
            </h1>
            <button 
              @click="refreshMetrics"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
            >
              Refresh
            </button>
          </div>
          <p class="text-gray-600">{{ containerDetails?.image }}</p>
        </div>
  
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <MetricCard
            title="CPU Usage"
            :value="currentCPU"
            unit="%"
          >
            <template #chart>
              <div class="h-48">
                <CPUChart :metrics="metrics" />
              </div>
            </template>
          </MetricCard>
  
          <MetricCard
            title="Memory Usage"
            :value="formatMemory(currentMemory)"
            :subtitle="`Total: ${formatMemory(totalMemory)}`"
          >
            <template #chart>
              <div class="h-48">
                <MemoryChart :metrics="metrics" />
              </div>
            </template>
          </MetricCard>
        </div>
      </div>
    </MainLayout>
  </template>
  
  <script setup>
  import { ref, computed, onMounted, onUnmounted } from 'vue'
  import { useRoute } from 'vue-router'
  import MainLayout from '@/layouts/MainLayout.vue'
  import MetricCard from '@/components/metrics/MetricCard.vue'
  import CPUChart from '@/components/charts/CPUChart.vue'
  import ContainerStatus from '@/components/containers/ContainerStatus.vue'
  import api from '@/services/api'
  
  const route = useRoute()
  const loading = ref(true)
  const containerDetails = ref(null)
  const metrics = ref([])
  const refreshInterval = ref(null)
  
  const currentCPU = computed(() => {
    if (!metrics.value.length) return '0'
    return metrics.value[0].metrics.cpu.percentage.toFixed(1)
  })
  
  const currentMemory = computed(() => {
    if (!metrics.value.length) return 0
    return metrics.value[0].metrics.memory.used
  })
  
  const totalMemory = computed(() => {
    if (!metrics.value.length) return 0
    return metrics.value[0].metrics.memory.total
  })
  
  function formatMemory(bytes) {
    if (!bytes) return '0 MB'
    const mb = bytes / (1024 * 1024)
    return `${mb.toFixed(1)} MB`
  }
  
  async function refreshMetrics() {
    try {
      const response = await api.getContainerMetrics(route.params.id)
      metrics.value = response.data.reverse()
      if (!containerDetails.value && metrics.value.length) {
        containerDetails.value = {
          name: metrics.value[0].name,
          image: metrics.value[0].image,
          status: metrics.value[0].status
        }
      }
    } catch (error) {
      console.error('Failed to fetch metrics:', error)
    } finally {
      loading.value = false
    }
  }
  
  onMounted(() => {
    refreshMetrics()
    refreshInterval.value = setInterval(refreshMetrics, 10000) // 10초마다 갱신
  })
  
  onUnmounted(() => {
    if (refreshInterval.value) {
      clearInterval(refreshInterval.value)
    }
  })
  </script>