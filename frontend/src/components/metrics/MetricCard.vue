<template>
    <div class="bg-white rounded-lg shadow p-4">
      <div class="flex justify-between items-start mb-2">
        <h3 class="text-sm font-medium text-gray-500">{{ title }}</h3>
        <span class="text-xs text-gray-400">{{ subtitle }}</span>
      </div>
      <div class="flex items-baseline">
        <div class="flex-1">
          <div class="flex items-center">
            <span class="text-2xl font-semibold text-gray-900">{{ value }}</span>
            <span class="ml-2 text-sm text-gray-500">{{ unit }}</span>
          </div>
          <slot name="chart"></slot>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  defineProps({
    title: String,
    subtitle: String,
    value: [String, Number],
    unit: String
  })
  </script>