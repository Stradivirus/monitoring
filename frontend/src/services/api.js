import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  timeout: 5000,
  headers: {
    'Content-Type': 'application/json'
  }
})

export default {
  // 컨테이너 목록 조회
  getContainers() {
    return api.get('/api/containers')
  },
  // 특정 컨테이너의 메트릭 조회
  getContainerMetrics(containerId) {
    return api.get(`/api/containers/${containerId}/metrics`)
  }
}