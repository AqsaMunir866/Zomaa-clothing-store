<template>
  <div class="flex items-center justify-between mb-3">
    <h1 class="text-3xl font-semibold">Banners</h1>
    <button type="button"
            @click="showAddNewModal()"
            class="py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
    >
      Add new Banner
    </button>
  </div>
  <BannersTable @clickEdit="editBanner"/>
  <BannerModal v-model="showBannerModal" :banner="bannerModel" @close="onModalClose"/>
</template>

<script setup>
import {computed, ref} from "vue";
import BannerModal from "./BannerModal.vue";
import BannersTable from "./BannersTable.vue";

const DEFAULT_BANNER = {
  id: '',
  title: '',
  kicker: '',
  text: '',
  image: '',
  link_label: '',
  link_url: '',
  position: 'hero',
  sort_order: 0,
  active: true,
}

const bannerModel = ref({...DEFAULT_BANNER})
const showBannerModal = ref(false);

function showAddNewModal() {
  showBannerModal.value = true
}

function editBanner(b) {
  bannerModel.value = b;
  showAddNewModal();
}

function onModalClose() {
  bannerModel.value = {...DEFAULT_BANNER}
}
</script>