<template>
    <button
      :class="[
        'min-h-[80px] min-w-full px-8 py-6 rounded-2xl font-bold text-white text-xl transition-all duration-300 transform active:scale-95 shadow-2xl relative overflow-hidden',
        buttonClasses,
        disabled ? 'opacity-50 cursor-not-allowed' : 'hover:shadow-2xl hover:-translate-y-1'
      ]"
      :disabled="disabled"
      @click="$emit('click')"
    >
      <span class="relative z-10 flex items-center justify-center">
        <span class="mr-3 text-2xl">{{ buttonIcon }}</span>
        {{ label }}
      </span>
      <div class="absolute inset-0 bg-gradient-to-r opacity-0 hover:opacity-20 transition-opacity duration-300" :class="hoverGradient"></div>
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