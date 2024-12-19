<script setup>
import { ref, computed, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { debounce } from "lodash";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { LayoutDashboardIcon, EyeIcon } from "lucide-vue-next";

// Props
const props = defineProps({
  users: Array,
  roles: Array,
  books: Array,
  searchedbooks: Array,
});

// Reactive states
const searchQuery = ref("");
const filteredBooks = ref([]);
const users = ref([...props.users]);
const cachedRoles = ref([...props.roles]);
// Initialize filteredBooks safely
filteredBooks.value = Array.isArray(props.books) ? [...props.books] : [];

// Role retrieval function
const getRole = (roleId) => {
  const role = cachedRoles.value.find((role) => role.id === roleId);
  return role ? role.user_type : "Unknown";
};

// User profile state
const user = ref({
  avatar: "/images/image.png",
});

// Debounced search function
const debouncedSearch = debounce(async (newQuery) => {
  if (newQuery.trim()) {
    await router.get(
      route("user.search"),
      { searchQuery: newQuery },
      { preserveState: true, preserveScroll: true }
    );
  }
}, 300);

// Watch for changes in searchQuery
watch(searchQuery, (newQuery, oldQuery) => {
  if (!newQuery.trim()) {
    filteredBooks.value = Array.isArray(props.books) ? [...props.books] : [];
    router.get(route("user-dashboard"), {}, { preserveState: true });
  } else if (oldQuery.length === 1 && newQuery.length === 0) {
    debouncedSearch.cancel();
    filteredBooks.value = Array.isArray(props.books) ? [...props.books] : [];
    router.get(route("user-dashboard"), {}, { preserveState: true });
  } else {
    debouncedSearch(newQuery);
  }
});

// Watch for backend updates to searchedbooks
watch(
  () => props.searchedbooks,
  (newBooks) => {
    filteredBooks.value =
      Array.isArray(newBooks) && newBooks.length > 0
        ? newBooks
        : Array.isArray(props.books)
        ? [...props.books]
        : [];
  },
  { immediate: true }
);

// Borrow book method
const isBorrowing = ref(false);

const borrowBook = async (userId, bookId) => {
  // Ask the user for confirmation before proceeding
  const isConfirmed = confirm("Are you sure you want to borrow this book?");

  if (!isConfirmed) {
    return; // Stop the function if the user cancels
  }

  try {
    isBorrowing.value = true; // Set isBorrowing to true to indicate loading

    // Make the API call to borrow the book
    await router.post(
      route("user.borrowBook"),
      { users_id: userId, book_id: bookId },
      { preserveState: true }
    );

    alert("Book borrowed successfully!");

    // Optionally refresh the page or update the state
    router.get(route("user-dashboard"), {}, { preserveState: true });
  } catch (error) {
    console.error(error);
    alert("Failed to borrow the book. Please try again.");
  } finally {
    isBorrowing.value = true;
  }
};
</script>

<template>
  <Head title="Dashboard" />
  <AuthenticatedLayout>
    <template #header>
      <h2
        class="text-xl font-semibold leading-tight text-gray-800 flex items-center justify-between"
      >
        <span>Library Management</span>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search books..."
          class="px-4 py-2 w-64 border rounded-md text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
      </h2>
    </template>

    <div class="flex flex-col md:flex-row h-screen bg-gray-100">
      <!-- Sidebar -->
      <aside
        class="w-full lg:w-1/4 bg-gray-200 p-4 lg:p-6 flex flex-col border-b lg:border-b-0 lg:border-r border-black"
      >
        <div class="flex flex-col items-center mb-6">
          <img
            :src="user.avatar"
            :alt="user.name"
            class="w-20 h-20 lg:w-24 lg:h-24 rounded-full border-2 border-black mb-2"
          />
          <div class="text-center">
            <h2 class="text-lg lg:text-2xl font-bold text-gray-800">
              {{ $page.props.auth.user.name }}
            </h2>
            <p class="text-sm lg:text-base text-gray-600">
              {{ $page.props.auth.user.email }}
            </p>
            <p class="font-bold text-sm lg:text-base text-gray-500">
              {{ getRole($page.props.auth.user.role_id) }}
            </p>
          </div>
        </div>
      </aside>

      <!-- Main Content -->
      <main class="w-full md:w-3/4 p-4 md:p-8">
        <h1
          class="text-2xl md:text-3xl font-bold mb-4 text-gray-800 flex items-center"
        >
          <LayoutDashboardIcon class="w-6 md:w-8 h-6 md:h-8 mr-2" />
          Dashboard
        </h1>

        <!-- Search Results Display -->
        <div
          class="bg-gray-100 p-4 md:p-6 rounded-lg shadow-md border border-black mb-6 max-h-64 overflow-y-auto"
        >
          <h3 class="text-xl font-semibold text-gray-800">Search Results</h3>
          <ul class="mt-4 space-y-2">
            <li
              v-for="book in filteredBooks"
              :key="book.id"
              class="p-4 bg-white rounded-md shadow-md flex justify-between items-center"
            >
              <div>
                <p class="font-semibold">{{ book.title }}</p>
                <p class="text-gray-500">{{ book.author_name }}</p>

                <!-- Ge add sab nko apil ang Category ug Year Pub-->
                <p class="text-gray-400">
                  <strong>Category & Genre:</strong>
                  {{ book.category }}, {{ book.genre }}
                </p>
                <p class="text-gray-400">
                  <strong>Year Published:</strong>
                  {{ book.year_published }}
                </p>
              </div>
              <button
                @click="borrowBook($page.props.auth.user.id, book.id)"
                :disabled="isBorrowing"
                class="px-4 py-2 bg-black text-white rounded hover:bg-neutral-700 transition"
              >
                Borrow
              </button>
            </li>
          </ul>
          <p v-if="filteredBooks.length === 0" class="text-gray-500">
            No books found.
          </p>
        </div>

        <!-- Borrow Logs -->
        <div
          class="bg-gray-100 p-4 md:p-6 rounded-lg shadow-md border border-black h-64 overflow-y-auto max-h-64 overflow-y-auto"
        >
          <h3 class="text-xl font-semibold text-gray-800">Borrow Logs</h3>
          <ul class="mt-4 space-y-2">
            <!-- <li
              v-for="(log, index) in borrowLogs"
              :key="index"
              class="p-2 bg-white rounded-md shadow-md"
            >
              {{ log }}
            </li> -->
          </ul>
        </div>
      </main>
    </div>
  </AuthenticatedLayout>
</template>
