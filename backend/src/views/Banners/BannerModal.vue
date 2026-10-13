<template>
  <!-- This example requires Tailwind CSS v2.0+ -->
  <TransitionRoot as="template" :show="show">
    <Dialog as="div" class="relative z-10" @close="show = false">
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
                  {{ isEdit ? `Update Banner: "${banner.title}"` : 'Create new Banner' }}
                </DialogTitle>
                <button
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
                <div class="bg-white px-4 pt-5 pb-4 grid sm:grid-cols-2 gap-3">
                  <CustomInput class="sm:col-span-2" v-model="banner.title" label="Title" :errors="errors['title']"/>
                  <CustomInput v-model="banner.kicker" label="Kicker (small caption)" :errors="errors['kicker']"/>
                  <CustomInput type="select"
                               :select-options="positionOptions"
                               v-model="banner.position"
                               label="Position" :errors="errors['position']"/>
                  <CustomInput type="textarea" class="sm:col-span-2" v-model="banner.text" label="Text / Description"
                               :errors="errors['text']"/>
                  <CustomInput v-model="banner.link_label" label="Button label (e.g. Shop Now)"
                               :errors="errors['link_label']"/>
                  <CustomInput v-model="banner.link_url" label="Button URL (e.g. /category/women)"
                               :errors="errors['link_url']"/>
                  <CustomInput type="number" v-model="banner.sort_order" label="Sort order" :errors="errors['sort_order']"/>
                  <div class="flex items-center">
                    <CustomInput type="checkbox" v-model="banner.active" label="Active" :errors="errors['active']"/>
                  </div>

                  <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Banner image (1920&times;800 hero, 1200&times;675 split)</label>
                    <div class="flex items-center gap-4">
                      <div class="relative w-56 h-32 rounded border border-dashed flex items-center justify-center hover:border-purple-500 overflow-hidden bg-gray-50">
                        <img v-if="previewUrl" :src="previewUrl" class="w-full h-full object-cover">
                        <span v-else class="text-sm text-gray-400">No image</span>
                      </div>
                      <div>
                        <input
                          type="file"
                          accept="image/jpeg,image/png,image/webp"
                          class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                          @change="onFileChange">
                        <small v-if="errors['image']" class="text-red-600">{{ errors['image'][0] }}</small>
                      </div>
                    </div>
                  </div>
                </div>
                <footer class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                  <button type="submit"
                          class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm
                          text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500">
                    Submit
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
import {computed, onUpdated, ref} from 'vue'
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from '@headlessui/vue'
import CustomInput from "../../components/core/CustomInput.vue";
import store from "../../store/index.js";
import Spinner from "../../components/core/Spinner.vue";

const loading = ref(false)
const errors = ref({})
const selectedFile = ref(null)
const previewUrl = ref('')

const props = defineProps({
  modelValue: Boolean,
  banner: {
    required: true,
    type: Object,
  }
})

const banner = ref({...props.banner})

const emit = defineEmits(['update:modelValue', 'close'])

const show = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const isEdit = computed(() => !!banner.value.id)

const positionOptions = [
  {key: 'hero', text: 'Hero slider (homepage top)'},
  {key: 'split', text: 'Split banner (mid page)'},
]

onUpdated(() => {
  banner.value = {...props.banner}
  previewUrl.value = props.banner.image || ''
  selectedFile.value = null
})

function onFileChange($event) {
  const file = $event.target.files[0]
  if (!file) return
  selectedFile.value = file
  const reader = new FileReader()
  reader.onload = (e) => {
    previewUrl.value = e.target.result
  }
  reader.readAsDataURL(file)
}

function closeModal() {
  show.value = false
  emit('close')
  errors.value = {};
  selectedFile.value = null
  previewUrl.value = props.banner.image || ''
}

function onSubmit() {
  loading.value = true

  const form = new FormData()
  form.append('title', banner.value.title || '')
  form.append('kicker', banner.value.kicker || '')
  form.append('text', banner.value.text || '')
  form.append('link_label', banner.value.link_label || '')
  form.append('link_url', banner.value.link_url || '')
  form.append('position', banner.value.position || 'hero')
  form.append('sort_order', banner.value.sort_order ?? 0)
  form.append('active', banner.value.active ? 1 : 0)
  if (selectedFile.value) {
    form.append('image', selectedFile.value)
  }

  if (banner.value.id) {
    store.dispatch('updateBanner', {...banner.value, _form: form})
      .then(response => {
        loading.value = false;
        if (response.status === 200) {
          store.commit('showToast', 'Banner was successfully updated');
          store.dispatch('getBanners')
          closeModal()
        }
      })
      .catch(err => {
        loading.value = false;
        errors.value = err.response.data.errors
      })
  } else {
    store.dispatch('createBanner', form)
      .then(response => {
        loading.value = false;
        if (response.status === 201) {
          store.commit('showToast', 'Banner was successfully created');
          store.dispatch('getBanners')
          closeModal()
        }
      })
      .catch(err => {
        loading.value = false;
        errors.value = err.response.data.errors
      })
  }
}
</script>