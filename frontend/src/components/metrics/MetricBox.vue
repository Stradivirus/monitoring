<template>
  <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
    <div class="flex items-center justify-between mb-2">
      <span class="text-sm font-medium text-gray-500">{{ title }}</span>
      <span :class="`p-1.5 bg-${color}-50 rounded-lg`">
        <svg xmlns="http://www.w3.org/2000/svg" :class="`h-4 w-4 text-${color}-500`" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
      </span>
    </div>
    <div class="flex items-baseline">
      <AnimatedNumber
        :value="value"
        format="0.00"
        class="text-2xl font-bold text-gray-900"
      />
      <span class="ml-1 text-sm text-gray-500">{{ unit }}</span>
    </div>
    <div class="mt-3" v-if="percentage !== undefined">
      <div class="w-full bg-gray-200 rounded-full h-1.5">
        <div :class="`bg-${color}-600 h-1.5 rounded-full transition-all duration-500`"
             :style="{ width: `${Math.min(percentage, 100)}%` }">
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import AnimatedNumber from './AnimatedNumber.vue'

defineProps({
  title: String,
  value: Number,
  unit: String,
  color: {
    type: String,
    default: 'blue'
  },
  percentage: Number
})
</script>