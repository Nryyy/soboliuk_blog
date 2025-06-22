<script setup lang="ts">
import { h } from 'vue'
import type { TableColumn, DropdownMenuItem } from '@nuxt/ui'

interface Blog {
  id: number
  title: string
  slug: string
  published_at: string | null
  user?: { name: string }
  category?: { title: string }
}

interface PostTableRow {
  id: number
  title: string
  slug: string
  author: string
  category: string
  publishedAt: string
}

const page = ref(1)
const itemsPerPage = ref(10)
const isDeleting = ref<number | null>(null)

const { data: postsResponse, status, refresh } = await useFetch<{ success: boolean, data: Blog[] }>('http://localhost/api/blog/posts', {
  key: 'table-posts'
})

const allPosts = computed<PostTableRow[]>(() =>
  postsResponse.value?.data?.map((post) => ({
    id: post.id,
    title: post.title,
    slug: post.slug,
    author: post.user?.name ?? 'Невідомо',
    category: post.category?.title ?? 'Без категорії',
    publishedAt: post.published_at
      ? new Date(post.published_at).toLocaleDateString('uk-UA')
      : 'Не опубліковано'
  })) ?? []
)

const posts = computed(() => {
  const start = (page.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return allPosts.value.slice(start, end)
})

const total = computed(() => allPosts.value.length)

const deletePost = async (postId: number, postTitle: string) => {
  if (!confirm(`Видалити пост "${postTitle}"? Ця дія незворотна!`)) return

  isDeleting.value = postId
  try {
    const response = await $fetch<{ success: boolean, message: string }>(`http://localhost/api/blog/posts/${postId}`, {
      method: 'DELETE'
    })

    if (response.success) {
      useToast().add({
        title: 'Успіх!',
        description: 'Пост видалено',
        color: 'green'
      })

      await refresh()
      
      if (posts.value.length === 0 && page.value > 1) {
        page.value = page.value - 1
      }
    }
  } catch (error: any) {
    useToast().add({
      title: 'Помилка',
      description: error.data?.message || 'Не вдалося видалити пост',
      color: 'red'
    })
  } finally {
    isDeleting.value = null
  }
}

const columns: TableColumn<PostTableRow>[] = [
  { accessorKey: 'id', header: '#' },
  { accessorKey: 'title', header: 'Заголовок' },
  { accessorKey: 'author', header: 'Автор' },
  { accessorKey: 'category', header: 'Категорія' },
  { accessorKey: 'publishedAt', header: 'Дата публікації' },
  { id: 'action' }
]

const getDropdownActions = (post: PostTableRow): DropdownMenuItem[][] => [
  [
    {
      label: 'Переглянути',
      icon: 'i-heroicons-eye',
      onSelect: () => window.open(`/posts/${post.id}`, '_blank')
    }
  ],
  [
    {
      label: 'Редагувати',
      icon: 'i-heroicons-pencil-square',
      onSelect: () => navigateTo(`/posts/edit-${post.id}`)
    },
    {
      label: 'Видалити',
      icon: 'i-heroicons-trash',
      color: 'error',
      onSelect: () => deletePost(post.id, post.title),
      disabled: isDeleting.value === post.id
    }
  ]
]

watch(allPosts, () => { page.value = 1 })
</script>

<template>
  <div class="container mx-auto p-4">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold">Блог пости</h1>
      <UButton as="a" href="/posts/create" color="primary" icon="i-heroicons-plus">
        Додати пост
      </UButton>
    </div>

    <UCard>
      <UTable :data="posts" :columns="columns" :loading="status === 'pending'" class="w-full">
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
      
      <div v-if="total > itemsPerPage" class="flex justify-center mt-6 p-4 border-t">
        <UPagination
          v-model:page="page"
          :total="total"
          :items-per-page="itemsPerPage"
          :sibling-count="2"
          show-edges
          show-controls
          color="neutral"
          variant="outline"
          active-color="primary"
          active-variant="solid"
          size="md"
        />
      </div>
    </UCard>

    <div class="flex justify-between items-center mt-4 text-sm text-gray-600">
      <div>Показано {{ Math.min(itemsPerPage, total - (page - 1) * itemsPerPage) }} з {{ total }} записів</div>
      <div>Сторінка {{ page }} з {{ Math.ceil(total / itemsPerPage) }}</div>
    </div>

    <div v-if="isDeleting" class="fixed inset-0 bg-black bg-opacity-25 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg shadow-lg">
        <div class="flex items-center gap-3">
          <UIcon name="i-heroicons-arrow-path" class="animate-spin" />
          <span>Видалення поста...</span>
        </div>
      </div>
    </div>
  </div>
</template>