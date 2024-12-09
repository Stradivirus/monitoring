<template>
    <Line
      :data="chartData"
      :options="chartOptions"
    />
  </template>
  
  <script setup>
  import { ref, watch } from 'vue'
  import { Line } from 'vue-chartjs'
  import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend } from 'chart.js'
  
  ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend)
  
  const props = defineProps({
    metrics: {
      type: Array,
      required: true
    }
  })
  
  const chartData = ref({
    labels: [],
    datasets: [{
      label: 'CPU Usage %',
      data: [],
      borderColor: 'rgb(75, 192, 192)',
      tension: 0.1
    }]
  })
  
  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: {
        beginAtZero: true,
        max: 100
      }
    }
  }
  
  watch(() => props.metrics, (newMetrics) => {
    chartData.value.labels = newMetrics.map(m => new Date(m.timestamp.$date).toLocaleTimeString())
    chartData.value.datasets[0].data = newMetrics.map(m => m.metrics.cpu.percentage)
  }, { deep: true })
  </script>