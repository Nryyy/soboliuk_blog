<template>
  <div>
    <Head>
      <Title>Блог пости</Title>
    </Head>
    
    <div class="min-h-screen bg-gray-50">
      <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-6">
          <h1 class="text-3xl font-bold text-gray-900">Блог пости</h1>
        </div>
      </header>
      
      <main class="container mx-auto px-4 py-8">
        <PostsTableComponent />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
// Компонент автоматично імпортується завдяки Nuxt
</script>