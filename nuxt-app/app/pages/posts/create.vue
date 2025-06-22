<script setup lang="ts">
import { z } from 'zod'

interface Category {
  id: number
  title: string
  slug: string
}

interface User {
  id: number
  name: string
  email: string
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
}

const router = useRouter()
const toast = useToast()

const { data: categoriesResponse } = await useFetch<{ success: boolean, data: Category[] }>('http://localhost/api/blog/categories')
const { data: usersResponse } = await useFetch<{ success: boolean, data: User[] }>('http://localhost/api/users', { 
  default: () => ({ success: true, data: [] }) 
})

const categories = computed(() => categoriesResponse.value?.data || [])
const users = computed(() => usersResponse.value?.data || [])

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

const isLoading = ref(false)
const errors = ref<Record<string, string>>({})

const form = reactive({
  title: '',
  excerpt: '',
  content_raw: '',
  content_html: '',
  slug: '',
  category_id: categories.value.length > 0 ? categories.value[0]?.id : null,
  user_id: users.value.length > 0 ? users.value[0]?.id : null,
  is_published: false,
  published_at: ''
})

const categoryOptions = computed(() => 
  categories.value.map(cat => ({ label: cat.title, value: cat.id }))
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
  if (newTitle) {
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
    const response = await $fetch<{ success: boolean, message: string, data: Post }>('http://localhost/api/blog/posts', {
      method: 'POST',
      body: {
        ...form,
        published_at: form.is_published && form.published_at ? form.published_at : null
      }
    })
    
    if (response.success) {
      toast.add({ title: 'Успіх!', description: 'Пост створено', color: 'green' })
      await router.push(`/posts/${response.data.slug}`)
    }
  } catch (error: any) {
    if (error.status === 422 && error.data?.errors) {
      const serverErrors = error.data.errors
      Object.keys(serverErrors).forEach(field => {
        if (serverErrors[field] && serverErrors[field][0]) {
          errors.value[field] = serverErrors[field][0]
        }
      })
      toast.add({ title: 'Помилка валідації', description: 'Виправте помилки у формі', color: 'red' })
    } else {
      toast.add({ title: 'Помилка', description: error.data?.message || 'Не вдалося створити пост', color: 'red' })
    }
  } finally {
    isLoading.value = false
  }
}

const resetForm = () => {
  Object.assign(form, {
    title: '',
    excerpt: '',
    content_raw: '',
    content_html: '',
    slug: '',
    category_id: categories.value.length > 0 ? categories.value[0]?.id : null,
    user_id: users.value.length > 0 ? users.value[0]?.id : null,
    is_published: false,
    published_at: ''
  })
  errors.value = {}
}

useSeoMeta({
  title: 'Створення поста',
  description: 'Створити новий пост для блогу'
})
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <Head>
      <Title>Створення поста</Title>
    </Head>

    <UContainer>
      <div class="py-8 space-y-8">
        
        <!-- Header -->
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Створення поста</h1>
            <p class="text-gray-600 mt-1">Новий пост для блогу</p>
          </div>
          <div class="flex items-center space-x-3">
            <UButton @click="router.back()" variant="ghost" color="gray" icon="i-heroicons-arrow-left">
              Назад
            </UButton>
            <UButton to="/BlogPostsUi" variant="ghost" color="primary" icon="i-heroicons-list-bullet">
              До списку
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
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Заголовок <span class="text-red-500">*</span>
                  </label>
                  <UInput
                    v-model="form.title"
                    placeholder="Введіть заголовок"
                    :disabled="isLoading"
                    @blur="validateField('title')"
                    :color="errors.title ? 'red' : 'primary'"
                  />
                  <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title }}</p>
                </div>

                <!-- Slug -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    URL адреса <span class="text-red-500">*</span>
                  </label>
                  <UInput
                    v-model="form.slug"
                    placeholder="url-slug"
                    :disabled="isLoading"
                    @blur="validateField('slug')"
                    :color="errors.slug ? 'red' : 'primary'"
                  />
                  <p class="text-xs text-gray-500 mt-1">Автоматично генерується з заголовка</p>
                  <p v-if="errors.slug" class="mt-1 text-sm text-red-600">{{ errors.slug }}</p>
                </div>

                <!-- Category -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Категорія <span class="text-red-500">*</span>
                  </label>
                  <USelect
                    v-model="form.category_id"
                    :options="categoryOptions"
                    placeholder="Оберіть категорію"
                    :disabled="isLoading"
                    @change="validateField('category_id')"
                    :color="errors.category_id ? 'red' : 'primary'"
                  />
                  <p v-if="errors.category_id" class="mt-1 text-sm text-red-600">{{ errors.category_id }}</p>
                </div>

                <!-- Author -->
                <div v-if="users.length > 0">
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Автор <span class="text-red-500">*</span>
                  </label>
                  <USelect
                    v-model="form.user_id"
                    :options="userOptions"
                    placeholder="Оберіть автора"
                    :disabled="isLoading"
                    @change="validateField('user_id')"
                    :color="errors.user_id ? 'red' : 'primary'"
                  />
                  <p v-if="errors.user_id" class="mt-1 text-sm text-red-600">{{ errors.user_id }}</p>
                </div>
              </div>

              <!-- Excerpt -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Короткий опис</label>
                <UTextarea
                  v-model="form.excerpt"
                  placeholder="Опис поста..."
                  :rows="3"
                  :disabled="isLoading"
                  @blur="validateField('excerpt')"
                  :color="errors.excerpt ? 'red' : 'primary'"
                />
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                  <span>Максимум 500 символів</span>
                  <span>{{ (form.excerpt || '').length }}/500</span>
                </div>
                <p v-if="errors.excerpt" class="mt-1 text-sm text-red-600">{{ errors.excerpt }}</p>
              </div>
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

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Основний текст <span class="text-red-500">*</span>
              </label>
              <UTextarea
                v-model="form.content_raw"
                placeholder="Текст поста..."
                :rows="20"
                class="font-mono text-sm"
                :disabled="isLoading"
                @blur="validateField('content_raw')"
                :color="errors.content_raw ? 'red' : 'primary'"
              />
              <p class="text-xs text-gray-500 mt-1">Мінімум 10 символів</p>
              <p v-if="errors.content_raw" class="mt-1 text-sm text-red-600">{{ errors.content_raw }}</p>
            </div>
          </UCard>

          <!-- Publication -->
          <UCard>
            <template #header>
              <h2 class="text-lg font-semibold">Публікація</h2>
            </template>
            
            <div class="space-y-4">
              <UCheckbox
                v-model="form.is_published"
                :label="form.is_published ? 'Опублікувати' : 'Зберегти як чернетку'"
                :disabled="isLoading"
              />

              <div v-if="form.is_published">
                <label class="block text-sm font-medium text-gray-700 mb-2">Дата публікації</label>
                <UInput
                  v-model="form.published_at"
                  type="datetime-local"
                  :disabled="isLoading"
                  @blur="validateField('published_at')"
                  :color="errors.published_at ? 'red' : 'primary'"
                />
                <p class="text-xs text-gray-500 mt-1">Залиште порожнім для поточної дати</p>
                <p v-if="errors.published_at" class="mt-1 text-sm text-red-600">{{ errors.published_at }}</p>
              </div>

              <UAlert :color="form.is_published ? 'green' : 'yellow'" variant="soft">
                <template #title>Статус публікації</template>
                <template #description>
                  {{ form.is_published ? 'Пост буде опубліковано' : 'Пост буде збережено як чернетка' }}
                </template>
              </UAlert>
            </div>
          </UCard>

          <!-- Actions -->
          <div class="flex justify-between">
            <UButton
              type="button"
              @click="resetForm"
              :disabled="isLoading"
              variant="ghost"
              color="gray"
            >
              Очистити
            </UButton>
            
            <div class="flex gap-3">
              <UButton
                type="button"
                @click="router.back()"
                :disabled="isLoading"
                variant="ghost"
                color="gray"
              >
                Скасувати
              </UButton>
              
              <UButton
                type="submit"
                :loading="isLoading"
                color="primary"
              >
                {{ form.is_published ? 'Створити та опублікувати' : 'Зберегти чернетку' }}
              </UButton>
            </div>
          </div>
        </form>
      </div>
    </UContainer>
  </div>
</template>