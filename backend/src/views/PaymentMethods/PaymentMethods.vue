<template>
  <div class="flex items-center justify-between mb-3">
    <h1 class="text-3xl font-semibold">Payment Methods</h1>
    <button type="button"
            @click="showAddNewModal()"
            class="py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
      Add new Payment Method
    </button>
  </div>
  <PaymentMethodsTable @clickEdit="editMethod"/>
  <PaymentMethodModal v-model="showMethodModal" :paymentMethod="methodModel" @close="onModalClose"/>
</template>

<script setup>
import {computed, ref} from "vue";
import store from "../../store";
import PaymentMethodsTable from "./PaymentMethodsTable.vue";
import PaymentMethodModal from "./PaymentMethodModal.vue";

const DEFAULT_METHOD = {
  id: '',
  name: '',
  code: '',
  description: '',
  details: '',
  is_active: true,
  sort_order: 0,
}

const paymentMethods = computed(() => store.state.paymentMethods);
const methodModel = ref({...DEFAULT_METHOD})
const showMethodModal = ref(false);

function showAddNewModal() {
  methodModel.value = {...DEFAULT_METHOD}
  showMethodModal.value = true
}

function editMethod(m) {
  methodModel.value = m;
  showMethodModal.value = true;
}

function onModalClose() {
  methodModel.value = {...DEFAULT_METHOD}
}
</script>

<style scoped>

</style>