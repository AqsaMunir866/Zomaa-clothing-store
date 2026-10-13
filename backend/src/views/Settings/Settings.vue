<template>
  <div class="flex items-center justify-between mb-3">
    <h1 class="text-3xl font-semibold">Settings</h1>
    <button type="button"
            @click="onSubmit"
            :disabled="loading"
            class="py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
    >
      Save Changes
    </button>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- General info -->
    <div class="bg-white p-6 rounded-lg shadow animate-fade-in-down lg:col-span-2">
      <h2 class="text-lg font-medium text-gray-900 border-b pb-3 mb-4">General Business Information</h2>
      <div class="grid sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Business Name</label>
          <input type="text" v-model="form.business_name"
                 class="block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm rounded-md">
          <small v-if="errors['business_name']" class="text-red-600">{{ errors['business_name'][0] }}</small>
        </div>
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Tagline</label>
          <textarea v-model="form.tagline" rows="2"
                    class="block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm rounded-md"></textarea>
          <small v-if="errors['tagline']" class="text-red-600">{{ errors['tagline'][0] }}</small>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input type="email" v-model="form.email"
                 class="block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm rounded-md">
          <small v-if="errors['email']" class="text-red-600">{{ errors['email'][0] }}</small>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
          <input type="text" v-model="form.phone"
                 class="block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm rounded-md">
          <small v-if="errors['phone']" class="text-red-600">{{ errors['phone'][0] }}</small>
        </div>
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Primary Address</label>
          <textarea v-model="form.primary_address" rows="3"
                    class="block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm rounded-md"></textarea>
          <small v-if="errors['primary_address']" class="text-red-600">{{ errors['primary_address'][0] }}</small>
        </div>
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Announcement bar text</label>
          <input type="text" v-model="form.announcement"
                 class="block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm rounded-md">
          <small v-if="errors['announcement']" class="text-red-600">{{ errors['announcement'][0] }}</small>
        </div>
      </div>
    </div>

    <!-- Logo -->
    <div class="bg-white p-6 rounded-lg shadow animate-fade-in-down">
      <h2 class="text-lg font-medium text-gray-900 border-b pb-3 mb-4">Logo</h2>

      <div class="relative w-full h-40 rounded border border-dashed flex items-center justify-center hover:border-purple-500 overflow-hidden bg-gray-50 mb-3">
        <img v-if="logoPreview" :src="logoPreview" class="max-h-full max-w-full object-contain">
        <span v-else class="text-sm text-gray-400">No logo uploaded</span>
      </div>

      <input
        type="file"
        accept="image/jpeg,image/png,image/webp,image/svg+xml"
        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
        @change="onLogoChange">
      <small v-if="errors['logo']" class="text-red-600">{{ errors['logo'][0] }}</small>

      <label class="flex items-center mt-3 text-sm text-gray-700 cursor-pointer">
        <input type="checkbox" v-model="removeLogo" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded mr-2">
        Remove current logo
      </label>

      <p class="mt-4 text-xs text-gray-400">
        The logo appears next to the business name in the storefront header, and in the footer.
      </p>
    </div>
  </div>
</template>

<script setup>
import {computed, onMounted, ref} from "vue";
import store from "../../store";

const loading = ref(false)
const errors = ref({})
const logoFile = ref(null)
const logoPreview = ref('')
const removeLogo = ref(false)

const settings = computed(() => store.state.settings)

const form = ref({
  business_name: '',
  tagline: '',
  email: '',
  phone: '',
  primary_address: '',
  announcement: '',
})

onMounted(() => {
  store.dispatch('getSettings').then(() => {
    form.value = {
      business_name: settings.value.data.business_name || '',
      tagline: settings.value.data.tagline || '',
      email: settings.value.data.email || '',
      phone: settings.value.data.phone || '',
      primary_address: settings.value.data.primary_address || '',
      announcement: settings.value.data.announcement || '',
    }
    logoPreview.value = settings.value.data.logo_url || ''
  })
})

function onLogoChange($event) {
  const file = $event.target.files[0]
  if (!file) return
  logoFile.value = file
  removeLogo.value = false
  const reader = new FileReader()
  reader.onload = (e) => {
    logoPreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

function onSubmit() {
  loading.value = true
  errors.value = {}

  const formData = new FormData()
  formData.append('business_name', form.value.business_name || '')
  formData.append('tagline', form.value.tagline || '')
  formData.append('email', form.value.email || '')
  formData.append('phone', form.value.phone || '')
  formData.append('primary_address', form.value.primary_address || '')
  formData.append('announcement', form.value.announcement || '')
  if (logoFile.value) {
    formData.append('logo', logoFile.value)
  }
  formData.append('remove_logo', removeLogo.value ? 1 : 0)
  formData.append('_method', 'PUT')

  store.dispatch('updateSettings', formData)
    .then(response => {
      loading.value = false;
      if ([200, 201, 202].includes(response.status)) {
        store.commit('showToast', 'Settings were successfully updated');
        logoFile.value = null
        removeLogo.value = false
      }
    })
    .catch(err => {
      loading.value = false;
      errors.value = err.response.data.errors
    })
}
</script>