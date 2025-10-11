<template>
    <button
    :class="['clock-button-base', buttonClasses]"
      :disabled="disabled"
      @click="$emit('click')"
    >
      <span class="clock-button-label">
        <span class="clock-button-icon">{{ buttonIcon }}</span>
        {{ label }}
      </span>
      <div class="clock-button-hover-gradient bg-gradient-to-r" :class="hoverGradient"></div>
    </button>
  </template>
  
  <script setup>
  import { computed } from 'vue'
  
  const props = defineProps({
    type: {
      type: String,
      required: true,
      validator: (value) => ['work-start', 'service-start', 'service-end', 'work-end'].includes(value)
    },
    label: {
      type: String,
      required: true
    },
    disabled: {
      type: Boolean,
      default: false
    }
  })
  
  defineEmits(['click'])
  
  const buttonClasses = computed(() => {
    switch (props.type) {
      case 'work-start':
        return 'btn-success'
      case 'service-start':
        return 'btn-primary'
      case 'service-end':
        return 'btn-primary'
      case 'work-end':
        return 'btn-danger'
      default:
        return 'bg-gray-500'
    }
  })
  
  const buttonIcon = computed(() => {
    switch (props.type) {
      case 'work-start':
        return '🌅'
      case 'service-start':
        return '🏥'
      case 'service-end':
        return '✅'
      case 'work-end':
        return '🌙'
      default:
        return '⚡'
    }
  })
  
  const hoverGradient = computed(() => {
    switch (props.type) {
      case 'work-start':
        return 'from-green-400 to-emerald-400'
      case 'service-start':
      case 'service-end':
        return 'from-blue-400 to-purple-400'
      case 'work-end':
        return 'from-red-400 to-pink-400'
      default:
        return 'from-gray-400 to-gray-500'
    }
  })
  </script>