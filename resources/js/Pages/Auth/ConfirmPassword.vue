<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirm Password" />

        <div class="mb-4 text-sm text-gray-600">
            Ceci est une zone sécurisée de l'application. 
            Veuillez confirmer votre mot de passe avant de continuer.
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="password" value="Mot de passe" class="text-primary" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full h-10 text-lg px-3 py-2 rounded-md bg-white text-base text-gray-900 
                        outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 
                        focus:outline focus:outline-2 focus:-outline-2 focus:outline-primary-dark sm:text-sm/6"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 flex justify-end">
                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Confirmer
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
