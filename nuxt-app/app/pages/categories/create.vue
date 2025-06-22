<script setup lang="ts">
import { z } from 'zod'

interface Category {
  id: number
  parent_id: number
  slug: string
  title: string
  description: string | null
}

const router = useRouter()
const toast = useToast()

const { data: categoriesResponse } = await useFetch<{ success: boolean, data: Category[] }>('http://localhost/api/blog/categories')

const categories = computed(() => categoriesResponse.value?.data || [])

const categorySchema = z.object({
  title: z.string().min(3, 'Назва повинна містити мінімум 3 символи').max(255),
  description: z.string().max(500).optional(),
  slug: z.string().min(1).regex(/^[a-z0-9_-]+$/, 'URL може містити тільки малі літери, цифри, дефіси та підкреслення').max(255),
  parent_id: z.number().min(0)
})

const isLoading = ref(false)
const errors = ref<Record<string, string>>({})

const form = reactive({
  title: '',
  description: '',
  slug: '',
  parent_id: 0
})

const parentOptions = computed(() => {
  const options = [{ label: 'Головна категорія', value: 0 }]
  categories.value.forEach(cat => {
    options.push({
      label: cat.parent_id && cat.parent_id !== 0 ? `  ↳ ${cat.title}` : cat.title,
      value: cat.id
    })
  })
  return options
})

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

const validateField = (fieldName: keyof typeof form) => {
  try {
    const fieldSchema = categorySchema.shape[fieldName as keyof typeof categorySchema.shape]
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
    categorySchema.parse({
      title: form.title,
      description: form.description || undefined,
      slug: form.slug,
      parent_id: form.parent_id
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
    const response = await $fetch<{ success: boolean, message: string, data: Category }>('http://localhost/api/blog/categories', {
      method: 'POST',
      body: {
        title: form.title.trim(),
        description: form.description.trim() || null,
        slug: form.slug.trim(),
        parent_id: Number(form.parent_id)
      }
    })
    
    if (response.success) {
      toast.add({ title: 'Успіх!', description: 'Категорію створено', color: 'green' })
      await router.push(`/categories/${response.data.id}`)
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
      toast.add({ title: 'Помилка', description: error.data?.message || 'Не вдалося створити категорію', color: 'red' })
    }
  } finally {
    isLoading.value = false
  }
}

const resetForm = () => {
  Object.assign(form, { title: '', description: '', slug: '', parent_id: 0 })
  errors.value = {}
}

useSeoMeta({
  title: 'Створення категорії',
  description: 'Створити нову категорію для організації постів блогу'
})
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <Head>
      <Title>Створення категорії</Title>
    </Head>

    <UContainer>
      <div class="py-8 space-y-8">
        
        <!-- Header -->
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Створення категорії</h1>
            <p class="text-gray-600 mt-1">Організація постів блогу</p>
          </div>
          <div class="flex items-center space-x-3">
            <UButton @click="router.back()" variant="ghost" color="gray" icon="i-heroicons-arrow-left">
              Назад
            </UButton>
            <UButton to="/categories" variant="ghost" color="primary" icon="i-heroicons-list-bullet">
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
                    Назва категорії <span class="text-red-500">*</span>
                  </label>
                  <UInput
                    v-model="form.title"
                    placeholder="Введіть назву"
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
                  <p class="text-xs text-gray-500 mt-1">Автоматично генерується з назви</p>
                  <p v-if="errors.slug" class="mt-1 text-sm text-red-600">{{ errors.slug }}</p>
                </div>
              </div>

              <!-- Parent Category -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Батьківська категорія</label>
                <USelect
                  v-model="form.parent_id"
                  :options="parentOptions"
                  placeholder="Оберіть батьківську категорію"
                  :disabled="isLoading"
                  @change="validateField('parent_id')"
                  :color="errors.parent_id ? 'red' : 'primary'"
                />
                <p class="text-xs text-gray-500 mt-1">Для створення ієрархії категорій</p>
                <p v-if="errors.parent_id" class="mt-1 text-sm text-red-600">{{ errors.parent_id }}</p>
              </div>

              <!-- Description -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Опис</label>
                <UTextarea
                  v-model="form.description"
                  placeholder="Опис категорії..."
                  :rows="4"
                  :disabled="isLoading"
                  @blur="validateField('description')"
                  :color="errors.description ? 'red' : 'primary'"
                />
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                  <span>Максимум 500 символів</span>
                  <span>{{ (form.description || '').length }}/500</span>
                </div>
                <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</p>
              </div>

              <!-- Preview -->
              <UAlert color="primary" variant="soft">
                <template #title>Попередній перегляд</template>
                <template #description>
                  <div class="space-y-1 text-sm">
                    <div><strong>Назва:</strong> {{ form.title || 'Не вказано' }}</div>
                    <div><strong>URL:</strong> {{ form.slug || 'Не вказано' }}</div>
                    <div><strong>Тип:</strong> {{ form.parent_id === 0 ? 'Головна категорія' : 'Підкатегорія' }}</div>
                  </div>
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
                Створити категорію
              </UButton>
            </div>
          </div>
        </form>
      </div>
    </UContainer>
  </div>
</template>