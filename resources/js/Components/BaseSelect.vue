<script setup>
defineProps({
  modelValue: [String, Number],
  label: String,
  name: String,
  options: Array,
  optionLabel: { type: String, default: "label" },
  optionValue: { type: String, default: "value" },
  required: Boolean,
})

defineEmits(["update:modelValue"])
</script>

<template>
  <div class="sm:col-span-1">
    <label :for="name" class="block text-sm font-medium text-primary-txt">
      {{ label }}
    </label>

    <div class="mt-2">
      <select
        :id="name"
        :name="name"
        :required="required"
        class="h-7 block w-full rounded-md bg-white px-3 py-1.5 text-base text-primary-txt
              outline outline-1 -outline-offset-1 outline-primary-only placeholder:text-gray-400
              focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary sm:text-sm/6"
        :value="modelValue"
        @change="$emit('update:modelValue', $event.target.value)"
      >
        <option disabled value="">Choisir ici</option>

        <!-- Si options = [{id:1, slt_region:'Dakar'}] -->
        <option
          v-for="opt in options"
          :key="opt[optionValue]"
          :value="opt[optionValue]"
        >
          {{ opt[optionLabel] }}
        </option>
      </select>
    </div>
  </div>
</template>
