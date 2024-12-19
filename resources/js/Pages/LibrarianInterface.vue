<script setup>
import { ref, computed, watch, reactive } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { debounce } from "lodash";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { LayoutDashboardIcon, EyeIcon, PlusIcon } from "lucide-vue-next";

// Props
const props = defineProps({
  users: Array,
  roles: Array,
  books: Array,
  searchedbooks: Array,
});

// Reactive states
const users = ref([...props.users]);
const searchQuery = ref("");
const filteredBooks = ref([]); // Store the books after filtering
const cachedRoles = ref([...props.roles]);

// Initialize filteredBooks safely
filteredBooks.value = Array.isArray(props.books) ? [...props.books] : [];

// Get role function
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
    // Perform backend search only if query is not empty
    await router.get(
      route("librarian.search"),
      { searchQuery: newQuery },
      { preserveState: true, preserveScroll: true }
    );
  }
}, 300);

// Watch for changes in searchQuery
watch(searchQuery, (newQuery, oldQuery) => {
  if (!newQuery.trim()) {
    filteredBooks.value = Array.isArray(props.books) ? [...props.books] : [];
    router.get(route("librarian-dashboard"), {}, { preserveState: true }); // Clear query params
  } else if (oldQuery.length === 1 && newQuery.length === 0) {
    debouncedSearch.cancel();
    filteredBooks.value = Array.isArray(props.books) ? [...props.books] : [];
    router.get(route("librarian-dashboard"), {}, { preserveState: true });
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

// Method to delete a book
function deleteBook(id) {
  if (confirm("Are you sure you want to delete this book?")) {
    router.post(route("librarian.destroy", { id }), {
      onSuccess: () => {
        console.log("Book deleted successfully!");
      },
      onError: (errors) => {
        console.error("Failed to delete book:", errors);
      },
    });
  }
}
// Add, edit, and delete functionalities remain untouched
const showEditBookModal = ref(false);
const bookData = ref({
  id: null,
  title: "",
  category: "",
  genre: "",
  year_published: "",
  number_of_copies: 0,
});

// Open modal and populate form fields
const editBook = (book) => {
  bookData.value = { ...book };
  showEditBookModal.value = true;
};

// Save changes to the book
const saveBook = async () => {
  try {
    await router.put(`/librarian-dashboard/update/${bookData.value.id}`, {
      title: bookData.value.title,
      category: bookData.value.category,
      genre: bookData.value.genre,
      year_published: bookData.value.year_published,
      number_of_copies: bookData.value.num_copies, // Fixed key
    });

    alert("Book updated successfully!");
  } catch (error) {
    console.error("Failed to update book:", error);
    alert("An error occurred while updating the book.");
  } finally {
    showEditBookModal.value = false; // Ensure modal closes
  }
};


const showAddBookModal = ref(false);
const newBookData = ref({
  title: "",
  category: "",
  genre: "",
  year_published: "",
  author_name: "",
  author_country: "",
});

const addBook = async () => {
  try {
    console.log("New Book Data:", newBookData.value);
    await router.put(`/librarian-dashboard/add`, {
      title: newBookData.value.title,
      category: newBookData.value.category,
      genre: newBookData.value.genre,
      year_published: newBookData.value.year_published,
      author_name: newBookData.value.author_name,
      author_country: newBookData.value.author_country,
    });

    alert("Book added successfully!");
    window.location.reload();
  } catch (error) {
    console.error("Failed to add book:", error);
    alert("An error occurred while adding the book.");
  } finally {
    showAddBookModal.value = false;
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

        <button
          @click="showAddBookModal = true"
          class="px-4 py-2 bg-black text-white rounded hover:bg-green-700 transition flex items-center"
        >
          <PlusIcon class="w-5 h-5 mr-2" />
          Add Book
        </button>

        <!-- Add Book Button -->
        <div class="mb-4 flex justify-end">
          <button
            @click="showAddBookModal = true"
            class="px-4 py-2 bg-black text-white rounded hover:bg-green-700 transition flex items-center"
          >
            <PlusIcon class="w-5 h-5 mr-2" />
            Add Book
          </button>
          <!-- Add Book Modal -->
          <div
            v-if="showAddBookModal"
            class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center"
          >
            <div
              class="bg-white p-6 rounded-lg shadow-lg w-96 h-[90vh] overflow-y-auto"
            >
              <h3 class="text-xl font-bold mb-4">Add New Book</h3>

              <!-- Book Information Form -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700"
                  >Book Title:</label
                >
                <input
                  v-model="newBookData.title"
                  type="text"
                  class="mt-1 p-2 w-full border rounded"
                  placeholder="Enter book title"
                />
              </div>

              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700"
                  >Category:</label
                >
                <input
                  v-model="newBookData.category"
                  type="text"
                  class="mt-1 p-2 w-full border rounded"
                  placeholder="Enter book category"
                />
              </div>

              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700"
                  >Genre:</label
                >
                <input
                  v-model="newBookData.genre"
                  type="text"
                  class="mt-1 p-2 w-full border rounded"
                  placeholder="Enter book genre"
                />
              </div>

              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700"
                  >Year Published:</label
                >
                <input
                  v-model="newBookData.year_published"
                  type="date"
                  class="mt-1 p-2 w-full border rounded"
                />
              </div>

              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700"
                  >Author Name:</label
                >
                <input
                  v-model="newBookData.author_name"
                  type="text"
                  class="mt-1 p-2 w-full border rounded"
                  placeholder="Enter Author Name"
                />
              </div>

              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700"
                  >Author Country:</label
                >
                <input
                  v-model="newBookData.author_country"
                  type="text"
                  class="mt-1 p-2 w-full border rounded"
                  placeholder="Enter Author Country"
                />
              </div>

              <!-- Modal Buttons -->
              <div class="flex justify-end">
                <button
                  @click="addBook"
                  class="px-4 py-2 bg-black text-white rounded hover:bg-neutral-700 transition mr-2"
                >
                  Add Book
                </button>
                <button
                  @click="showAddBookModal = false"
                  class="px-4 py-2 bg-white text-black rounded hover:bg-gray-300 transition"
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
        </div>
        <!-- Modal for Adding or Editing Book -->
        <div
          v-if="showEditBookModal"
          class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center"
        >
          <div class="bg-white p-6 rounded-lg shadow-lg w-96">
            <h3 class="text-xl font-bold mb-4">Editing Book</h3>

            <!-- Book Informations -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700"
                >Book Title:
              </label>
              <input
                v-model="bookData.title"
                type="text"
                class="mt-1 p-2 w-full border rounded"
                placeholder="Enter book title"
              />
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700"
                >Category:</label
              >
              <input
                v-model="bookData.category"
                type="text"
                class="mt-1 p-2 w-full border rounded"
                placeholder="Enter book category"
              />
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700"
                >Genre:</label
              >
              <input
                v-model="bookData.genre"
                type="text"
                class="mt-1 p-2 w-full border rounded"
                placeholder="Enter Genre"
              />
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700"
                >Year Published: dd/mm/yyyy</label
              >
              <input
                v-model="bookData.year_published"
                type="date"
                class="mt-1 p-2 w-full border rounded"
                placeholder="Enter publication year"
              />
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700"
                >Copies:</label
              >
              <input
                v-model="bookData.num_copies"
                type="number"
                class="mt-1 p-2 w-full border rounded"
                placeholder="Enter new Copies"
              />
            </div>

            <!-- Modal Buttons -->
            <div class="flex justify-end">
              <button
                @click="saveBook"
                class="px-4 py-2 bg-black text-white rounded hover:bg-neutral-700 transition mr-2"
              >
                Save Changes
              </button>
              <button
                @click="showEditBookModal = false"
                class="px-4 py-2 bg-white text-black rounded hover:bg-gray-300 transition"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>

        <!-- Modal for Adding or Editing Book -->
        <div
          v-if="showEditBookModal"
          class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center"
        >
          <div class="bg-white p-6 rounded-lg shadow-lg w-96">
            <h3 class="text-xl font-bold mb-4">Editing Book</h3>

            <!-- Book Informations -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700"
                >Book Title:
              </label>
              <input
                v-model="bookData.title"
                type="text"
                class="mt-1 p-2 w-full border rounded"
                placeholder="Enter book title"
              />
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700"
                >Category:</label
              >
              <input
                v-model="bookData.category"
                type="text"
                class="mt-1 p-2 w-full border rounded"
                placeholder="Enter book category"
              />
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700"
                >Genre:</label
              >
              <input
                v-model="bookData.genre"
                type="text"
                class="mt-1 p-2 w-full border rounded"
                placeholder="Enter Genre"
              />
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700"
                >Year Published: dd/mm/yyyy</label
              >
              <input
                v-model="bookData.year_published"
                type="date"
                class="mt-1 p-2 w-full border rounded"
                placeholder="Enter publication year"
              />
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700"
                >Copies:</label
              >
              <input
                v-model="bookData.num_copies"
                type="number"
                class="mt-1 p-2 w-full border rounded"
                placeholder="Enter new Copies"
              />
            </div>

            <!-- Modal Buttons -->
            <div class="flex justify-end">
              <button
                @click="saveBook"
                class="px-4 py-2 bg-black text-white rounded hover:bg-neutral-700 transition mr-2"
              >
                Save Changes
              </button>
              <button
                @click="showEditBookModal = false"
                class="px-4 py-2 bg-white text-black rounded hover:bg-gray-300 transition"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>

        <!-- Search Results Display -->
        <div
          class="bg-gray-100 p-4 md:p-6 rounded-lg shadow-md border border-black mb-6 max-h-64 overflow-y-auto"
        >
          <h3 class="text-xl font-semibold text-gray-800">Search Results</h3>
          <ul class="mt-4 space-y-2">
            <li
              v-for="book in filteredBooks"
              :key="book.id"
              class="p-4 bg-white rounded-md shadow-md"
            >
              <div class="flex justify-between">
                <div>
                  <p class="font-semibold">
                    {{ book.title }}
                  </p>
                  <p class="text-gray-500">
                    {{ book.author_name }}
                  </p>
                  <p class="text-gray-500">
                    <strong>Category:</strong>
                    {{ book.category }}
                  </p>
                  <!-- Display category -->
                  <p class="text-gray-500">
                    <strong>Year Published:</strong>
                    {{ book.year_published }}
                  </p>
                  <!-- Display year published -->
                  <p class="text-gray-500">
                    <strong>Number of copies:</strong>
                    {{ book.num_copies }}
                  </p>
                </div>
                <div class="flex space-x-2">
                  <button
                    @click="editBook(book)"
                    class="text-sm text-blue-500 hover:underline"
                  >
                    Edit
                  </button>
                  <button
                    @click="deleteBook(book.id)"
                    class="text-sm text-red-500 hover:underline"
                  >
                    Delete
                  </button>
                </div>
              </div>
            </li>
          </ul>
          <p v-if="filteredBooks.length === 0" class="text-gray-500">
            No books found.
          </p>
        </div>

        <!-- Add Logs -->
        <!-- <div
                    class="bg-gray-100 p-4 md:p-6 rounded-lg shadow-md border border-black h-64 overflow-y-auto max-h-64 max-h-64 overflow-y-auto"
                >
                    <h3 class="text-xl font-semibold text-gray-800">
                        Add Logs
                    </h3>
                    <ul class="mt-4 space-y-2">
                        <li
                            v-for="(log, index) in addLogs"
                            :key="index"
                            class="p-2 bg-white rounded-md shadow-md"
                        >
                            {{ log }}
                        </li>
                    </ul>
                </div> -->
      </main>
    </div>
  </AuthenticatedLayout>
</template>
