<script setup>
const props = defineProps({
  modelValue: [String, Number],
  label: String,
  name: String,
  type: { type: String, default: "text" },
  required: Boolean,
  placeholder: String,
  max: String,
  min: String,
  maxlength: [String, Number],
  minlength: [String, Number],
  autocomplete: { type: String, default: "null" },
  readonly: Boolean,
  error: String,
})

const emit = defineEmits(["update:modelValue", "input", "change"])
</script>

<template>
  <div class="sm:col-span-1">
    <label :for="name" class="block text-sm font-medium text-primary-txt">
      {{ label }}
    </label>

    <div class="mt-2">
      <input
        :type="type"
        :name="name"
        :id="name"
        :readonly="readonly"
        :placeholder="placeholder"
        :required="required"
        :max="max"
        :min="min"
        :maxlength="maxlength"
        :minlength="minlength"
        :autocomplete="autocomplete ?? 'on'"
        class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt
              outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400
              focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
        :value="type !== 'file' ? modelValue : null"

        @input="type !== 'file' ? $emit('update:modelValue', $event.target.value) : null"
        @change="type === 'file' ? $emit('change', $event) : null"
      />
    </div>

    <span v-if="error" class="text-red-500 text-sm mt-1">{{ error }}</span>
  </div>
</template>
