<script setup lang="ts">
import { z } from 'zod'

interface User {
  id: number
  name: string
  email: string
}

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
  excerpt: string
  content_raw: string
  content_html: string
  slug: string
  category_id: number
  user_id: number
  is_published: boolean
  published_at: string | null
  created_at: string
  updated_at: string
  user: User
  category: Category
}

const route = useRoute()
const router = useRouter()
const toast = useToast()
const postId = route.params.id

const postSchema = z.object({
  title: z.string().min(3, 'Заголовок повинен містити мінімум 3 символи').max(255),
  excerpt: z.string().max(500).optional(),
  content_raw: z.string().min(10, 'Контент повинен містити мінімум 10 символів'),
  slug: z.string().min(1).regex(/^[a-z0-9_-]+$/, 'URL може містити тільки малі літери, цифри, дефіси та підкреслення').max(255),
  category_id: z.number().min(1, 'Оберіть категорію'),
  user_id: z.number().min(1, 'Оберіть автора'),
  is_published: z.boolean(),
  published_at: z.string().optional().refine((val) => !val || !isNaN(Date.parse(val)), 'Невірний формат дати')
})

// Fetch data
const { data: postResponse, error: postError } = await useFetch<{ success: boolean, data: Post }>(`http://localhost/api/blog/posts/${postId}`)
const { data: categoriesResponse } = await useFetch<{ success: boolean, data: Category[] }>('http://localhost/api/blog/categories')
const { data: usersResponse } = await useFetch<{ success: boolean, data: User[] }>('http://localhost/api/users', { 
  default: () => ({ success: true, data: [] }) 
})

if (postError.value || !postResponse.value?.data) {
  throw createError({ statusCode: 404, statusMessage: 'Пост не знайдено' })
}

const post = postResponse.value.data
const categories = computed(() => categoriesResponse.value?.data || [])
const users = computed(() => usersResponse.value?.data || [])

const isLoading = ref(false)
const isDeleting = ref(false)
const errors = ref<Record<string, string>>({})

const form = reactive({
  title: post.title,
  excerpt: post.excerpt || '',
  content_raw: post.content_raw,
  content_html: post.content_html,
  slug: post.slug,
  category_id: post.category_id,
  user_id: post.user_id,
  is_published: post.is_published,
  published_at: post.published_at ? post.published_at.substring(0, 16) : ''
})

const categoryOptions = computed(() => 
  categories.value.map(cat => ({
    label: cat.parent_id && cat.parent_id !== 0 ? `  ↳ ${cat.title}` : cat.title,
    value: cat.id
  }))
)

const userOptions = computed(() => 
  users.value.map(user => ({ label: user.name, value: user.id }))
)

const generateSlug = (title: string) => {
  return title
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9а-яё\s_-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '')
}

watch(() => form.title, (newTitle) => {
  if (newTitle && newTitle !== post.title) {
    form.slug = generateSlug(newTitle)
    delete errors.value.slug
  }
})

watch(() => form.content_raw, (newContent) => {
  form.content_html = newContent
    .replace(/\n\n/g, '</p><p>')
    .replace(/\n/g, '<br>')
    .replace(/^/, '<p>')
    .replace(/$/, '</p>')
})

const validateField = (fieldName: keyof typeof form) => {
  try {
    const fieldSchema = postSchema.shape[fieldName as keyof typeof postSchema.shape]
    if (fieldSchema) {
      fieldSchema.parse(form[fieldName])
      delete errors.value[fieldName]
    }
  } catch (error) {
    if (error instanceof z.ZodError && error.errors[0]) {
      errors.value[fieldName] = error.errors[0].message
    }
  }
}

const validateForm = () => {
  try {
    postSchema.parse({
      title: form.title,
      excerpt: form.excerpt || undefined,
      content_raw: form.content_raw,
      slug: form.slug,
      category_id: form.category_id,
      user_id: form.user_id,
      is_published: form.is_published,
      published_at: form.published_at || undefined
    })
    errors.value = {}
    return true
  } catch (error) {
    if (error instanceof z.ZodError) {
      errors.value = {}
      error.errors.forEach((err) => {
        if (err.path[0]) errors.value[err.path[0] as string] = err.message
      })
    }
    return false
  }
}

const handleSubmit = async () => {
  if (!validateForm()) {
    toast.add({ title: 'Помилка валідації', description: 'Виправте помилки у формі', color: 'red' })
    return
  }

  isLoading.value = true
  try {
    const response = await $fetch<{ success: boolean, message: string, data: Post }>(`http://localhost/api/blog/posts/${postId}`, {
      method: 'PUT',
      body: { ...form, published_at: form.is_published && form.published_at ? form.published_at : null }
    })
    
    if (response.success) {
      toast.add({ title: 'Успіх!', description: 'Пост оновлено', color: 'green' })
      await router.push(`/posts/${response.data.slug}`)
    }
  } catch (error: any) {
    toast.add({ title: 'Помилка', description: error.data?.message || 'Не вдалося оновити пост', color: 'red' })
  } finally {
    isLoading.value = false
  }
}

const handleDelete = async () => {
  if (!confirm(`Видалити пост "${post.title}"? Ця дія незворотна!`)) return
  
  isDeleting.value = true
  try {
    const response = await $fetch<{ success: boolean, message: string }>(`http://localhost/api/blog/posts/${postId}`, { method: 'DELETE' })
    if (response.success) {
      toast.add({ title: 'Успіх!', description: 'Пост видалено', color: 'green' })
      await router.push('/BlogPostsUi')
    }
  } catch (error: any) {
    toast.add({ title: 'Помилка', description: error.data?.message || 'Не вдалося видалити пост', color: 'red' })
  } finally {
    isDeleting.value = false
  }
}

useSeoMeta({
  title: `Редагування: ${post.title}`,
  description: `Редагування поста "${post.title}"`
})
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <Head>
      <Title>Редагування: {{ post.title }}</Title>
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
              <h1 class="text-xl font-semibold text-gray-900 truncate">{{ post.title }}</h1>
              <p class="text-sm text-gray-500">ID: {{ postId }}</p>
            </div>
          </div>
          <div class="flex items-center space-x-3">
            <UButton :to="`/posts/${post.id}`" target="_blank" variant="ghost" color="primary" icon="i-heroicons-eye">
              Переглянути
            </UButton>
            <UButton @click="handleDelete" :loading="isDeleting" :disabled="isLoading" color="red" variant="ghost" icon="i-heroicons-trash">
              Видалити
            </UButton>
          </div>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-8">
          
          <!-- Basic Info -->
          <UCard>
            <template #header>
              <h2 class="text-lg font-semibold">Основна інформація</h2>
            </template>
            
            <div class="space-y-6">
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Title -->
                <UFormGroup label="Заголовок" required :error="errors.title">
                  <UInput
                    v-model="form.title"
                    placeholder="Введіть заголовок"
                    :disabled="isLoading"
                    @blur="validateField('title')"
                  />
                </UFormGroup>

                <!-- Slug -->
                <UFormGroup label="URL адреса" required :error="errors.slug">
                  <UInput
                    v-model="form.slug"
                    placeholder="url-slug"
                    :disabled="isLoading"
                    @blur="validateField('slug')"
                  />
                </UFormGroup>

                <!-- Category -->
                <UFormGroup label="Категорія" required :error="errors.category_id">
                  <USelect
                    v-model="form.category_id"
                    :options="categoryOptions"
                    placeholder="Оберіть категорію"
                    :disabled="isLoading"
                    @change="validateField('category_id')"
                  />
                </UFormGroup>

                <!-- Author -->
                <UFormGroup v-if="users.length > 0" label="Автор" required :error="errors.user_id">
                  <USelect
                    v-model="form.user_id"
                    :options="userOptions"
                    placeholder="Оберіть автора"
                    :disabled="isLoading"
                    @change="validateField('user_id')"
                  />
                </UFormGroup>
              </div>

              <!-- Excerpt -->
              <UFormGroup label="Короткий опис" :error="errors.excerpt">
                <UTextarea
                  v-model="form.excerpt"
                  placeholder="Опис для SEO..."
                  :rows="3"
                  :disabled="isLoading"
                  @blur="validateField('excerpt')"
                />
                <template #help>
                  <div class="flex justify-between text-xs">
                    <span>Максимум 500 символів</span>
                    <span>{{ (form.excerpt || '').length }}/500</span>
                  </div>
                </template>
              </UFormGroup>
            </div>
          </UCard>

          <!-- Content -->
          <UCard>
            <template #header>
              <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Контент</h2>
                <span class="text-sm text-gray-500">{{ form.content_raw.length }} символів</span>
              </div>
            </template>

            <UFormGroup :error="errors.content_raw">
              <UTextarea
                v-model="form.content_raw"
                placeholder="Основний текст поста..."
                :rows="20"
                class="font-mono text-sm"
                :disabled="isLoading"
                @blur="validateField('content_raw')"
              />
            </UFormGroup>
          </UCard>

          <!-- Publication Settings -->
          <UCard>
            <template #header>
              <h2 class="text-lg font-semibold">Публікація</h2>
            </template>
            
            <div class="space-y-4">
              <UCheckbox
                v-model="form.is_published"
                :label="form.is_published ? 'Опубліковано' : 'У чернетках'"
                :disabled="isLoading"
              />

              <UFormGroup v-if="form.is_published" label="Дата публікації" :error="errors.published_at">
                <UInput
                  v-model="form.published_at"
                  type="datetime-local"
                  :disabled="isLoading"
                  @blur="validateField('published_at')"
                />
                <template #help>
                  <span class="text-xs">Залиште порожнім для поточної дати</span>
                </template>
              </UFormGroup>
            </div>
          </UCard>

          <!-- Actions -->
          <div class="flex justify-between">
            <UButton
              type="button"
              @click="router.back()"
              :disabled="isLoading || isDeleting"
              variant="ghost"
              color="gray"
            >
              Скасувати
            </UButton>
            
            <UButton
              type="submit"
              :loading="isLoading"
              :disabled="isDeleting"
              color="primary"
            >
              Зберегти
            </UButton>
          </div>
        </form>
      </div>
    </UContainer>
  </div>
</template>