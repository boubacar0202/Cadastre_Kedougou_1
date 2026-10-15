<script setup>
  import { computed } from "vue"

  const props = defineProps({
    modelValue: [String, Number],
    label: String,
    name: String,
    id: String,
    inputClass: { type: String, default: "" },
    type: { type: String, default: "text" },
    required: Boolean,
    placeholder: String,
    max: [String, Number],
    min: [String, Number],
    step: [String, Number],
    maxlength: [String, Number],
    minlength: [String, Number],
    autocomplete: { type: String, default: "null" },
    accept: String,
    readonly: Boolean,
    error: String,
    customError: String,
    fontSize: { type: String, default: "0.875rem" },
  })

  const emit = defineEmits(["update:modelValue", "input", "change", "blur"])

  const inputId = computed(() => props.id || props.name || undefined)

  function onInput(event) {
    if (props.type === "file") return
    emit("update:modelValue", event.target.value)
    emit("input", event)
  }

  const inputInlineStyle = computed(() => ({
    fontSize: props.fontSize,
    fontWeight: props.inputClass.includes("font-bold") ? "700" : "500",
    fontStyle: props.inputClass.includes("italic") ? "italic" : "normal",
    color: "#66380a",
  }))
</script>

<template>
  <div class="sm:col-span-1">
    <label
      :for="inputId"
      class="block text-sm font-medium text-primary-txt mb-2 transition-colors duration-200"
      :style="{ fontSize: fontSize }"
    >
      {{ label }}
      <span v-if="required" class="text-red-500 ml-0.5">*</span>
    </label>

    <div class="mt-2 relative">
      <input
        :type="type"
        :name="name"
        :id="inputId"
        :readonly="readonly"
        :placeholder="placeholder"
        :required="required"
        :max="max"
        :min="min"
        :step="step"
        :maxlength="maxlength"
        :minlength="minlength"
        :autocomplete="autocomplete ?? 'on'"
        :accept="accept"
        :class="[
          'h-10 block w-full rounded-xl bg-white px-3.5 py-2 text-base text-primary-txt',
          $slots.append ? 'pr-10' : '',
          'border border-gray-300 shadow-sm',
          'transition-all duration-200 ease-in-out',
          'placeholder:text-primary-txt/50 placeholder:font-normal',
          'hover:border-gray-400 hover:shadow',
          'focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 focus:shadow-md',
          'read-only:bg-gray-50 read-only:cursor-default read-only:focus:ring-0 read-only:focus:border-gray-300',
          'disabled:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-70',
          inputClass,
          type === 'file' ? 'file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-form file:text-primary hover:file:bg-primary-only file:cursor-pointer cursor-pointer py-1.5' : '',
          (error || customError)
            ? 'border-red-500 focus:ring-red-500/40 focus:border-red-500'
            : ''
        ]"
        :style="inputInlineStyle"
        :value="type !== 'file' ? modelValue : null"
        @input="onInput"
        @change="type === 'file' ? emit('change', $event) : null"
        @blur="emit('blur', $event)"
      />
      <slot name="append" />
    </div>

    <!-- Message d'erreur -->
    <p v-if="error || customError" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
      <svg class="h-3.5 w-3.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0zm-8-4a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
      </svg>
      <span :style="{ fontSize: fontSize }">
        {{ error || customError }}
      </span>
    </p>
  </div>
</template>