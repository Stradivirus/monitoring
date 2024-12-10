import { defineStore } from 'pinia'
import api from '../services/api'

export const useContainerStore = defineStore('containers', {
  state: () => ({
    containers: [],
    selectedContainer: null,
    loading: false,
    error: null,
    refreshInterval: null,
    isAutoRefresh: true,
    lastUpdated: null
  }),

  getters: {
    // 네트워크별로 컨테이너 그룹화
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

    // 전체 시스템 상태
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

    startAutoRefresh() {
      if (!this.refreshInterval) {
        this.isAutoRefresh = true
        this.refreshInterval = setInterval(() => {
          this.fetchContainers()
        }, 10000) // 10초마다 갱신
      }
    },

    stopAutoRefresh() {
      if (this.refreshInterval) {
        this.isAutoRefresh = false
        clearInterval(this.refreshInterval)
        this.refreshInterval = null
      }
    },

    toggleAutoRefresh() {
      if (this.isAutoRefresh) {
        this.stopAutoRefresh()
      } else {
        this.startAutoRefresh()
      }
    },

    selectContainer(container) {
      this.selectedContainer = container
    },

    // 컴포넌트 마운트 시 자동으로 시작
    initializeStore() {
      this.fetchContainers()
      this.startAutoRefresh()
    },

    // 컴포넌트 언마운트 시 정리
    clearStore() {
      this.stopAutoRefresh()
      this.containers = []
      this.selectedContainer = null
      this.error = null
    }
  }
})