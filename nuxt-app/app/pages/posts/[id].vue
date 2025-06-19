<template>
  <div>
    <Head>
      <Title>{{ post?.title || 'Завантаження...' }}</Title>
    </Head>
    
    <div class="min-h-screen bg-gray-50">
      <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-6">
          <UButton
            as="NuxtLink"
            to="/BlogPostsUi"
            variant="outline"
            icon="i-heroicons-arrow-left"
            class="mb-4"
          >
            Повернутися до списку
          </UButton>
          <h1 v-if="post" class="text-3xl font-bold text-gray-900">{{ post.title }}</h1>
        </div>
      </header>
      
      <main class="container mx-auto px-4 py-8">
        <div v-if="pending" class="flex justify-center">
          <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 animate-spin" />
          <span class="ml-2 text-lg">Завантаження...</span>
        </div>
        
        <div v-else-if="error" class="text-center">
          <UAlert
            icon="i-heroicons-exclamation-triangle"
            color="warning"
            variant="solid"
            title="Помилка"
            :description="error.data?.message || 'Пост не знайдено'"
          />
        </div>
        
        <UCard v-else-if="post" class="max-w-4xl mx-auto">
          <template #header>
            <div class="flex justify-between items-start">
              <div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ post.title }}</h1>
                <div class="text-sm text-gray-600 space-y-1">
                  <div v-if="post.user" class="flex items-center gap-2">
                    <UIcon name="i-heroicons-user" />
                    <span>Автор: {{ post.user.name }}</span>
                  </div>
                  <div v-if="post.category" class="flex items-center gap-2">
                    <UIcon name="i-heroicons-tag" />
                    <span>Категорія: {{ post.category.title }}</span>
                  </div>
                  <div v-if="post.published_at" class="flex items-center gap-2">
                    <UIcon name="i-heroicons-calendar" />
                    <span>Опубліковано: {{ formatDate(post.published_at) }}</span>
                  </div>
                </div>
              </div>
              
              <div class="flex gap-2">
                <UButton
                  as="a"
                  :href="`/admin/blog/posts/${post.id}/edit`"
                  color="primary"
                  variant="outline"
                  icon="i-heroicons-pencil"
                  size="sm"
                >
                  Редагувати
                </UButton>
              </div>
            </div>
          </template>
          
          <div class="prose prose-lg max-w-none">
            <div v-if="post.excerpt" class="text-lg text-gray-600 mb-6 italic border-l-4 border-blue-500 pl-4">
              {{ post.excerpt }}
            </div>
            <div class="whitespace-pre-line text-gray-800 leading-relaxed">
              {{ post.content_raw || post.content_html }}
            </div>
          </div>
          
          <template #footer>
            <div class="flex justify-between items-center">
              <div class="text-sm text-gray-500">
                Останнє оновлення: {{ formatDate(post.updated_at) }}
              </div>
              <div class="flex gap-2">
                <UButton
                  as="NuxtLink"
                  to="/BlogPostsUi"
                  variant="outline"
                  icon="i-heroicons-list-bullet"
                >
                  До списку постів
                </UButton>
              </div>
            </div>
          </template>
        </UCard>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
interface User {
  id: number;
  name: string;
}

interface Category {
  id: number;
  title: string;
}

interface Post {
  id: number;
  title: string;
  content_raw?: string;
  content_html?: string;
  excerpt?: string;
  published_at: string;
  updated_at: string;
  user: User;
  category: Category;
}

const route = useRoute()
const postId = route.params.id

const { data: postResponse, pending, error } = await useFetch<{success: boolean, data: Post}>(`http://localhost/api/blog/posts/${postId}`)

const post = computed(() => postResponse.value?.data)

const formatDate = (dateString: string) => {
  if (!dateString) return 'Невідомо';
  return new Date(dateString).toLocaleDateString('uk-UA', {
    year: 'numeric',
    month: 'long', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Додаємо мета-теги для SEO
useSeoMeta({
  title: () => post.value?.title || 'Пост',
  ogTitle: () => post.value?.title,
  description: () => post.value?.excerpt || post.value?.content_raw?.substring(0, 160) || 'Читайте пост у нашому блозі',
  ogDescription: () => post.value?.excerpt || post.value?.content_raw?.substring(0, 160)
})
</script>