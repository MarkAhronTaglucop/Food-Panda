<template>
  <Head title="Menu List" />
  <AuthenticatedLayout>
    <template #header>
      <h2
        class="text-xl font-semibold leading-tight text-blue-700 flex items-center justify-between"
      >
        <span>Menu List</span>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search Menu..."
          class="px-4 py-2 w-64 border rounded-md text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </h2>
    </template>
    <div class="min-h-screen bg-blue-50 py-6 flex justify-center">
      <div class="max-w-6xl w-full mx-auto flex gap-6">
        <!-- Profile Section (now on the left with full height) -->
        <div
          class="w-64 bg-blue-100 shadow-lg rounded-lg p-6 sticky top-6 self-start"
          style="height: 100vh"
        >
          <div class="flex flex-col items-center">
            <img
              :src="user.avatar"
              :alt="user.name"
              class="w-20 h-20 rounded-full border-2 border-blue-500 mb-4"
            />
            <h2 class="text-xl font-semibold text-blue-800">
              {{ $page.props.auth.user.name }}
            </h2>
            <p class="text-sm text-blue-600">
              {{ $page.props.auth.user.email }}
            </p>
            <p class="mt-2 text-sm font-medium text-blue-500">
              {{ getRole($page.props.auth.user.role_id) }}
            </p>
          </div>
        </div>

        <!-- Main Content -->
        <div class="flex-grow space-y-6">
          <!-- Menu -->
          <div ref="menuSection" class="bg-white shadow-lg rounded-lg p-6">
            <h1 class="text-2xl font-semibold text-blue-700 mb-6">
              Store Menu
            </h1>
            <div v-if="isLoading">
              <p>Loading menu items...</p>
            </div>
            <div
              v-else
              class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
            >
              <div
                v-for="item in menu"
                :key="item.menu_item_id"
                class="border rounded-lg p-4 flex justify-between items-center hover:bg-blue-50"
              >
                <div>
                  <h3 class="font-semibold text-blue-800">
                    {{ item.menu_item_name }}
                  </h3>
                  <p class="text-sm text-blue-600">
                    {{ item.food_store_name }}
                  </p>
                  <p class="font-medium text-blue-700">
                    ${{ item.menu_item_price }}
                  </p>
                </div>
                <button
                  @click="addToCart(item)"
                  class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                >
                  Add to Cart
                </button>
              </div>
            </div>
          </div>

          <!-- Cart -->
          <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-semibold text-blue-700 mb-4">Your Cart</h2>
            <div v-if="cart.length === 0" class="text-blue-500">
              Your cart is empty.
            </div>
            <div v-else class="space-y-4">
              <div
                v-for="item in cart"
                :key="item.id"
                class="flex justify-between items-center"
              >
                <div>
                  <h3 class="font-semibold text-blue-800">{{ item.name }}</h3>
                  <p class="text-sm text-blue-600">
                    {{ item.store }} - ${{ item.price.toFixed(2) }} x
                    {{ item.quantity }}
                  </p>
                </div>
                <div class="flex items-center space-x-2">
                  <button
                    @click="removeFromCart(item.id)"
                    class="p-1 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                  >
                    <MinusIcon class="w-4 h-4" />
                  </button>
                  <span class="text-blue-800">{{ item.quantity }}</span>
                  <button
                    @click="addToCart(item)"
                    class="p-1 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                  >
                    <PlusIcon class="w-4 h-4" />
                  </button>
                </div>
              </div>
              <div class="pt-4 border-t">
                <p class="font-bold text-blue-800">
                  Total: ${{ calculateTotal().toFixed(2) }}
                </p>
                <button
                  @click="openOrderModal"
                  class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 w-full flex items-center justify-center"
                >
                  <ShoppingCartIcon class="w-5 h-5 mr-2" />
                  Place Order
                </button>
              </div>
            </div>
          </div>

          <!-- Modal -->
          <div
            v-if="showModal"
            class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center"
          >
            <div class="bg-white p-6 rounded-lg shadow-lg w-96">
              <h3 class="text-lg font-bold mb-4">Order Details</h3>
              <div class="mb-4">
                <label
                  for="remarks"
                  class="block text-sm font-medium text-gray-700"
                  >Remarks</label
                >
                <textarea
                  id="remarks"
                  v-model="orderDetails.remarks"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                ></textarea>
              </div>
              <div class="mb-4">
                <label
                  for="delivery_address"
                  class="block text-sm font-medium text-gray-700"
                  >Delivery Address</label
                >
                <input
                  type="text"
                  id="delivery_address"
                  v-model="orderDetails.delivery_address"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
              <div class="mb-4">
                <label
                  for="payment_methods"
                  class="block text-sm font-medium text-gray-700"
                  >Payment Methods</label
                >
                <select
                  id="payment_methods"
                  v-model="orderDetails.payment_methods"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="credit_card">Credit Card</option>
                  <option value="cash_on_delivery">Cash</option>
                  <option value="online_banking">Online Banking</option>
                </select>
              </div>
              <div class="flex justify-end space-x-2">
                <button
                  @click="closeOrderModal"
                  class="px-4 py-2 text-gray-600 hover:text-gray-800"
                >
                  Cancel
                </button>
                <button
                  @click="confirmOrder"
                  class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
                >
                  Confirm
                </button>
              </div>
            </div>
          </div>

          <!-- Order Logs -->
          <div class="bg-white shadow-lg rounded-lg p-6">
            <h2 class="text-xl font-semibold text-blue-700 mb-4">Order Logs</h2>
            <div v-if="orderLogs.length === 0" class="text-blue-500">
              No orders placed yet.
            </div>
            <ul v-else class="space-y-4">
              <li
                v-for="log in orderLogs"
                :key="log.id"
                class="border-b pb-4 hover:bg-blue-50"
              >
                <p class="font-semibold text-blue-800">
                  Order #{{ log.id }} - {{ log.date }}
                </p>
                <p class="text-blue-700">Total: ${{ log.total.toFixed(2) }}</p>
                <ul class="mt-2 space-y-1">
                  <li
                    v-for="item in log.items"
                    :key="item.id"
                    class="text-sm text-blue-600"
                  >
                    {{ item.name }} ({{ item.store }}) x
                    {{ item.quantity }} (${{
                      (item.price * item.quantity).toFixed(2)
                    }})
                  </li>
                </ul>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { ref, onMounted, computed } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { PlusIcon, MinusIcon, ShoppingCartIcon } from "lucide-vue-next";

const showModal = ref(false);
const orderDetails = ref({
  remarks: "",
  delivery_address: "",
  payment_methods: "",
});

const page = usePage();

const cart = ref([]);

const addToCart = (item) => {
  const existingItem = cart.value.find(
    (cartItem) => cartItem.id === item.menu_item_id
  );
  if (existingItem) {
    existingItem.quantity++;
  } else {
    cart.value.push({
      id: item.menu_item_id,
      name: item.menu_item_name,
      price: item.menu_item_price,
      store: item.food_store_name,
      quantity: 1,
    });
  }
  console.log("Cart updated:", cart.value);
};

const removeFromCart = (itemId) => {
  const index = cart.value.findIndex((item) => item.id === itemId);
  if (index !== -1) {
    if (cart.value[index].quantity > 1) {
      cart.value[index].quantity--;
    } else {
      cart.value.splice(index, 1);
    }
  }
  console.log("Cart updated:", cart.value);
};

const calculateTotal = () => {
  return cart.value.reduce(
    (total, item) => total + item.price * item.quantity,
    0
  );
};

const openOrderModal = () => {
  showModal.value = true;
};

const closeOrderModal = () => {
  showModal.value = false;
};

const confirmOrder = () => {
  if (
    !orderDetails.value.delivery_address ||
    !orderDetails.value.payment_methods
  ) {
    alert("Please fill in all fields.");
    return;
  }

  const payload = {
    user_id: page.props.auth.user.id, // Replace with the logged-in user ID
    menu_item_ids: cart.value.map((item) => item.id),
    quantities: cart.value.map((item) => item.quantity),
    remarks: orderDetails.value.remarks || "",
    payment_method: orderDetails.value.payment_methods,
    delivery_address: orderDetails.value.delivery_address,
  };

  console.log("Payload being sent:", payload);

  router.post("/user-dashboard/placeorder", payload, {
    onSuccess: () => {
      console.log("Order placed successfully!");
      alert("Order placed successfully!");
      closeOrderModal();
      cart.value = []; // Clear cart after order placement
    },
    onError: (errors) => {
      console.error("Error placing order:", errors);
      alert("Failed to place order. Please check the details and try again.");
    },
  });
};

const props = defineProps({
  users: Array,
  roles: Array,
  menu: Array,
});

const isLoading = ref(true);
const menuSection = ref(null);

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
const orderLogs = ref([]);
const profileHeight = ref("auto");

// Mock API call to fetch menu items
const fetchMenuItems = () => {
  return new Promise((resolve) => {
    setTimeout(() => {
      resolve([]);
    }, 1000);
  });
};

onMounted(async () => {
  menuItems.value = await fetchMenuItems();
  isLoading.value = false;

  const savedLogs = localStorage.getItem("orderLogs");
  if (savedLogs) {
    orderLogs.value = JSON.parse(savedLogs);
  }

  // Set initial profile height
  updateProfileHeight();

  // Update profile height on window resize
  window.addEventListener("resize", updateProfileHeight);
});

const updateProfileHeight = () => {
  if (menuSection.value) {
    profileHeight.value = `${menuSection.value.offsetHeight}px`;
  }
};

const placeOrder = () => {
  if (cart.value.length === 0) {
    alert("Please add items to your cart before placing an order.");
    return;
  }

  const newOrder = {
    id: Date.now(),
    items: [...cart.value],
    total: calculateTotal(),
    date: new Date().toLocaleString(),
  };

  orderLogs.value = [newOrder, ...orderLogs.value].slice(0, 5);
  localStorage.setItem("orderLogs", JSON.stringify(orderLogs.value));

  console.log("Order placed:", newOrder);
  alert("Order placed successfully!");
  cart.value = [];
};
</script>