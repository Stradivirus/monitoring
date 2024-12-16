import { defineStore } from 'pinia'
import api from '../services/api'

export const useContainerStore = defineStore('containers', {
  state: () => ({
    containers: [],
    selectedContainer: null,
    loading: false,
    error: null,
    eventSource: null,
    isAutoRefresh: true,
    lastUpdated: null
  }),

  getters: {
    networkGroups: (state) => {
      const groups = {}
      
      state.containers.forEach(container => {
        const networkName = container.labels?.['com.docker.compose.project'] || 'default'
        if (!groups[networkName]) {
          groups[networkName] = {
            containers: [],
            stats: {
              totalCpu: 0,
              totalMemory: 0,
              totalContainers: 0,
              runningContainers: 0,
              stoppedContainers: 0
            }
          }
        }
        
        groups[networkName].containers.push(container)
        groups[networkName].stats.totalCpu += container.metrics?.cpu?.percentage || 0
        groups[networkName].stats.totalMemory += container.metrics?.memory?.used || 0
        groups[networkName].stats.totalContainers++
        
        if (container.status.toLowerCase() === 'running') {
          groups[networkName].stats.runningContainers++
        } else {
          groups[networkName].stats.stoppedContainers++
        }
      })
      
      return groups
    },

    systemStats: (state) => {
      return {
        totalContainers: state.containers.length,
        runningContainers: state.containers.filter(c => c.status.toLowerCase() === 'running').length,
        stoppedContainers: state.containers.filter(c => c.status.toLowerCase() !== 'running').length,
        totalCpu: state.containers.reduce((sum, c) => sum + (c.metrics?.cpu?.percentage || 0), 0),
        totalMemory: state.containers.reduce((sum, c) => sum + (c.metrics?.memory?.used || 0), 0)
      }
    }
  },

  actions: {
    async fetchContainers() {
      this.loading = true
      try {
        const response = await api.getContainers()
        this.containers = response.data
        this.lastUpdated = new Date()
        this.error = null
      } catch (error) {
        console.error('Error fetching containers:', error)
        this.error = error.message
      } finally {
        this.loading = false
      }
    },

    startEventStream() {
      if (!this.eventSource) {
        console.log('Starting EventSource connection...')
        this.isAutoRefresh = true
        this.eventSource = new EventSource(`${import.meta.env.VITE_API_URL}/api/stream/metrics`)
        
        this.eventSource.onmessage = (event) => {
          console.log('Received SSE data:', event.data)
          try {
            const data = JSON.parse(event.data)
            this.updateContainerMetrics(data)
            this.lastUpdated = new Date()
          } catch (error) {
            console.error('Error processing SSE data:', error)
          }
        }

        this.eventSource.onopen = () => {
          console.log('EventSource connected')
          this.error = null
        }

        this.eventSource.onerror = (error) => {
          console.error('EventSource failed:', error)
          this.eventSource.close()
          this.eventSource = null
          this.error = 'Connection lost. Retrying...'
          
          setTimeout(() => {
            console.log('Attempting to reconnect...')
            this.startEventStream()
          }, 3000)
        }
      }
    },

    updateContainerMetrics(newData) {
      const index = this.containers.findIndex(c => c.container_id === newData.container_id)
      if (index !== -1) {
        // 메트릭 데이터만 업데이트
        const container = this.containers[index]
        container.metrics = { ...newData.metrics }
        if (newData.status !== container.status) {
          container.status = newData.status
        }
      }
    },

    stopEventStream() {
      console.log('Stopping EventSource connection...')
      if (this.eventSource) {
        this.isAutoRefresh = false
        this.eventSource.close()
        this.eventSource = null
      }
    },

    toggleAutoRefresh() {
      if (this.isAutoRefresh) {
        this.stopEventStream()
      } else {
        this.startEventStream()
      }
    },

    selectContainer(container) {
      this.selectedContainer = container
    },

    initializeStore() {
      console.log('Initializing container store...')
      this.fetchContainers()
      this.startEventStream()
    },

    clearStore() {
      this.stopEventStream()
      this.containers = []
      this.selectedContainer = null
      this.error = null
    }
  }
})