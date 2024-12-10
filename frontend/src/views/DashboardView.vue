<template>
    <MainLayout>
      <div class="w-full">
        <ContainerList @select-container="selectContainer" />
      </div>
    </MainLayout>
   </template>
   
   <script setup>
   import MainLayout from '@/layouts/MainLayout.vue'
   import ContainerList from '@/components/containers/ContainerList.vue'
   import { useRouter } from 'vue-router'
   
   const router = useRouter()
   
   const selectContainer = (container) => {
    router.push(`/container/${container.container_id}`)
   }
   </script>