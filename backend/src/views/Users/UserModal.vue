<!-- This example requires Tailwind CSS v2.0+ -->
<template>
  <TransitionRoot as="template" :show="show">
    <Dialog as="div" class="relative z-10" @close="closeModal">
      <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100"
                       leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
        <div class="fixed inset-0 bg-black bg-opacity-70 transition-opacity"/>
      </TransitionChild>

      <div class="fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-end sm:items-center justify-center min-h-full p-4 text-center sm:p-0">
          <TransitionChild as="template" enter="ease-out duration-300"
                           enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                           enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200"
                           leave-from="opacity-100 translate-y-0 sm:scale-100"
                           leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            <DialogPanel
              class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-[700px] sm:w-full">
              <Spinner v-if="loading"
                       class="absolute left-0 top-0 bg-white right-0 bottom-0 flex items-center justify-center"/>
              <header class="py-3 px-4 flex justify-between items-center">
                <DialogTitle as="h3" class="text-lg leading-6 font-medium text-gray-900">
                  {{ user.id ? `Update user: "${props.user.name}"` : 'Create new User' }}
                </DialogTitle>
                <button
                  type="button"
                  @click="closeModal()"
                  class="w-8 h-8 flex items-center justify-center rounded-full transition-colors cursor-pointer hover:bg-[rgba(0,0,0,0.2)]"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                </button>
              </header>
              <form @submit.prevent="onSubmit">
                <div class="bg-white px-4 pt-5 pb-4">
                  <div v-if="errors.general && errors.general.length"
                       class="mb-4 rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700"
                       role="alert">
                    <p v-for="(message, index) of errors.general" :key="`general-${index}`">{{ message }}</p>
                  </div>
                  <CustomInput class="mb-2" v-model="user.name" label="Name" :errors="errors.name"/>
                  <CustomInput class="mb-2" v-model="user.email" label="Email" :errors="errors.email"/>
                  <CustomInput type="password" class="mb-2" v-model="user.password"
                               :label="user.id ? 'Password (leave blank to keep current)' : 'Password'"
                               :errors="errors.password"/>
                  <p v-if="!user.id" class="text-xs text-gray-500">
                    At least 8 characters, including letters, numbers and a symbol.
                  </p>
                </div>
                <footer class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                  <button type="submit"
                          :disabled="loading"
                          class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm
                          text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ loading ? 'Saving…' : 'Submit' }}
                  </button>
                  <button type="button"
                          class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                          @click="closeModal" ref="cancelButtonRef">
                    Cancel
                  </button>
                </footer>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import {computed, ref, watch} from 'vue'
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from '@headlessui/vue'
import CustomInput from "../../components/core/CustomInput.vue";
import store from "../../store/index.js";
import Spinner from "../../components/core/Spinner.vue";

const props = defineProps({
  modelValue: Boolean,
  user: {
    required: true,
    type: Object,
  }
})

const emit = defineEmits(['update:modelValue', 'close'])

const loading = ref(false)
const errors = ref({})
const user = ref({id: '', name: '', email: '', password: ''})

const show = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

/**
 * Build the editable copy from the prop. Keep `password` blank: the admin
 * must never see (and re-submit) an existing password hash.
 */
function resetForm() {
  user.value = {
    id: props.user.id || '',
    name: props.user.name || '',
    email: props.user.email || '',
    password: '',
  }
  errors.value = {}
}

// Reset whenever the modal opens (and when the selected user changes) instead
// of on every re-render, which used to wipe values while the admin was typing.
watch(show, (open) => {
  if (open) {
    resetForm()
  }
}, {immediate: true})

watch(() => props.user, () => {
  if (show.value) {
    resetForm()
  }
})

function closeModal() {
  if (loading.value) {
    return
  }
  show.value = false
  errors.value = {}
  emit('close')
}

function errorMessageFor(error) {
  const response = error && error.response

  if (!response) {
    return 'Could not reach the server. Please check your connection and try again.'
  }

  if (response.status === 422 && response.data && response.data.errors) {
    return null
  }

  if (response.status === 401) {
    return 'Your session has expired. Please sign in again.'
  }

  if (response.status === 419) {
    return 'Your session expired. Please refresh the page and try again.'
  }

  if (response.status >= 500) {
    return 'The server could not save the user. Please try again in a moment.'
  }

  return (response.data && response.data.message) || 'Something went wrong. Please try again.'
}

function onSubmit() {
  if (loading.value) {
    return
  }

  loading.value = true
  errors.value = {}

  const payload = {
    name: user.value.name,
    email: user.value.email,
  }
  if (user.value.password) {
    payload.password = user.value.password
  }

  const request = user.value.id
    ? store.dispatch('updateUser', {...payload, id: user.value.id})
    : store.dispatch('createUser', payload)

  request
    .then(() => {
      loading.value = false
      store.dispatch('getUsers')
      closeModal()
    })
    .catch((error) => {
      const response = error && error.response
      if (response && response.status === 422 && response.data && response.data.errors) {
        errors.value = response.data.errors
      } else {
        errors.value = {general: [errorMessageFor(error)]}
      }
    })
    .finally(() => {
      loading.value = false
    })
}
</script>
