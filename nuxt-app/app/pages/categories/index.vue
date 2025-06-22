<script setup lang="ts">
import { h } from 'vue'
import type { TableColumn, DropdownMenuItem } from '@nuxt/ui'

interface Category {
  id: number
  title: string
  slug: string
  description: string | null
  parent_id: number
  created_at: string | null
  updated_at: string | null
}

interface CategoryTableRow {
  id: number
  title: string
  slug: string
  description: string
  parentCategory: string
  type: string
  createdAt: string
}

const page = ref(1)
const itemsPerPage = ref(10)
const isDeleting = ref<number | null>(null)

const { data: categoriesResponse, status, refresh } = await useFetch<{ success: boolean, data: Category[] }>('http://localhost/api/blog/categories', {
  key: 'table-categories'
})

const allCategories = computed<CategoryTableRow[]>(() => {
  const categories = categoriesResponse.value?.data ?? []
  
  return categories.map((category) => {
    const parentCategory = categories.find(cat => cat.id === category.parent_id)
    
    return {
      id: category.id,
      title: category.title,
      slug: category.slug,
      description: category.description ? 
        (category.description.length > 50 ? category.description.substring(0, 50) + '...' : category.description) : 
        'Без опису',
      parentCategory: parentCategory ? parentCategory.title : 'Головна категорія',
      type: category.parent_id && category.parent_id !== 0 ? 'Підкатегорія' : 'Головна',
      createdAt: category.created_at
        ? new Date(category.created_at).toLocaleDateString('uk-UA')
        : 'Невідомо'
    }
  })
})

const categories = computed(() => {
  const start = (page.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return allCategories.value.slice(start, end)
})

const total = computed(() => allCategories.value.length)

// Функція видалення категорії
async function deleteCategory(categoryId: number, categoryTitle: string) {
  if (!confirm(`Ви впевнені, що хочете видалити категорію "${categoryTitle}"?\n\nЦя дія незворотна!`)) {
    return
  }

  isDeleting.value = categoryId

  try {
    const response = await $fetch<{ success: boolean, message: string }>(`http://localhost/api/blog/categories/${categoryId}`, {
      method: 'DELETE'
    })

    if (response.success) {
      useToast().add({
        title: 'Успіх!',
        description: response.message || 'Категорію успішно видалено',
        color: 'success',
        icon: 'i-heroicons-check-circle'
      })

      await refresh()
      
      // Якщо на поточній сторінці не залишилось категорій, переходимо на попередню
      if (categories.value.length === 0 && page.value > 1) {
        page.value = page.value - 1
      }
    }
  } catch (error: any) {
    console.error('Delete error:', error)
    useToast().add({
      title: 'Помилка',
      description: error.data?.message || 'Не вдалося видалити категорію',
      color: 'warning',
      icon: 'i-heroicons-x-circle'
    })
  } finally {
    isDeleting.value = null
  }
}

const columns: TableColumn<CategoryTableRow>[] = [
  {
    accessorKey: 'id',
    header: '#'
  },
  {
    accessorKey: 'title',
    header: 'Назва'
  },
  {
    accessorKey: 'type',
    header: 'Тип'
  },
  {
    accessorKey: 'parentCategory',
    header: 'Батьківська категорія'
  },
  {
    accessorKey: 'description',
    header: 'Опис'
  },
  {
    accessorKey: 'createdAt',
    header: 'Дата створення'
  },
  {
    id: 'action'
  }
]

// Функція для отримання дій випадаючого меню
function getDropdownActions(category: CategoryTableRow): DropdownMenuItem[][] {
  return [
    [
      {
        label: 'Переглянути',
        icon: 'i-heroicons-eye',
        onSelect: () => {
          window.open(`/categories/${category.id}`, '_blank')
        }
      }
    ],
    [
      {
        label: 'Редагувати',
        icon: 'i-heroicons-pencil-square',
        onSelect: () => {
          navigateTo(`/categories/edit-${category.id}`)
        }
      },
      {
        label: 'Видалити',
        icon: 'i-heroicons-trash',
        color: 'error',
        onSelect: () => {
          deleteCategory(category.id, category.title)
        },
        disabled: isDeleting.value === category.id
      }
    ]
  ]
}

watch(allCategories, () => {
  page.value = 1
})
</script>

<template>
  <div class="container mx-auto p-4">
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-3xl font-bold text-green-700">Категорії</h1>
        <p class="text-gray-600 mt-1">Управління категоріями для організації постів</p>
      </div>
      <UButton
        as="a"
        href="/categories/create"
        color="green"
        icon="i-heroicons-plus"
      >
        Додати категорію
      </UButton>
    </div>

    <UCard>
      <UTable 
        :data="categories" 
        :columns="columns" 
        :loading="status === 'pending'" 
        class="w-full"
      >
        <!-- Слот для назви з посиланням -->
        <template #title-cell="{ row }">
          <NuxtLink
            :to="`/categories/${row.original.id}`"
            class="text-green-600 hover:text-green-800 font-medium hover:underline"
          >
            {{ row.original.title }}
          </NuxtLink>
        </template>

        <!-- Слот для типу з бейджем -->
        <template #type-cell="{ row }">
          <UBadge
            :color="row.original.type === 'Головна' ? 'green' : 'blue'"
            variant="soft"
            size="xs"
          >
            {{ row.original.type }}
          </UBadge>
        </template>

        <!-- Слот для опису -->
        <template #description-cell="{ row }">
          <span :class="row.original.description === 'Без опису' ? 'text-gray-400 italic' : 'text-gray-700'">
            {{ row.original.description }}
          </span>
        </template>

        <!-- Слот для дій -->
        <template #action-cell="{ row }">
          <UDropdownMenu :items="getDropdownActions(row.original)">
            <UButton
              icon="i-heroicons-ellipsis-vertical"
              color="neutral"
              variant="ghost"
              aria-label="Дії"
              :loading="isDeleting === row.original.id"
              :disabled="isDeleting === row.original.id"
            />
          </UDropdownMenu>
        </template>
      </UTable>
      
      <template v-if="total > itemsPerPage">
        <div class="flex justify-center mt-6 p-4 border-t">
          <UPagination
            v-model:page="page"
            :total="total"
            :items-per-page="itemsPerPage"
            :sibling-count="2"
            show-edges
            show-controls
            color="neutral"
            variant="outline"
            active-color="green"
            active-variant="solid"
            size="md"
          />
        </div>
      </template>
    </UCard>

    <div class="flex justify-between items-center mt-4 text-sm text-gray-600">
      <div>
        Показано {{ Math.min(itemsPerPage, total - (page - 1) * itemsPerPage) }} з {{ total }} записів
      </div>
      <div>
        Сторінка {{ page }} з {{ Math.ceil(total / itemsPerPage) }}
      </div>
    </div>

    <!-- Індикатор завантаження при видаленні -->
    <div v-if="isDeleting" class="fixed inset-0 bg-black bg-opacity-25 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg shadow-lg">
        <div class="flex items-center gap-3">
          <UIcon name="i-heroicons-arrow-path" class="animate-spin" />
          <span>Видалення категорії...</span>
        </div>
      </div>
    </div>
  </div>
</template>