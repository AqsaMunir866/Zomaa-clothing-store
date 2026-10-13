<template>
  <div class="bg-white p-4 rounded-lg shadow animate-fade-in-down">
    <div class="flex justify-between border-b-2 pb-3">
      <div class="flex items-center">
        <span class="ml-3">Found {{paymentMethods.data.length}} payment methods</span>
      </div>
    </div>

    <table class="table-auto w-full">
      <thead>
      <tr>
        <TableHeaderCell field="name">
          Name
        </TableHeaderCell>
        <TableHeaderCell field="code">
          Code
        </TableHeaderCell>
        <TableHeaderCell field="description">
          Description
        </TableHeaderCell>
        <TableHeaderCell field="is_active">
          Active
        </TableHeaderCell>
        <TableHeaderCell field="sort_order">
          Sort
        </TableHeaderCell>
        <TableHeaderCell field="actions">
          Actions
        </TableHeaderCell>
      </tr>
      </thead>
      <tbody v-if="paymentMethods.loading || !paymentMethods.data.length">
      <tr>
        <td colspan="6">
          <Spinner v-if="paymentMethods.loading"/>
          <p v-else class="text-center py-8 text-gray-700">
            There are no payment methods
          </p>
        </td>
      </tr>
      </tbody>
      <tbody v-else>
      <tr v-for="(m, index) of paymentMethods.data">
        <td class="border-b p-2 font-medium">{{ m.name }}</td>
        <td class="border-b p-2">
          <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-xs">{{ m.code }}</span>
        </td>
        <td class="border-b p-2 max-w-[280px] text-sm text-gray-600">{{ m.description }}</td>
        <td class="border-b p-2">{{ m.is_active ? 'Yes' : 'No' }}</td>
        <td class="border-b p-2">{{ m.sort_order }}</td>
        <td class="border-b p-2">
          <Menu as="div" class="relative inline-block text-left">
            <div>
              <MenuButton
                class="inline-flex items-center justify-center w-full justify-center rounded-full w-10 h-10 bg-black bg-opacity-0 text-sm font-medium text-white hover:bg-opacity-5 focus:bg-opacity-5 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75">
                <DotsVerticalIcon class="h-5 w-5 text-indigo-500" aria-hidden="true"/>
              </MenuButton>
            </div>

            <transition
              enter-active-class="transition duration-100 ease-out"
              enter-from-class="transform scale-95 opacity-0"
              enter-to-class="transform scale-100 opacity-100"
              leave-active-class="transition duration-75 ease-in"
              leave-from-class="transform scale-100 opacity-100"
              leave-to-class="transform scale-95 opacity-0">
              <MenuItems
                class="absolute z-10 right-0 mt-2 w-32 origin-top-right divide-y divide-gray-100 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                <div class="px-1 py-1">
                  <MenuItem v-slot="{ active }">
                    <button
                      :class="[active ? 'bg-indigo-600 text-white' : 'text-gray-900',
                               'group flex w-full items-center rounded-md px-2 py-2 text-sm']"
                      @click="emit('clickEdit', m)">
                      <PencilIcon :active="active" class="mr-2 h-5 w-5 text-indigo-400" aria-hidden="true"/>
                      Edit
                    </button>
                  </MenuItem>
                  <MenuItem v-slot="{ active }">
                    <button
                      :class="[active ? 'bg-indigo-600 text-white' : 'text-gray-900',
                               'group flex w-full items-center rounded-md px-2 py-2 text-sm']"
                      @click="deleteMethod(m)">
                      <TrashIcon :active="active" class="mr-2 h-5 w-5 text-indigo-400" aria-hidden="true"/>
                      Delete
                    </button>
                  </MenuItem>
                </div>
              </MenuItems>
            </transition>
          </Menu>
        </td>
      </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import {computed, onMounted} from "vue";
import store from "../../store";
import Spinner from "../../components/core/Spinner.vue";
import TableHeaderCell from "../../components/core/Table/TableHeaderCell.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {DotsVerticalIcon, PencilIcon, TrashIcon} from '@heroicons/vue/outline'
import PaymentMethodModal from "./PaymentMethodModal.vue";

const emit = defineEmits(['clickEdit'])

const paymentMethods = computed(() => store.state.paymentMethods);

onMounted(() => {
  store.dispatch('getPaymentMethods')
})

function deleteMethod(m) {
  if (!confirm(`Are you sure you want to delete "${m.name}"?`)) {
    return
  }
  store.dispatch('deletePaymentMethod', m)
    .then(() => {
      store.commit('showToast', 'Payment method was successfully deleted');
      store.dispatch('getPaymentMethods')
    })
}
</script>

<style scoped>

</style>