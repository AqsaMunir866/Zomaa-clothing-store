<template>
  <div class="bg-white p-4 rounded-lg shadow animate-fade-in-down">
    <div class="flex justify-between border-b-2 pb-3">
      <div class="flex items-center">
        <span class="ml-3">Found {{banners.data.length}} banners</span>
      </div>
    </div>

    <table class="table-auto w-full">
      <thead>
      <tr>
        <th class="text-left p-2">ID</th>
        <th class="text-left p-2">Image</th>
        <th class="text-left p-2">Title</th>
        <th class="text-left p-2">Position</th>
        <th class="text-left p-2">Sort</th>
        <th class="text-left p-2">Active</th>
        <th class="text-left p-2">Actions</th>
      </tr>
      </thead>
      <tbody v-if="banners.loading || !banners.data.length">
      <tr>
        <td colspan="7">
          <Spinner v-if="banners.loading"/>
          <p v-else class="text-center py-8 text-gray-700">
            There are no banners
          </p>
        </td>
      </tr>
      </tbody>
      <tbody v-else>
      <tr v-for="(banner, index) of banners.data">
        <td class="border-b p-2">{{ banner.id }}</td>
        <td class="border-b p-2">
          <img v-if="banner.image" :src="banner.image" :alt="banner.title"
               class="w-16 h-12 object-cover rounded">
          <span v-else class="text-gray-400 text-sm">No image</span>
        </td>
        <td class="border-b p-2 max-w-[260px]">
          <p class="font-medium">{{ banner.title }}</p>
          <p v-if="banner.kicker" class="text-xs text-gray-500">{{ banner.kicker }}</p>
        </td>
        <td class="border-b p-2">
          <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium"
                :class="banner.position === 'hero' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-700'">
            {{ banner.position }}
          </span>
        </td>
        <td class="border-b p-2">{{ banner.sort_order }}</td>
        <td class="border-b p-2">{{ banner.active ? 'Yes' : 'No' }}</td>
        <td class="border-b p-2">
          <Menu as="div" class="relative inline-block text-left">
            <div>
              <MenuButton
                class="inline-flex items-center justify-center w-full justify-center rounded-full w-10 h-10 bg-black bg-opacity-0 text-sm font-medium text-white hover:bg-opacity-5 focus:bg-opacity-5 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75"
              >
                <DotsVerticalIcon
                  class="h-5 w-5 text-indigo-500"
                  aria-hidden="true"/>
              </MenuButton>
            </div>

            <transition
              enter-active-class="transition duration-100 ease-out"
              enter-from-class="transform scale-95 opacity-0"
              enter-to-class="transform scale-100 opacity-100"
              leave-active-class="transition duration-75 ease-in"
              leave-from-class="transform scale-100 opacity-100"
              leave-to-class="transform scale-95 opacity-0"
            >
              <MenuItems
                class="absolute z-10 right-0 mt-2 w-32 origin-top-right divide-y divide-gray-100 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
              >
                <div class="px-1 py-1">
                  <MenuItem v-slot="{ active }">
                    <button
                      :class="[
                        active ? 'bg-indigo-600 text-white' : 'text-gray-900',
                        'group flex w-full items-center rounded-md px-2 py-2 text-sm',
                      ]"
                      @click="editBanner(banner)"
                    >
                      <PencilIcon
                        :active="active"
                        class="mr-2 h-5 w-5 text-indigo-400"
                        aria-hidden="true"
                      />
                      Edit
                    </button>
                  </MenuItem>
                  <MenuItem v-slot="{ active }">
                    <button
                      :class="[
                        active ? 'bg-indigo-600 text-white' : 'text-gray-900',
                        'group flex w-full items-center rounded-md px-2 py-2 text-sm',
                      ]"
                      @click="deleteBanner(banner)"
                    >
                      <TrashIcon
                        :active="active"
                        class="mr-2 h-5 w-5 text-indigo-400"
                        aria-hidden="true"
                      />
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
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {DotsVerticalIcon, PencilIcon, TrashIcon} from '@heroicons/vue/outline'

const banners = computed(() => store.state.banners);

const emit = defineEmits(['clickEdit'])

onMounted(() => {
  getBanners();
})

function getBanners() {
  store.dispatch("getBanners");
}

function deleteBanner(banner) {
  if (!confirm(`Are you sure you want to delete the banner "${banner.title}"?`)) {
    return
  }
  store.dispatch('deleteBanner', banner)
    .then(res => {
      store.commit('showToast', 'Banner was successfully deleted');
      store.dispatch('getBanners')
    })
}

function editBanner(b) {
  emit('clickEdit', b)
}
</script>