<script setup lang="ts">
import { z } from 'zod'

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

const route = useRoute()
const router = useRouter()
const toast = useToast()
const categoryId = route.params.id

const categorySchema = z.object({
  title: z.string().min(3, 'Назва повинна містити мінімум 3 символи').max(255),
  description: z.string().max(500).optional(),
  slug: z.string().min(1).regex(/^[a-z0-9_-]+$/, 'URL може містити тільки малі літери, цифри, дефіси та підкреслення').max(255),
  parent_id: z.number().min(0)
})

const { data: categoryResponse, error: categoryError, refresh: refreshCategory } = await useFetch<{ success: boolean, data: Category }>(`http://localhost/api/blog/categories/${categoryId}`, {
  key: `category-${categoryId}`
})
const { data: categoriesResponse } = await useFetch<{ success: boolean, data: Category[] }>('http://localhost/api/blog/categories')

if (categoryError.value || !categoryResponse.value?.data) {
  throw createError({ statusCode: 404, statusMessage: 'Категорію не знайдено' })
}

const category = ref(categoryResponse.value.data)
const allCategories = computed(() => categoriesResponse.value?.data || [])

const isLoading = ref(false)
const isDeleting = ref(false)
const errors = ref<Record<string, string>>({})
const hasChanges = ref(false)

const form = reactive({
  title: category.value.title,
  description: category.value.description || '',
  slug: category.value.slug,
  parent_id: category.value.parent_id
})

watch(form, () => {
  hasChanges.value = 
    form.title !== category.value.title ||
    form.description !== (category.value.description || '') ||
    form.slug !== category.value.slug ||
    form.parent_id !== category.value.parent_id
}, { deep: true })

const parentOptions = computed(() => {
  const options = [{ label: 'Без батьківської категорії', value: 0 }]
  
  const availableCategories = allCategories.value.filter(cat => {
    if (cat.id === category.value.id) return false
    if (cat.parent_id === category.value.id) return false
    return true
  })
  
  availableCategories.forEach(cat => {
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
  if (newTitle && newTitle !== category.value.title) {
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
  if (!hasChanges.value) {
    toast.add({ title: 'Інформація', description: 'Немає змін для збереження', color: 'yellow' })
    return
  }

  if (!validateForm()) {
    toast.add({ title: 'Помилка валідації', description: 'Виправте помилки у формі', color: 'red' })
    return
  }

  isLoading.value = true
  
  const requestBody = {
    title: form.title.trim(),
    description: form.description.trim() || null,
    slug: form.slug.trim(),
    parent_id: Number(form.parent_id)
  }

  try {
    const response = await $fetch<{ success: boolean, message: string, data: Category }>(`http://localhost/api/blog/categories/${categoryId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: requestBody
    })
    
    if (response.success) {
      // Check if server returned updated data
      const serverData = response.data
      const dataMatches = 
        serverData.title === requestBody.title &&
        (serverData.description || '') === (requestBody.description || '') &&
        serverData.slug === requestBody.slug &&
        serverData.parent_id === requestBody.parent_id

      if (dataMatches) {
        // Server data matches - update local state
        category.value = serverData
        Object.assign(form, {
          title: serverData.title,
          description: serverData.description || '',
          slug: serverData.slug,
          parent_id: serverData.parent_id
        })
        hasChanges.value = false
        
        toast.add({ 
          title: 'Успіх!', 
          description: 'Категорію успішно оновлено', 
          color: 'green' 
        })
      } else {
        // Server data doesn't match - there might be a server-side issue
        console.warn('⚠️ SERVER DATA MISMATCH')
        console.warn('Sent:', requestBody)
        console.warn('Received:', serverData)
        
        toast.add({ 
          title: 'Попередження', 
          description: 'Дані збережено, але сервер повернув неочікувані дані. Перезавантажте сторінку.', 
          color: 'orange' 
        })
        
        // Force refresh to get actual server state
        await refreshCategory()
        if (categoryResponse.value?.data) {
          category.value = categoryResponse.value.data
          Object.assign(form, {
            title: category.value.title,
            description: category.value.description || '',
            slug: category.value.slug,
            parent_id: category.value.parent_id
          })
          hasChanges.value = false
        }
      }
    } else {
      toast.add({ 
        title: 'Помилка', 
        description: response.message || 'Сервер повернув помилку', 
        color: 'red' 
      })
    }
  } catch (error: any) {
    console.error('Request failed:', error)
    
    let errorMessage = 'Не вдалося оновити категорію'
    
    if (error.status === 422 && error.data?.errors) {
      const serverErrors = error.data.errors
      Object.keys(serverErrors).forEach(field => {
        if (serverErrors[field] && serverErrors[field][0]) {
          errors.value[field] = serverErrors[field][0]
        }
      })
      errorMessage = 'Помилка валідації на сервері'
    } else if (error.data?.message) {
      errorMessage = error.data.message
    } else if (error.message) {
      errorMessage = error.message
    }
    
    toast.add({ 
      title: 'Помилка', 
      description: errorMessage, 
      color: 'red' 
    })
  } finally {
    isLoading.value = false
  }
}

const handleDelete = async () => {
  if (!confirm(`Видалити категорію "${category.value.title}"? Ця дія незворотна!`)) return
  
  isDeleting.value = true
  try {
    const response = await $fetch<{ success: boolean, message: string }>(`http://localhost/api/blog/categories/${categoryId}`, { 
      method: 'DELETE',
      headers: {
        'Accept': 'application/json'
      }
    })
    
    if (response.success) {
      toast.add({ title: 'Успіх!', description: 'Категорію видалено', color: 'green' })
      await router.push('/categories')
    }
  } catch (error: any) {
    toast.add({ 
      title: 'Помилка', 
      description: error.data?.message || error.message || 'Не вдалося видалити категорію', 
      color: 'red' 
    })
  } finally {
    isDeleting.value = false
  }
}

const resetForm = () => {
  Object.assign(form, {
    title: category.value.title,
    description: category.value.description || '',
    slug: category.value.slug,
    parent_id: category.value.parent_id
  })
  errors.value = {}
  hasChanges.value = false
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <Head>
      <Title>Редагування: {{ category.title }}</Title>
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
              <h1 class="text-xl font-semibold text-gray-900 truncate">{{ category.title }}</h1>
              <p class="text-sm text-gray-500">ID: {{ categoryId }}</p>
            </div>
          </div>
          <div class="flex items-center space-x-3">
            <UButton :to="`/categories/${category.id}`" target="_blank" variant="ghost" color="primary" icon="i-heroicons-eye">
              Переглянути
            </UButton>
            <UButton @click="handleDelete" :loading="isDeleting" :disabled="isLoading" color="red" variant="ghost" icon="i-heroicons-trash">
              Видалити
            </UButton>
          </div>
        </div>

        <!-- Changes indicator -->
        <UAlert v-if="hasChanges" color="orange" variant="soft">
          <template #title>Є незбережені зміни</template>
          <template #description>
            Ви внесли зміни до форми. Не забудьте зберегти їх.
          </template>
        </UAlert>

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

              <!-- Category Info -->
              <UAlert color="primary" variant="soft">
                <template #title>Інформація про категорію</template>
                <template #description>
                  <div class="space-y-1 text-sm">
                    <div><strong>ID:</strong> {{ category.id }}</div>
                    <div><strong>Тип:</strong> {{ category.parent_id && category.parent_id !== 0 ? 'Підкатегорія' : 'Головна категорія' }}</div>
                    <div><strong>Створено:</strong> {{ category.created_at ? new Date(category.created_at).toLocaleDateString('uk-UA') : 'Невідомо' }}</div>
                    <div><strong>Оновлено:</strong> {{ category.updated_at ? new Date(category.updated_at).toLocaleDateString('uk-UA') : 'Невідомо' }}</div>
                  </div>
                </template>
              </UAlert>
            </div>
          </UCard>

          <!-- Actions -->
          <div class="flex justify-between">
            <div class="flex gap-3">
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
                v-if="hasChanges"
                type="button"
                @click="resetForm"
                :disabled="isLoading || isDeleting"
                variant="ghost"
                color="orange"
              >
                Скинути зміни
              </UButton>
            </div>
            
            <UButton
              type="submit"
              :loading="isLoading"
              :disabled="isDeleting || !hasChanges"
              color="primary"
            >
              {{ hasChanges ? 'Зберегти зміни' : 'Немає змін' }}
            </UButton>
          </div>
        </form>
      </div>
    </UContainer>
  </div>
</template>