import { defineStore } from 'pinia'
import api from '../services/api'

export const useContainerStore = defineStore('containers', {
  state: () => ({
    containers: [],
    selectedContainer: null,
    loading: false,
    error: null
  }),
  
  actions: {
    async fetchContainers() {
      this.loading = true
      try {
        const response = await api.getContainers()
        this.containers = response.data
      } catch (error) {
        this.error = error.message
      } finally {
        this.loading = false
      }
    }
  }
})