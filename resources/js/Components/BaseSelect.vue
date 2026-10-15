<script setup>
  import { computed } from "vue"

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

  function onChange(event) {
    const raw = event.target.value
    if (raw === "") {
      emit("update:modelValue", "")
      return
    }

    const match = normalizedOptions.value.find((option) => String(option.value) === raw)
    let value = match ? match.value : raw
    if (props.modelModifiers.number && value !== "" && value !== null && value !== undefined) {
      value = Number(value)
    }
    emit("update:modelValue", value)
  }
</script>

<template>
  <div :class="wrapperClass">
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
      <select
        :id="selectId"
        :name="name"
        :required="required"
        :disabled="disabled"
        :value="modelValue"
        :aria-label="label || placeholder || undefined"
        :class="[
          'h-10 block w-full rounded-xl bg-white px-3.5 pr-10 py-2 text-base text-primary-txt',
          'border border-gray-300 shadow-sm appearance-none cursor-pointer',
          'transition-all duration-200 ease-in-out',
          'hover:border-gray-400 hover:shadow',
          'focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 focus:shadow-md',
          'disabled:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-70',
          inputClass,
          customError
            ? 'border-red-500 focus:ring-red-500/40 focus:border-red-500'
            : ''
        ]"
        @change="onChange"
      >
        <option v-if="placeholder" value="" :disabled="required">
          {{ placeholder }}
        </option>
        <option
          v-for="option in normalizedOptions"
          :key="option.key"
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select>

      <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-primary-txt">
        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
      </div>
    </div>

    <p v-if="customError" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
      <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0zm-8-4a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
      </svg>
      {{ customError }}
    </p>
  </div>
</template>
