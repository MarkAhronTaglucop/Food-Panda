<script setup>
import { ref, computed } from "vue";
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
  users: Array,
  roles: Array,
  allorders: Array
});

// Reactive state for users
const searchQuery = ref("");
const users = ref([...props.users]);
//to get the role based on user id
const getRole = (roleId) => {
  const role = props.roles.find((role) => role.id === roleId);
  return role ? role.user_type : "Unknown";
};
// Computed filtered users
const filteredUsers = computed(() => {
  if (!searchQuery.value) return users.value;
  return users.value.filter((user) =>
    user.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});
//user-profile state
const user = ref({
  avatar: "/images/image.png",
});

const menuItems = ref([]);
const cart = ref([]);

const orderLogs = ref([
  {
    id: 1,
    date: "2024-12-19 10:00 AM",
    total: 25.5,
    status: "Pending",
  },
  {
    id: 2,
    date: "2024-12-18 02:30 PM",
    total: 40.0,
    status: "Accepted",
  },
  {
    id: 3,
    date: "2024-12-17 09:15 AM",
    total: 15.75,
    status: "Denied",
  },
]);

const activityLogs = ref([
  {
    id: 1,
    orderId: 2,
    action: "Accepted",
    date: "2024-12-18 02:31 PM",
  },
  {
    id: 2,
    orderId: 3,
    action: "Denied",
    date: "2024-12-17 09:16 AM",
  },
]);

const filteredMenuItems = computed(() => {
  return menuItems.value.filter(
    (item) =>
      item.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      item.store.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

const acceptOrder = (orderId) => {
  const order = orderLogs.value.find((log) => log.id === orderId);
  if (order) {
    order.status = "Accepted";
    logAction(orderId, "Accepted");
  }
};

const denyOrder = (orderId) => {
  const order = orderLogs.value.find((log) => log.id === orderId);
  if (order) {
    order.status = "Denied";
    logAction(orderId, "Denied");
  }
};

const logAction = (orderId, action) => {
  const logEntry = {
    id: activityLogs.value.length + 1,
    orderId,
    action,
    date: new Date().toLocaleString(),
  };
  activityLogs.value.push(logEntry);
};
</script>
<template>
  <Head title="Order Management" />
  <AuthenticatedLayout>
    <template #header>
      <h2
        class="text-xl font-semibold leading-tight text-blue-800 flex items-center justify-between"
      >
        <span>Order Management</span>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search Menu..."
          class="px-4 py-2 w-64 border border-blue-300 rounded-md text-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </h2>
    </template>

    <div class="min-h-screen bg-blue-50 py-6 flex justify-center">
      <div class="max-w-6xl w-full mx-auto flex gap-6">
        <!-- Profile Section -->
        <div
          class="w-64 bg-blue-100 shadow-lg rounded-lg p-6 sticky top-6 self-start"
          style="height: calc(100vh - 2rem)"
        >
          <div class="flex flex-col items-center">
            <img
              :src="user.avatar"
              :alt="user.name"
              class="w-20 h-20 rounded-full border-4 border-blue-500 mb-4"
            />
            <h2 class="text-xl font-semibold text-blue-900">
              {{ $page.props.auth.user.name }}
            </h2>
            <p class="text-sm text-blue-700">
              {{ $page.props.auth.user.email }}
            </p>
            <p class="mt-2 text-sm font-medium text-blue-600">
              {{ getRole($page.props.auth.user.role_id) }}
            </p>
          </div>
        </div>

        <!-- Main Content -->
        <div class="flex-grow space-y-6">
          <!-- Order Requests -->
          <div class="bg-white shadow-lg rounded-lg p-6 border border-blue-200">
            <h2 class="text-xl font-semibold mb-4 text-blue-800">Order Requests</h2>
            <ul v-if="allorders.length > 0" class="space-y-4">
              <li
                v-for="log in allorders"
                :key="log.order_id"
                class="border-b pb-4 border-blue-300"
              >
                <p class="font-semibold text-blue-800">
                  Order #{{ log.order_id }} - {{ log.created_at }}
                </p>
                <p class="text-blue-700">Orders: {{ log.items }}</p>
                <p class="text-blue-600">Status: {{ log.current_status }}</p>
                <div class="flex space-x-2 mt-2" v-if="log.current_status === 'Pending'">
                  <button
                    @click="acceptOrder(log.order_id)"
                    class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                  >
                    Accept
                  </button>
                  <button
                    @click="denyOrder(log.order_id)"
                    class="px-3 py-1 bg-blue-700 text-white rounded-md hover:bg-blue-800"
                  >
                    Deny
                  </button>
                </div>
              </li>
            </ul>
            <p v-else class="text-blue-600">No orders placed yet.</p>
          </div>
          <!-- Order Logs -->
          <div class="bg-white shadow-lg rounded-lg p-6 border border-blue-200">
            <h2 class="text-xl font-semibold mb-4 text-blue-800">Order Logs</h2>
            <ul v-if="orderLogs.length > 0" class="space-y-4">
              <li
                v-for="log in orderLogs"
                :key="log.id"
                class="border-b pb-4 border-blue-300"
              >
                <p class="font-semibold text-blue-800">
                  Order #{{ log.id }} - {{ log.date }}
                </p>
                <p class="text-blue-700">Total: ${{ log.total.toFixed(2) }}</p>
                <p class="text-blue-600">Status: {{ log.status }}</p>
                <div class="flex space-x-2 mt-2" v-if="log.status === 'Pending'">
                  <button
                    @click="acceptOrder(log.id)"
                    class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                  >
                    Accept
                  </button>
                  <button
                    @click="denyOrder(log.id)"
                    class="px-3 py-1 bg-blue-700 text-white rounded-md hover:bg-blue-800"
                  >
                    Deny
                  </button>
                </div>
              </li>
            </ul>
            <p v-else class="text-blue-600">No orders placed yet.</p>
          </div>

          <!-- Activity Logs -->
          <div class="bg-white shadow-lg rounded-lg p-6 border border-blue-200">
            <h2 class="text-xl font-semibold mb-4 text-blue-800">Activity Logs</h2>
            <ul v-if="activityLogs.length > 0" class="space-y-4">
              <li v-for="log in activityLogs" :key="log.id" class="text-blue-600">
                <p>
                  Action #{{ log.id }}: Order {{ log.orderId }} was {{ log.action }} on
                  {{ log.date }}
                </p>
              </li>
            </ul>
            <p v-else class="text-blue-600">No activity logs available.</p>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
