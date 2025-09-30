<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue'

const showPassword = ref(false)
const showPasswordConfirmer = ref(false)

const togglePassword = () => {
  showPassword.value = !showPassword.value 
}

const togglePasswordConfirmer = () => {
    showPasswordConfirmer.value = !showPasswordConfirmer.value
}
 
const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Pénom et Nom" class="text-primary" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full h-10 text-lg px-3 py-2 rounded-md bg-white text-base text-gray-900 
                        outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 
                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary-dark sm:text-sm/6"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Adresse Email" class="text-primary" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full h-10 text-lg px-3 py-2 rounded-md bg-white text-base text-gray-900 
                        outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 
                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary-dark sm:text-sm/6"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Mot de passe" class="text-primary"/>

                <div class="flex items-center mt-1 relative">   
                    <TextInput
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        class="mt-1 block w-full h-10 text-lg px-3 py-2 rounded-md bg-white text-base text-gray-900 
                            outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 
                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary-dark sm:text-sm/6"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                    />
                    <button
                        type="button"
                        @click="togglePassword"
                        class="absolute right-3 text-primary-txt"
                        >
                        <span v-if="showPassword">👁️</span>
                        <span v-else>👁️‍🗨️</span>
                    </button> 
                </div>
                 
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    value="Confirmation Mot de passe"
                    class="text-primary"
                />

                <div class="flex items-center mt-1 relative">   
                    <TextInput
                        id="password_confirmation"
                        :type="showPasswordConfirmer ? 'text' : 'password'"
                        class="mt-1 block w-full h-10 text-lg px-3 py-2 rounded-md bg-white text-base text-gray-900 
                            outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 
                            focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary-dark sm:text-sm/6"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                    />
                    <button
                        type="button"
                        @click="togglePasswordConfirmer"
                        class="absolute right-3 text-primary-txt"
                        >
                        <span v-if="showPasswordConfirmer">👁️</span>
                        <span v-else>👁️‍🗨️</span>
                    </button> 
                </div>

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-primary underline hover:text-primary-dark focus:outline-none focus:ring-primary focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Déjà inscrit ?
                </Link>

                <PrimaryButton
                    class="ms-4 bg-primary-dark text-white hover:bg-primary-dark focus:ring-primary-dark disabled:opacity-25"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Inscrire
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
