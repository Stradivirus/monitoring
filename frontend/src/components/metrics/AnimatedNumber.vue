<template>
    <span>{{ animatedValue }}{{ unit }}</span>
  </template>
  
  <script setup>
  import { ref, watch, onMounted } from 'vue'
  import gsap from 'gsap'
  
  const props = defineProps({
    value: {
      type: Number,
      required: true
    },
    format: {
      type: String,
      default: '0.00' // 기본값을 2자리 소수점으로 설정
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
  
  const animatedValue = ref(0)
  
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
        return Number(value.toFixed(2)) // 기본값 2자리 소수점
    }
  }
  
  const animateValue = (newValue) => {
    gsap.to(animatedValue, {
      duration: props.duration,
      value: newValue,
      onUpdate: () => {
        animatedValue.value = formatValue(animatedValue.value)
      }
    })
  }
  
  watch(() => props.value, (newValue) => {
    if (newValue !== undefined && newValue !== null) {
      animateValue(newValue)
    }
  }, { immediate: true })
  
  onMounted(() => {
    animatedValue.value = formatValue(props.value)
  })
  </script>