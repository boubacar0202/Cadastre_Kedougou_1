<script setup>
  import { computed, onBeforeUnmount, onMounted, ref } from "vue"

  const props = defineProps({
    modelValue: [String, Number],
    label: String,
    name: String,
    id: String,
    options: { type: Array, default: () => [] },
    optionLabel: { type: String, default: "label" },
    optionValue: { type: String, default: "value" },
    required: Boolean,
    disabled: Boolean,
    customError: String,
    placeholder: { type: String, default: "" },
    fontSize: { type: String, default: "0.875rem" },
    wrapperClass: { type: String, default: "sm:col-span-1" },
    inputClass: { type: String, default: "" },
    modelModifiers: { type: Object, default: () => ({}) },
  })

  const emit = defineEmits(["update:modelValue"])

  const ouvert = ref(false)
  const racine = ref(null)
  const selectId = computed(() => props.id || props.name || undefined)

  const normalizedOptions = computed(() =>
    (props.options || []).map((option, index) => {
      if (option !== null && typeof option === "object") {
        const value = option[props.optionValue]
        return {
          label: option[props.optionLabel] ?? value,
          value,
          key: `${value ?? "option"}-${index}`,
        }
      }

      return {
        label: option,
        value: option,
        key: `${option ?? "option"}-${index}`,
      }
    })
  )

  const optionChoisie = computed(() =>
    normalizedOptions.value.find((option) => String(option.value) === String(props.modelValue ?? ""))
  )

  const libelleAffiche = computed(() => optionChoisie.value?.label || props.placeholder || "Sélectionner")

  function choisir(option) {
    if (props.disabled) return
    let value = option.value
    if (props.modelModifiers.number && value !== "" && value !== null && value !== undefined) {
      value = Number(value)
    }
    emit("update:modelValue", value)
    ouvert.value = false
  }

  function basculer() {
    if (props.disabled) return
    ouvert.value = !ouvert.value
  }

  function fermerSiDehors(event) {
    if (!racine.value?.contains(event.target)) ouvert.value = false
  }

  onMounted(() => document.addEventListener("pointerdown", fermerSiDehors))
  onBeforeUnmount(() => document.removeEventListener("pointerdown", fermerSiDehors))
</script>

<template>
  <div ref="racine" :class="wrapperClass">
    <label
      v-if="label"
      :for="selectId"
      class="block text-sm font-medium text-primary-txt mb-2 transition-colors duration-200"
      :style="{ fontSize: fontSize }"
    >
      {{ label }}
      <span v-if="required" class="text-red-500 ml-0.5">*</span>
    </label>

    <div :class="['relative', label ? 'mt-2' : '']">
      <button
        :id="selectId"
        type="button"
        :disabled="disabled"
        :aria-expanded="ouvert"
        :aria-label="label || placeholder || undefined"
        :class="[
          'flex h-10 w-full items-center justify-between rounded-xl bg-white px-3.5 text-left text-base text-primary-txt',
          'border border-gray-300 shadow-sm cursor-pointer',
          'transition-all duration-200 ease-in-out',
          'hover:border-gray-400 hover:shadow',
          'focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary',
          'disabled:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-70',
          !optionChoisie ? 'text-primary-txt/50' : '',
          inputClass,
          customError ? 'border-red-500 focus:ring-red-500/40 focus:border-red-500' : ''
        ]"
        @click="basculer"
      >
        <span class="truncate">{{ libelleAffiche }}</span>
        <svg class="h-4 w-4 shrink-0 text-primary-txt" :class="{ 'rotate-180': ouvert }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
      </button>

      <ul
        v-if="ouvert"
        class="mt-1 max-h-64 overflow-y-auto rounded-xl border border-primary-only bg-white py-1 shadow-lg"
        role="listbox"
      >
        <li v-for="option in normalizedOptions" :key="option.key">
          <button
            type="button"
            class="flex w-full items-center px-3.5 py-2 text-left text-sm text-primary-txt hover:bg-primary-form"
            :class="{ 'bg-primary-form font-semibold text-primary': String(option.value) === String(modelValue ?? '') }"
            role="option"
            :aria-selected="String(option.value) === String(modelValue ?? '')"
            @click="choisir(option)"
          >
            {{ option.label }}
          </button>
        </li>
      </ul>
    </div>

    <p v-if="customError" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
      <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0zm-8-4a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
      </svg>
      {{ customError }}
    </p>
  </div>
</template>
