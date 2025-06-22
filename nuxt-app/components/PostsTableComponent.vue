<template>
  <div class="container mx-auto p-4">
    <div class="flex justify-center">
      <div class="w-full">
        <nav class="navbar bg-gray-100 p-4 mb-4 rounded">
          <a href="blog/posts/create" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            Додати
          </a>
        </nav>
        <div class="card bg-white shadow-lg rounded-lg">
          <div class="card-body p-6">
            <div v-if="loading" class="text-center">
              <p>Завантаження...</p>
            </div>
            <div v-else-if="error" class="text-red-500 text-center">
              <p>Помилка: {{ error }}</p>
            </div>
            <table v-else class="table table-auto w-full border-collapse">
              <thead>
                <tr class="bg-gray-50">
                  <th class="border px-4 py-2 text-left">#</th>
                  <th class="border px-4 py-2 text-left">Автор</th>
                  <th class="border px-4 py-2 text-left">Категорія</th>
                  <th class="border px-4 py-2 text-left">Заголовок</th>
                  <th class="border px-4 py-2 text-left">Дата публікації</th>
                  <th class="border px-4 py-2 text-left">Дії</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="post in posts" :key="post.id" class="hover:bg-gray-50">
                  <td class="border px-4 py-2">{{ post.id }}</td>
                  <td class="border px-4 py-2">{{ post.user?.name || 'Невідомо' }}</td>
                  <td class="border px-4 py-2">{{ post.category?.title || 'Без категорії' }}</td>
                  <td class="border px-4 py-2">
                    <NuxtLink :to="`/posts/${post.id}`" class="text-blue-500 hover:underline font-medium">
                      {{ post.title }}
                    </NuxtLink>
                  </td>
                  <td class="border px-4 py-2">{{ formatDate(post.published_at) }}</td>
                  <td class="border px-4 py-2">
                    <div class="flex gap-2">
                      <NuxtLink 
                        :to="`/posts/${post.id}`" 
                        class="text-blue-500 hover:text-blue-700 text-sm"
                      >
                        Переглянути
                      </NuxtLink>
                      <a 
                        :href="`/posts/edit-${post.id}`" 
                        class="text-green-500 hover:text-green-700 text-sm"
                      >
                        Редагувати
                      </a>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
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
  published_at: string;
  user: User;
  category: Category;
}

const posts = ref<Post[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

const formatDate = (dateString: string) => {
  if (!dateString) return 'Не опубліковано';
  return new Date(dateString).toLocaleDateString('uk-UA');
};

const getPosts = async () => {
  try {
    loading.value = true;
    error.value = null;
    
    const response = await $fetch<{success: boolean, data: Post[]}>('http://localhost/api/blog/posts');
    
    if (response.success) {
      posts.value = response.data;
    } else {
      error.value = 'Не вдалося отримати дані';
    }
  } catch (err) {
    console.error('Помилка при отриманні постів:', err);
    error.value = 'Помилка з\'єднання з сервером';
  } finally {
    loading.value = false;
  }
};

// Викликаємо функцію при монтуванні компонента
onMounted(() => {
  getPosts();
});
</script>