<script setup>
import { ref, watch } from "vue"
import MazBtn from "maz-ui/components/MazBtn"
import { router } from '@inertiajs/vue3';

const props = defineProps({
  modelValue: { type: Boolean, default: false }, // ouvrir / fermer
  loading: { type: Boolean, default: false },
  title: {
    type: String,
    default: "Veuillez entrer votre code d'accès !",
  },
})

const emit = defineEmits(["update:modelValue", "confirm"])

const code = ref("")

watch(
  () => props.modelValue,
  (val) => {
    if (!val) code.value = ""
  }
)
  
// const close = () => {
//   emit("update:modelValue", false)
// }

const confirm = () => {
  emit("confirm", code.value)
}

const close = () => {
  router.visit(document.referrer || '/')
}
</script>

<template>
  <div
    v-if="modelValue"
    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50"
  >
    <div class="bg-white w-full max-w-md rounded-2xl shadow-lg">
      
      <!-- Header -->
      <div class="p-4 bg-primary rounded-t-2xl">
        <h1 class="text-xl text-center text-white font-semibold">
          {{ title }}
        </h1>
      </div>

      <!-- Body -->
      <div class="p-6">
        <input
          v-model="code"
          type="password"
          autocomplete="off"
          placeholder="Entrez votre code d'accès"
          class="h-10 w-full rounded-lg px-3 text-primary-txt
                 outline outline-1 outline-primary-only
                 focus:outline-2 focus:outline-primary-only mb-6"
          @keyup.enter="confirm"
        />

        <div class="flex justify-center gap-4">
          <MazBtn
            size="large"
            :loading="loading"
            class="w-32 h-10 rounded-xl"
            color="secondary"
            @click="close"
          >
            Annuler
          </MazBtn>

          <MazBtn
            size="large"
            :loading="loading"
            class="w-32 h-10 rounded-xl"
            color="primary"
            @click="confirm"
          >
            Confirmer
          </MazBtn>
        </div>
      </div>

    </div>
  </div>
</template>
