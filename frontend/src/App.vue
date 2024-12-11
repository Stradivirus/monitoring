<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Main Content -->
    <main>
      <!-- Ensure Full Width -->
      <div class="w-full py-6 px-4">
        <!-- Remove Constraining Widths -->
        <div class="w-full sm:px-0">
          <RouterView />
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { RouterView } from 'vue-router';
</script>

<style>
@import 'tailwindcss/base';
@import 'tailwindcss/components';
@import 'tailwindcss/utilities';

body {
  margin: 0;
  min-height: 100vh;
  background-color: #f3f4f6;
}

#app {
  width: 100%;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>
