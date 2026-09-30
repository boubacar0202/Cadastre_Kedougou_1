<script setup>
  defineProps({
    modelValue: [String, Number],
    label: String,
    name: String,
    options: Array,
    optionLabel: { type: String, default: "label" },
    optionValue: { type: String, default: "value" },
    required: Boolean,
    customError: String,
    fontSize: { type: String, default: "0.875rem" },
  })

  defineEmits(["update:modelValue"])
</script>

<template>
  <div class="sm:col-span-1">
    <label
      :for="name"
      class="block text-sm font-medium text-primary-txt mb-2 transition-colors duration-200"
      :style="{ fontFamily: 'Arial, sans-serif', fontWeight: 'bold-medium', fontSize: fontSize }"
    >
      {{ label }}
      <span v-if="required" class="text-red-500 ml-0.5">*</span>
    </label>

    <div class="mt-2 relative">
      <select
        :id="name"
        :name="name"
        :required="required"
        :value="modelValue"
        :class="[
          'h-10 block w-full rounded-xl bg-white px-3.5 pr-10 py-2 text-base text-primary-dark',
          'border border-gray-300 shadow-sm appearance-none cursor-pointer',
          'transition-all duration-200 ease-in-out',
          'hover:border-gray-400 hover:shadow',
          'focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 focus:shadow-md',
          'disabled:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-70',
          customError
            ? 'border-red-500 focus:ring-red-500/40 focus:border-red-500'
            : ''
        ]"
        @change="$emit('update:modelValue', $event.target.value)"
      >
        <option v-if="!modelValue" value="" disabled selected>
          
        </option>
        <option
          v-for="option in options"
          :key="option[optionValue]"
          :value="option[optionValue]"
        >
          {{ option[optionLabel] }}
        </option>
      </select>

      <!-- Flèche personnalisée -->
      <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500">
        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
      </div>
    </div>

    <!-- Message d'erreur -->
    <p v-if="customError" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
      <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0zm-8-4a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
      </svg>
      {{ customError }}
    </p>
  </div>
</template>