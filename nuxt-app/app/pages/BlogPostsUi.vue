<script setup lang="ts">
import { h } from 'vue'
import type { TableColumn } from '@nuxt/ui'

interface Blog {
  id: number
  title: string
  published_at: string | null
  user?: { name: string }
  category?: { title: string }
}

interface PostTableRow {
  id: number
  title: string
  author: string
  category: string
  publishedAt: string
}

const page = ref(1)
const itemsPerPage = ref(10)

const { data: postsResponse, status } = await useFetch<{ data: Blog[] }>('http://localhost/api/blog/posts', {
  key: 'table-posts'
})

const allPosts = computed<PostTableRow[]>(() =>
  postsResponse.value?.data.map((post) => ({
    id: post.id,
    title: post.title,
    author: post.user?.name ?? 'Невідомо',
    category: post.category?.title ?? 'Без категорії',
    publishedAt: post.published_at
      ? new Date(post.published_at).toLocaleDateString('uk-UA')
      : 'Не опубліковано'
  })) ?? []
)

const total = computed(() => allPosts.value.length)

const posts = computed(() => {
  const start = (page.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return allPosts.value.slice(start, end)
})

const columns: TableColumn<PostTableRow>[] = [
  {
    accessorKey: 'id',
    header: '#'
  },
  {
    accessorKey: 'author',
    header: 'Автор'
  },
  {
    accessorKey: 'category',
    header: 'Категорія'
  },
  {
    accessorKey: 'title',
    header: 'Заголовок',
    cell: ({ row }) =>
      h(
        'a',
        {
          href: `/posts/${row.original.id}`,
          class: 'text-blue-600 hover:text-blue-800 underline font-medium'
        },
        row.original.title
      )
  },
  {
    accessorKey: 'publishedAt',
    header: 'Дата публікації'
  },
  {
    accessorKey: 'actions',
    header: 'Дії',
    cell: ({ row }) =>
      h('div', { class: 'flex gap-2' }, [
        h(
          'a',
          {
            href: `/posts/${row.original.id}`,
            class: 'text-blue-600 hover:text-blue-800 text-sm'
          },
          'Переглянути'
        ),
        h(
          'a',
          {
            href: `/admin/blog/posts/${row.original.id}/edit`,
            class: 'text-green-600 hover:text-green-800 text-sm'
          },
          'Редагувати'
        )
      ])
  }
]

watch(allPosts, () => {
  page.value = 1
})
</script>

<template>
  <div class="container mx-auto p-4">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold">Блог пости</h1>
      <UButton
        as="a"
        href="/admin/blog/posts/create"
        color="primary"
        icon="i-heroicons-plus"
      >
        Додати пост
      </UButton>
    </div>

    <UCard>
      <UTable
        :data="posts"
        :columns="columns"
        :loading="status === 'pending'"
        stripe
        row-key="id"
        class="w-full"
      />
      
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
            active-color="primary"
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
  </div>
</template>