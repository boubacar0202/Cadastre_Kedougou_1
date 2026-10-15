<script setup>
import { ref, watch } from "vue"
import MazBtn from "maz-ui/components/MazBtn"
import { router } from '@inertiajs/vue3';
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  title: {
    type: String,
    default: "Veuillez entrer votre code d'accès !",
  },
})

const emit = defineEmits(["update:modelValue", "confirm"])

const code = ref("")
const showPassword = ref(false)

watch(
  () => props.modelValue,
  (val) => {
    if (!val) {
      code.value = ""
      showPassword.value = false
    }
  }
)

const confirm = () => {
  emit("confirm", code.value)
}

const close = () => {
  if (window.history.length > 1) {
    window.history.back()
    return
  }

  router.visit(route("dashboard"))
}

const togglePassword = () => {
  showPassword.value = !showPassword.value
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
        <!-- Input avec toggle à l'intérieur -->
        <div class="relative mb-6">
          <input
            v-model="code"
            :type="showPassword ? 'text' : 'password'"
            autocomplete="off"
            placeholder="Entrez votre code d'accès"
            class="h-10 w-full rounded-lg px-3 text-primary-txt
                   outline outline-1 outline-primary-only
                   focus:outline-2 focus:outline-primary-only
                   pr-12"
            @keyup.enter="confirm"
          />
          <button
            type="button"
            @click="togglePassword"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-primary-txt/60 hover:text-primary"
          >
            <EyeIcon v-if="!showPassword" class="h-5 w-5" />
            <EyeSlashIcon v-else class="h-5 w-5" />
          </button>
        </div>

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