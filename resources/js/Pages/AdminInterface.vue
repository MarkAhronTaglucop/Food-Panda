<script setup>
import { ref, computed } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {
  UserIcon,
  ActivityIcon,
  TrashIcon,
  EditIcon,
  EyeIcon,
} from "lucide-vue-next";

// Props
const props = defineProps({
  users: Array,
  roles: Array,
  actlogs: Array,
  menu: Array,
  foodstores: Array,
});

// Reactive state for users
const searchQuery = ref("");
const users = ref([...props.users]);

// Computed filtered users
const filteredUsers = computed(() => {
  if (!searchQuery.value) return users.value;
  return users.value.filter((user) =>
    user.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

// User Profile State
const user = ref({
  avatar: "/images/image.png",
});

// User Form for Editing Role
const showEditUserModal = ref(false);
const editingUser = ref(null);
const userForm = ref({
  name: "",
  email: "",
  role: "",
});

// Function to get the role based on user ID
const getRole = (roleId) => {
  const role = props.roles.find((role) => role.id === roleId);
  return role ? role.user_type : "Unknown";
};

// Function to delete user
const deleteUser = (userId) => {
  if (confirm("Are you sure you want to delete this user?")) {
    router.delete(route("admin.deleteUser", { user: userId }), {
      onSuccess: () => {
        users.value = users.value.filter((user) => user.id !== userId);
      },
      onError: () => {
        alert("There was an error deleting the user.");
      },
    });
  }
};

// Function to edit user
const editUser = (user) => {
  showEditUserModal.value = true;
  editingUser.value = user;
  userForm.value = {
    id: user.id,
    name: user.name,
    email: user.email,
    role_id: user.role_id,
  };
};

// Submit updated role to the backend
const submitUser = () => {
  if (editingUser.value) {
    if (
      getRole(editingUser.value.role_id) === "admin" &&
      userForm.value.role_id !== editingUser.value.role_id
    ) {
      alert("Once promoted to Admin, users cannot be demoted.");
      return;
    }

    router.put(
      route("admin.updateUserRole", { user: userForm.value.id }),
      {
        role_id: userForm.value.role_id,
      },
      {
        onSuccess: () => {
          const userIndex = users.value.findIndex(
            (u) => u.id === userForm.value.id
          );
          if (userIndex > -1) {
            users.value[userIndex].role_id = userForm.value.role_id;
          }
          closeModal();
          alert("User role updated successfully!");
        },
        onError: () => {
          alert("There was an error updating the user role.");
        },
      }
    );
  } else {
    alert("No user selected for editing.");
  }
};

// Close the modal and reset the form
const closeModal = () => {
  showEditUserModal.value = false;
  editingUser.value = null;
  userForm.value = { id: null, name: "", email: "", role_id: "" };
};

// Refresh View
const refreshView = async () => {
  try {
    router.post(route("admin.refresh"));
  } catch (err) {
    error.value = "Failed to refresh the materialized view.";
    console.error(err);
  }
};

// Function to format date
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString();
};

// Function to get action color
const getActionColor = (action) => {
  switch (action) {
    case "INSERT":
      return "text-green-600";
    case "UPDATE":
      return "text-blue-600";
    case "DELETE":
      return "text-red-600";
    default:
      return "text-gray-600";
  }
};

// Store State and Functions
const showAddModal = ref(false);
const showEditModal = ref(false);
const currentStore = ref({
  id: null,
  name: "",
  description: "",
  company: "",
  store_hours: "",
});

const resetCurrentStore = () => {
  currentStore.value = {
    id: null,
    name: "",
    description: "",
    company: "",
    store_hours: "",    
  };
};

const addStore = async () => {
  try {
    console.log("Adding Store:", currentStore.value);

    await router.post(route("admin.addStore"), {
      name: currentStore.value.name,
      description: currentStore.value.description,
      company: currentStore.value.company,
      store_hours: currentStore.value.store_hours,
    });

    alert("Store added successfully.");

    showAddModal.value = false;
    resetCurrentStore();
  } catch (error) {
    if (error.response && error.response.data.errors) {
      alert("Validation errors occurred.");
      console.error(error.response.data.errors);
    } else {
      alert("An error occurred while adding the store.");
      console.error(error);
    }
  }
};

const removeStore = (storeId) => {
  if (confirm("Are you sure you want to delete this store?")) {
    router.delete(route("admin.deleteStore", { storeId }), {
      onSuccess: () => {
        alert("Store deleted successfully.");
      },
      onError: (errors) => {
        console.error(errors);
        alert("There was an error deleting the store.");
      },
    });
  }
};




















// State for modals and current menu item
const showAddMenuModal = ref(false);
const showEditMenuModalFlag = ref(false);
const currentMenuItem = ref({ id: null, name: "", store: "", price: null });

// Function to add a menu item
const addMenuItem = () => {
  if (
    currentMenuItem.value.name &&
    currentMenuItem.value.store &&
    currentMenuItem.value.price
  ) {
    const newItem = {
      ...currentMenuItem.value,
      id: Date.now(),
    };
    menuItems.value.push(newItem);
    resetCurrentMenuItem();
    showAddMenuModal.value = false;
  }
};

// Function to show the edit menu modal
const showEditMenuModal = (menu) => {
  currentMenuItem.value = { ...menu };
  showEditMenuModalFlag.value = true;
};

// Function to update a menu item
const updateMenuItem = () => {
  const index = menuItems.value.findIndex(
    (item) => item.id === currentMenuItem.value.id
  );
  if (index !== -1) {
    menuItems.value.splice(index, 1, { ...currentMenuItem.value });
  }
  resetCurrentMenuItem();
  showEditMenuModalFlag.value = false;
};

// Function to remove a menu item
const removeMenuItem = (id) => {
  menuItems.value = menuItems.value.filter((item) => item.id !== id);
};

// Function to reset the current menu item
const resetCurrentMenuItem = () => {
  currentMenuItem.value = { id: null, name: "", store: "", price: null };
};
</script>

<template>
  <Head title="Menu List" />
  <AuthenticatedLayout>
    <template #header>
      <h2
        class="text-xl font-semibold leading-tight text-gray-800 flex items-center justify-between"
      >
        <span>User Management</span>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search Users..."
          class="px-4 py-2 w-64 border rounded-md text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
      </h2>
    </template>
    <div class="min-h-screen bg-gray-100 py-6 flex justify-center">
      <div class="max-w-7xl w-full mx-auto flex gap-6">
        <!-- Profile Section -->
        <div
          class="w-64 bg-white shadow-lg rounded-lg p-6 sticky top-6 self-start"
          style="height: calc(100vh - 2rem)"
        >
          <div class="flex flex-col items-center">
            <img
              :src="user.avatar"
              :alt="user.name"
              class="w-20 h-20 rounded-full border-2 border-blue-500 mb-4"
            />
            <h2 class="text-xl font-semibold text-gray-800">
              {{ $page.props.auth.user.name }}
            </h2>
            <p class="text-sm text-gray-600">
              {{ $page.props.auth.user.email }}
            </p>
            <p class="mt-2 text-sm font-medium text-blue-600">
              {{ getRole($page.props.auth.user.role_id) }}
            </p>
          </div>
        </div>

        <!-- Main Content -->
        <div class="flex-grow space-y-6">
          <!-- Stores Management -->

          <div
            class="bg-white shadow-lg rounded-lg p-6 border-t-4 border-blue-500"
          >
            <h2 class="text-xl font-semibold text-blue-700 mb-4">Stores</h2>
            <ul v-if="foodstores.length > 0" class="space-y-4">
              <li
                v-for="store in foodstores"
                :key="store.id"
                class="flex items-center justify-between border-b pb-2"
              >
                <span class="text-blue-700">{{ store.name }}</span>
                <div class="flex space-x-2">
                  <button
                    @click="removeStore(store.id)"
                    class="px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600"
                  >
                    Remove
                  </button>
                </div>
              </li>
            </ul>
            <button
              @click="showAddModal = true"
              class="mt-4 px-3 py-1 bg-green-500 text-white rounded-md hover:bg-green-600"
            >
              Add Store
            </button>
          </div>
          <!-- Add Store Modal -->
          <div
            v-if="showAddModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center"
          >
            <div class="bg-white p-6 rounded-lg shadow-lg w-1/3">
              <h3 class="text-lg font-semibold mb-4">Add New Store</h3>
              <form @submit.prevent="addStore">
                <div class="mb-4">
                  <label class="block text-gray-700">Name</label>
                  <input
                    v-model="currentStore.name"
                    type="text"
                    class="w-full px-3 py-2 border rounded-md"
                    required
                  />
                </div>
                <div class="mb-4">
                  <label class="block text-gray-700">Description</label>
                  <textarea
                    v-model="currentStore.description"
                    class="w-full px-3 py-2 border rounded-md"
                    required
                  ></textarea>
                </div>
                <div class="mb-4">
                  <label class="block text-gray-700">Company</label>
                  <input
                    v-model="currentStore.company"
                    type="text"
                    class="w-full px-3 py-2 border rounded-md"
                    required
                  />
                </div>
                <div class="mb-4">
                  <label class="block text-gray-700">Store Hours</label>
                  <input
                    v-model="currentStore.store_hours"
                    type="text"
                    class="w-full px-3 py-2 border rounded-md"
                    required
                  />
                </div>
                <div class="flex justify-end space-x-2">
                  <button
                    type="button"
                    @click="showAddModal = false"
                    class="px-3 py-1 bg-gray-300 rounded-md hover:bg-gray-400"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    class="px-3 py-1 bg-green-500 text-white rounded-md hover:bg-green-600"
                  >
                    Add
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Menu Section -->
          <div
            class="bg-white shadow-lg rounded-lg p-6 border-t-4 border-blue-500"
          >
            <h2 class="text-xl font-semibold text-blue-700 mb-4">Menus</h2>
            <ul v-if="menu.length > 0" class="space-y-4">
              <li
                v-for="item in menu"
                :key="item.menu_item_id"
                class="flex items-center justify-between border-b pb-2"
              >
                <div>
                  <p class="font-semibold text-blue-700">{{ item.menu_item_name }}</p>
                  <p class="text-sm text-gray-600">Store: {{ item.food_store_name }}</p>
                  <p class="text-sm text-gray-600">
                    Price: {{ item.menu_item_price }} Php
                  </p>
                </div>
                <div class="flex space-x-2">
                  <button
                    @click="showEditMenuModal(menu)"
                    class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                  >
                    Edit
                  </button>
                  <button
                    @click="removeMenuItem(menu.id)"
                    class="px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600"
                  >
                    Remove
                  </button>
                </div>
              </li>
            </ul>
            <button
              @click="showAddMenuModal = true"
              class="mt-4 px-3 py-1 bg-green-500 text-white rounded-md hover:bg-green-600"
            >
              Add Menu Item
            </button>
          </div>

          <!-- Add Menu Item Modal -->
          <div
            v-if="showAddMenuModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center"
          >
            <div class="bg-white p-6 rounded-lg shadow-lg w-1/3">
              <h3 class="text-lg font-semibold mb-4">Add New Menu Item</h3>
              <form @submit.prevent="addMenuItem">
                <div class="mb-4">
                  <label class="block text-gray-700">Name</label>
                  <input
                    v-model="currentMenuItem.name"
                    type="text"
                    class="w-full px-3 py-2 border rounded-md"
                    required
                  />
                </div>
                <div class="mb-4">
                  <label class="block text-gray-700">Store</label>
                  <select
                    v-model="currentMenuItem.store"
                    class="w-full px-3 py-2 border rounded-md"
                    required
                  >
                    <option
                      v-for="store in stores"
                      :key="store.id"
                      :value="store.name"
                    >
                      {{ store.name }}
                    </option>
                  </select>
                </div>
                <div class="mb-4">
                  <label class="block text-gray-700">Price</label>
                  <input
                    v-model="currentMenuItem.price"
                    type="number"
                    step="0.01"
                    class="w-full px-3 py-2 border rounded-md"
                    required
                  />
                </div>
                <div class="flex justify-end space-x-2">
                  <button
                    type="button"
                    @click="showAddMenuModal = false"
                    class="px-3 py-1 bg-gray-300 rounded-md hover:bg-gray-400"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    class="px-3 py-1 bg-green-500 text-white rounded-md hover:bg-green-600"
                  >
                    Add
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Edit Menu Item Modal -->
          <div
            v-if="showEditMenuModalFlag"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center"
          >
            <div class="bg-white p-6 rounded-lg shadow-lg w-1/3">
              <h3 class="text-lg font-semibold mb-4">Edit Menu Item</h3>
              <form @submit.prevent="updateMenuItem">
                <div class="mb-4">
                  <label class="block text-gray-700">Name</label>
                  <input
                    v-model="currentMenuItem.name"
                    type="text"
                    class="w-full px-3 py-2 border rounded-md"
                    required
                  />
                </div>
                <div class="mb-4">
                  <label class="block text-gray-700">Store</label>
                  <select
                    v-model="currentMenuItem.store"
                    class="w-full px-3 py-2 border rounded-md"
                    required
                  >
                    <option
                      v-for="store in stores"
                      :key="store.id"
                      :value="store.name"
                    >
                      {{ store.name }}
                    </option>
                  </select>
                </div>
                <div class="mb-4">
                  <label class="block text-gray-700">Price</label>
                  <input
                    v-model="currentMenuItem.price"
                    type="number"
                    step="0.01"
                    class="w-full px-3 py-2 border rounded-md"
                    required
                  />
                </div>
                <div class="flex justify-end space-x-2">
                  <button
                    type="button"
                    @click="showEditMenuModalFlag = false"
                    class="px-3 py-1 bg-gray-300 rounded-md hover:bg-gray-400"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                  >
                    Save Changes
                  </button>
                </div>
              </form>
            </div>
          </div>
          <!-- User Management -->
          <div class="bg-white shadow-lg rounded-lg p-6">
            <h1 class="text-2xl font-semibold mb-6 text-gray-800">
              User Management
            </h1>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th
                      class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                    >
                      Name
                    </th>
                    <th
                      class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                    >
                      Email
                    </th>
                    <th
                      class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                    >
                      Role
                    </th>
                    <th
                      class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                    >
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="userView in filteredUsers" :key="userView.id">
                    <td class="px-6 py-4 whitespace-nowrap">
                      {{ userView.name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      {{ userView.email }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      {{ getRole(userView.role_id) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <button
                        v-if="getRole(userView.role_id) !== 'admin'"
                        @click="editUser(userView)"
                        class="text-indigo-600 hover:text-indigo-900 mr-2"
                      >
                        <EditIcon class="h-5 w-5" />
                      </button>
                      <span
                        v-else
                        class="d-flex text-gray-500 cursor-not-allowed"
                        title="Admins cannot be deleted"
                      >
                        <EditIcon class="h-5 w-5" />
                      </span>
                      <button
                        v-if="getRole(userView.role_id) !== 'admin'"
                        @click="deleteUser(userView.id)"
                        class="text-red-600 hover:text-red-900"
                      >
                        <TrashIcon class="h-5 w-5" />
                      </button>
                      <span
                        v-else
                        class="d-flex text-gray-500 cursor-not-allowed"
                        title="Admins cannot be deleted"
                      >
                        <TrashIcon class="h-5 w-5" />
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Activity Logs -->
          <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">
              Activity Logs
            </h2>
            <button
              @click="refreshView"
              class="px-4 py-3 bg-gradient-to-r from-green-500 to-green-700 text-white text-sm font-bold rounded-lg shadow-lg hover:from-green-600 hover:to-green-800 transition-transform transform hover:scale-105 flex items-center"
            >
              Refresh
            </button>
            <div v-if="actlogs.length === 0" class="text-gray-500">
              No activity recorded yet.
            </div>
            <ul v-else class="space-y-4">
              <li
                v-for="log in actlogs"
                :key="log.log_id"
                class="border-b pb-4"
              >
                <div class="flex items-center justify-between">
                  <span
                    :class="['font-semibold', getActionColor(log.action)]"
                    >{{ log.action }}</span
                  >
                  <span class="text-sm text-gray-500">{{
                    formatDate(log.time)
                  }}</span>
                </div>
                <p class="text-gray-700">User ID: {{ log.users_id }}</p>
                <p class="text-gray-700">User name: {{ log.username }}</p>
                <p class="text-gray-700">Table: {{ log.table_name }}</p>
                <p class="text-gray-700">User Type: {{ log.role }}</p>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div
      v-if="showEditUserModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 sm:p-8"
    >
      <div class="bg-white p-6 sm:p-8 rounded-lg shadow-xl max-w-md w-full">
        <h3 class="text-lg sm:text-xl font-semibold mb-4">Edit user</h3>
        <form @submit.prevent="submitUser" class="space-y-4">
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700"
              >Name</label
            >
            <input
              type="text"
              id="name"
              v-model="userForm.name"
              :disabled="!!editingUser"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-100"
            />
          </div>
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700"
              >Email</label
            >
            <input
              type="email"
              id="email"
              v-model="userForm.email"
              :disabled="!!editingUser"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-100"
            />
          </div>
          <div>
            <label for="role" class="block text-sm font-medium text-gray-700"
              >Role</label
            >
            <select
              id="role"
              v-model="userForm.role_id"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring-indigo-200"
            >
              <option
                v-for="role in props.roles"
                :key="role.id"
                :value="role.id"
              >
                {{ role.user_type }}
              </option>
            </select>
          </div>
          <div class="flex justify-between items-center mt-4">
            <button
              @click="closeModal"
              type="button"
              class="text-gray-500 hover:text-gray-800"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="bg-indigo-600 text-white hover:bg-indigo-700 px-4 py-2 rounded-lg"
            >
              Submit
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
/* Add any additional styles here */
</style>



