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
    lastUpdated: null,
    reconnectAttempts: 0,
    maxReconnectAttempts: 5
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
        this.reconnectAttempts = 0
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
            
            // 에러 체크
            if (data.error) {
              console.error('Server reported error:', data.message)
              this.error = data.message
              return
            }

            this.updateContainerData(data)
            this.lastUpdated = new Date()
            this.error = null
            this.reconnectAttempts = 0
          } catch (error) {
            console.error('Error processing SSE data:', error)
            this.error = 'Error processing server data'
          }
        }

        this.eventSource.onopen = () => {
          console.log('EventSource connected')
          this.error = null
          this.reconnectAttempts = 0
        }

        this.eventSource.onerror = (error) => {
          console.error('EventSource failed:', error)
          this.eventSource.close()
          this.eventSource = null
          this.error = 'Connection lost. Retrying...'
          
          // 재연결 시도
          if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++
            console.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`)
            setTimeout(() => this.startEventStream(), 60000) // 1분 후 재시도
          } else {
            this.error = 'Connection lost. Please refresh the page.'
            this.isAutoRefresh = false
          }
        }
      }
    },

    updateContainerData(newData) {
      const index = this.containers.findIndex(c => c.container_id === newData.container_id)
      
      if (index !== -1) {
        // 기존 컨테이너 업데이트
        const container = this.containers[index]
        
        // metrics 객체 업데이트
        container.metrics = {
          cpu: { ...newData.metrics.cpu },
          memory: { ...newData.metrics.memory },
          network: { ...newData.metrics.network },
          disk: { ...newData.metrics.disk }
        }
        
        // 상태 변경이 있는 경우에만 업데이트
        if (newData.status !== container.status) {
          container.status = newData.status
        }

        // 기타 필드 업데이트
        container.name = newData.name
        container.image = newData.image
        container.labels = newData.labels
      } else {
        // 새 컨테이너 추가
        this.containers.push(newData)
      }
    },

    stopEventStream() {
      console.log('Stopping EventSource connection...')
      if (this.eventSource) {
        this.isAutoRefresh = false
        this.eventSource.close()
        this.eventSource = null
        this.reconnectAttempts = 0
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
      this.reconnectAttempts = 0
    }
  }
})