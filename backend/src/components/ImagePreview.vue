<template>
  <div class="flex flex-wrap gap-1">
    <Sortable
      :list="items"
      item-key="key"
      class="flex gap-1 flex-wrap"
      @end="onImageDragEnd"
    >
      <template #item="{element: image}">
        <div
          class="relative w-[120px] h-[120px] rounded border border-dashed flex items-center justify-center hover:border-purple-500 overflow-hidden">
          <img :src="image.url" class="max-w-full max-h-full" :class="image.deleted ? 'opacity-50' : ''">
          <small v-if="image.deleted"
                 class="absolute left-0 bottom-0 right-0 py-1 px-2 bg-black w-100 text-white justify-between items-center flex">
            To be deleted
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                 stroke="currentColor" class="w-4 h-4 cursor-pointer" @click="revertImage(image)">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>
            </svg>
          </small>
          <span v-else class="absolute top-1 right-1 cursor-pointer" @click="removeImage(image)">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
          <path
            d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
        </svg>
      </span>
        </div>
      </template>
    </Sortable>
    <div
      class="relative w-[120px] h-[120px] rounded border border-dashed flex items-center justify-center hover:border-purple-500 overflow-hidden">
      <span>
        Upload
      </span>
      <input type="file" class="absolute left-0 top-0 bottom-0 right-0 w-full h-full opacity-0"
             @change="onFileChange" multiple>
    </div>
  </div>
</template>

<script setup>
// Imports
import {Sortable} from "sortablejs-vue3";
import {ref, watch} from "vue";

// Props & Emit
const props = defineProps({
  images: {type: Array, default: () => []},
})

const emit = defineEmits(['update:files', 'update:deleted-images', 'update:image-order'])

// Refs
const items = ref([])
let nextKey = 0

// Methods
function onFileChange($event) {
  const chosenFiles = [...$event.target.files];
  $event.target.value = '';
  if (!chosenFiles.length) {
    return;
  }

  const uploaded = [];
  let pending = chosenFiles.length;

  chosenFiles.forEach((file) => {
    readFile(file).then((url) => {
      uploaded.push({
        key: `new-${nextKey++}`,
        file,
        url,
        isExisting: false,
        deleted: false,
      });

      if (--pending === 0) {
        // An upload is meant to be the new cover, so the photo it displaces is
        // queued for deletion instead of living on as a second "main" image.
        uploaded.forEach(() => {
          const displaced = items.value.find(im => im.isExisting && !im.deleted);
          if (displaced) {
            displaced.deleted = true;
          }
        });

        items.value = [...uploaded, ...items.value];
        sync();
      }
    });
  });
}

function readFile(file) {
  return new Promise((resolve, reject) => {
    const fileReader = new FileReader()
    fileReader.readAsDataURL(file)
    fileReader.onload = () => {
      resolve(fileReader.result)
    }
    fileReader.onerror = reject
  })
}

function removeImage(image) {
  if (image.isExisting) {
    image.deleted = true;
  } else {
    items.value = items.value.filter(item => item !== image);
  }

  sync();
}

function revertImage(image) {
  image.deleted = false;
  sync();
}

function onImageDragEnd(ev) {
  const {newIndex, oldIndex} = ev;
  const [tmp] = items.value.splice(oldIndex, 1);
  items.value.splice(newIndex, 0, tmp);
  sync();
}

/**
 * The form shows the images in display order, so that order is what the server
 * receives. Deleted photos are simply absent from it and get removed on save.
 */
function sync() {
  const kept = items.value.filter(im => !im.deleted);
  const files = kept.filter(im => !im.isExisting).map(im => im.file);
  const order = kept.map(im => im.isExisting ? `existing:${im.id}` : `new:${files.indexOf(im.file)}`);

  emit('update:files', files);
  emit('update:deleted-images', items.value.filter(im => im.isExisting && im.deleted).map(im => im.id));
  emit('update:image-order', order);
}

// Hooks
watch(() => props.images, (images) => {
  items.value = (images || [])
    .filter(im => im && im.id && im.url)
    .map(im => ({
      key: `existing-${im.id}`,
      id: im.id,
      url: im.url,
      path: im.path,
      isExisting: true,
      deleted: false,
    }));

  sync();
}, {immediate: true, deep: true});
</script>

<style scoped>

</style>
