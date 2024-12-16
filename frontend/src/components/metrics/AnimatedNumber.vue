<template>
  <span>{{ displayValue }}{{ unit }}</span>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import gsap from 'gsap'

const props = defineProps({
  value: {
    type: Number,
    required: true
  },
  format: {
    type: String,
    default: '0.00'
  },
  unit: {
    type: String,
    default: ''
  },
  duration: {
    type: Number,
    default: 0.5
  }
})

const currentValue = ref(0)
const displayValue = computed(() => formatValue(currentValue.value))

const formatValue = (value) => {
  switch(props.format) {
    case '0':
      return Math.round(value)
    case '0.0':
      return Number(value.toFixed(1))
    case '0.00':
      return Number(value.toFixed(2))
    case '0.000':
      return Number(value.toFixed(3))
    default:
      return Number(value.toFixed(2))
  }
}

const animateValue = (newValue) => {
  if (newValue === currentValue.value) return

  gsap.to(currentValue, {
    duration: props.duration,
    value: newValue,
    ease: 'power2.out',
    onUpdate: () => {
      currentValue.value = Number(currentValue.value)
    }
  })
}

watch(() => props.value, (newValue) => {
  if (newValue !== undefined && newValue !== null) {
    animateValue(newValue)
  }
}, { flush: 'post' })

onMounted(() => {
  currentValue.value = props.value
})
</script>