<template>
  <div class="min-h-screen bg-gray-50">
    <Head>
      <Title>{{ post.title }}</Title>
    </Head>
    
    <UContainer>
      <div class="py-8 space-y-8">
        
        <!-- Header -->
        <div class="flex justify-between items-center">
          <div class="flex items-center space-x-4">
            <UButton @click="router.back()" variant="ghost" color="gray" icon="i-heroicons-arrow-left">
              Назад
            </UButton>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">{{ post.title }}</h1>
              <div class="flex items-center space-x-4 text-sm text-gray-500 mt-1">
                <span v-if="post.user">👤 {{ post.user.name }}</span>
                <span v-if="post.category">🏷️ {{ post.category.title }}</span>
                <span v-if="post.published_at">📅 {{ formatDate(post.published_at) }}</span>
              </div>
            </div>
          </div>
          <div class="flex items-center space-x-3">
            <UButton :to="`/posts/edit-${post.id}`" variant="ghost" color="primary" icon="i-heroicons-pencil">
              Редагувати
            </UButton>
            <UButton to="/BlogPostsUi" variant="ghost" color="gray" icon="i-heroicons-list-bullet">
              До списку
            </UButton>
          </div>
        </div>

        <!-- Status Badge -->
        <div v-if="!post.is_published">
          <UAlert color="yellow" variant="soft">
            <template #title>Чернетка</template>
            <template #description>
              Цей пост ще не опубліковано
            </template>
          </UAlert>
        </div>

        <!-- Post Content -->
        <UCard>
          <!-- Excerpt -->
          <div v-if="post.excerpt" class="mb-6 p-4 bg-gray-50 border-l-4 border-primary-500 rounded-r">
            <p class="text-lg text-gray-700 italic">{{ post.excerpt }}</p>
          </div>

          <!-- Main Content -->
          <div class="prose prose-lg max-w-none">
            <div v-html="post.content_html || post.content_raw.replace(/\n/g, '<br>')"></div>
          </div>

          <!-- Footer -->
          <template #footer>
            <div class="flex justify-between items-center text-sm text-gray-500">
              <div class="flex items-center space-x-4">
                <span>ID: {{ post.id }}</span>
                <span v-if="post.updated_at">Оновлено: {{ formatDate(post.updated_at) }}</span>
              </div>
              <div class="flex items-center space-x-2">
                <UBadge v-if="post.is_published" color="green" variant="soft">
                  Опубліковано
                </UBadge>
                <UBadge v-else color="yellow" variant="soft">
                  Чернетка
                </UBadge>
              </div>
            </div>
          </template>
        </UCard>

        <!-- Related Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Author Info -->
          <UCard v-if="post.user">
            <template #header>
              <h3 class="text-lg font-semibold flex items-center gap-2">
                <Icon name="i-heroicons-user" />
                Автор
              </h3>
            </template>
            <div class="space-y-2">
              <div><strong>Ім'я:</strong> {{ post.user.name }}</div>
              <div><strong>Email:</strong> {{ post.user.email }}</div>
            </div>
          </UCard>

          <!-- Category Info -->
          <UCard v-if="post.category">
            <template #header>
              <h3 class="text-lg font-semibold flex items-center gap-2">
                <Icon name="i-heroicons-tag" />
                Категорія
              </h3>
            </template>
            <div class="space-y-2">
              <div><strong>Назва:</strong> {{ post.category.title }}</div>
              <div><strong>Slug:</strong> {{ post.category.slug }}</div>
              <UButton :to="`/categories/${post.category.slug}`" size="sm" variant="ghost" color="primary">
                Переглянути категорію
              </UButton>
            </div>
          </UCard>
        </div>
      </div>
    </UContainer>
  </div>
</template>

<script setup lang="ts">
interface User {
  id: number
  name: string
  email: string
}

interface Category {
  id: number
  title: string
  slug: string
}

interface Post {
  id: number
  title: string
  content_raw: string
  content_html: string
  excerpt: string
  slug: string
  published_at: string
  updated_at: string
  user: User
  category: Category
  is_published: boolean
}

const route = useRoute()
const router = useRouter()
const postId = route.params.id

const { data: postResponse, pending, error } = await useFetch<{ success: boolean, data: Post }>(`http://localhost/api/blog/posts/${postId}`)

if (error.value || !postResponse.value?.data) {
  throw createError({ statusCode: 404, statusMessage: 'Пост не знайдено' })
}

const post = postResponse.value.data

const formatDate = (dateString: string) => {
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