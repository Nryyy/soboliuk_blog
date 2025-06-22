<template>
  <div>
    <Head>
      <Title>{{ category?.title || 'Завантаження...' }}</Title>
    </Head>
    
    <div class="min-h-screen bg-gray-50">
      <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-6">
          <UButton
            as="NuxtLink"
            to="/categories"
            variant="outline"
            icon="i-heroicons-arrow-left"
            class="mb-4"
          >
            Повернутися до списку
          </UButton>
          <h1 v-if="category" class="text-3xl font-bold text-gray-900">{{ category.title }}</h1>
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
            :description="error.data?.message || 'Категорію не знайдено'"
          />
        </div>
        
        <UCard v-else-if="category" class="max-w-4xl mx-auto">
          <template #header>
            <div class="flex justify-between items-start">
              <div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ category.title }}</h1>
                <div class="text-sm text-gray-600 space-y-1">
                  <div class="flex items-center gap-2">
                    <UIcon name="i-heroicons-link" />
                    <span>Slug: {{ category.slug }}</span>
                  </div>
                  <div v-if="category.parent_id && category.parent_id !== 0" class="flex items-center gap-2">
                    <UIcon name="i-heroicons-folder" />
                    <span>Підкатегорія</span>
                  </div>
                  <div v-if="category.created_at" class="flex items-center gap-2">
                    <UIcon name="i-heroicons-calendar" />
                    <span>Створено: {{ formatDate(category.created_at) }}</span>
                  </div>
                </div>
              </div>
              
              <div class="flex gap-2">
                <UButton
                  :to="`/categories/edit-${category.id}`"
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
            <div v-if="category.description" class="text-lg text-gray-600 mb-6 italic border-l-4 border-green-500 pl-4">
              {{ category.description }}
            </div>
            <div v-else class="text-gray-500 italic">
              Опис категорії не вказано
            </div>
          </div>

          <!-- Posts in this category -->
          <div v-if="categoryPosts && categoryPosts.length > 0" class="mt-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Пости в цій категорії</h3>
            <div class="space-y-3">
              <div 
                v-for="post in categoryPosts" 
                :key="post.id"
                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
              >
                <div>
                  <NuxtLink 
                    :to="`/posts/${post.id}`"
                    class="text-blue-600 hover:text-blue-800 font-medium"
                  >
                    {{ post.title }}
                  </NuxtLink>
                  <div class="text-sm text-gray-500 mt-1">
                    {{ post.published_at ? formatDate(post.published_at) : 'Не опубліковано' }}
                  </div>
                </div>
                <UBadge 
                  :color="post.published_at ? 'green' : 'orange'"
                  variant="soft"
                  size="xs"
                >
                  {{ post.published_at ? 'Опубліковано' : 'Чернетка' }}
                </UBadge>
              </div>
            </div>
          </div>

          <!-- Subcategories if any -->
          <div v-if="subcategories && subcategories.length > 0" class="mt-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Підкатегорії</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div 
                v-for="subcat in subcategories" 
                :key="subcat.id"
                class="p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"
              >
                <NuxtLink 
                  :to="`/categories/${subcat.id}`"
                  class="text-blue-600 hover:text-blue-800 font-medium"
                >
                  {{ subcat.title }}
                </NuxtLink>
                <p v-if="subcat.description" class="text-sm text-gray-600 mt-1">
                  {{ subcat.description }}
                </p>
              </div>
            </div>
          </div>
          
          <template #footer>
            <div class="flex justify-between items-center">
              <div class="text-sm text-gray-500">
                Останнє оновлення: {{ formatDate(category.updated_at) }}
              </div>
              <div class="flex gap-2">
                <UButton
                  as="NuxtLink"
                  to="/categories"
                  variant="outline"
                  icon="i-heroicons-list-bullet"
                >
                  До списку категорій
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
interface Category {
  id: number
  parent_id: number
  slug: string
  title: string
  description: string | null
  created_at: string | null
  updated_at: string | null
  deleted_at: string | null
}

interface Post {
  id: number
  title: string
  published_at: string | null
}

const route = useRoute()
const categoryId = route.params.id

// Fetch category data
const { data: categoryResponse, pending, error } = await useFetch<{success: boolean, data: Category}>(`http://localhost/api/blog/categories/${categoryId}`)

// Fetch posts in this category
const { data: postsResponse } = await useFetch<{success: boolean, data: Post[]}>(`http://localhost/api/blog/categories/${categoryId}/posts`, {
  default: () => ({ success: true, data: [] })
})

// Fetch subcategories
const { data: subcategoriesResponse } = await useFetch<{success: boolean, data: Category[]}>(`http://localhost/api/blog/categories`, {
  key: 'all-categories',
  transform: (data: {success: boolean, data: Category[]}) => {
    if (!data.success || !categoryResponse.value?.data) return { success: true, data: [] }
    
    const subcats = data.data.filter(cat => cat.parent_id === categoryResponse.value.data.id)
    return { success: true, data: subcats }
  },
  default: () => ({ success: true, data: [] })
})

const category = computed(() => categoryResponse.value?.data)
const categoryPosts = computed(() => postsResponse.value?.data || [])
const subcategories = computed(() => subcategoriesResponse.value?.data || [])

const formatDate = (dateString: string | null) => {
  if (!dateString) return 'Невідомо'
  return new Date(dateString).toLocaleDateString('uk-UA', {
    year: 'numeric',
    month: 'long', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>