<script setup>
 
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia'; 
import { ref } from 'vue'

const showPassword = ref(false)

const togglePassword = () => {
  showPassword.value = !showPassword.value
}
 
defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

</script>

<template>
    <GuestLayout class="bg-white">  
                <Head title="Login" />

                <div v-if="status" class="mb-6 bg-white/90 text-sm font-medium text-primary-txt font-bold">
                    {{ status }}
                </div>

                <div class="justify-right"> 
                    <form @submit.prevent="submit"
                        class="w-full max-w-md">
                        <BaseInput
                            id="email"
                            name="email"
                            label="Adresse Email"
                            type="email"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            wrapper-class="w-full"
                            :error="form.errors.email"
                        />

                        <BaseInput
                            id="password"
                            name="password"
                            label="Mot de Passe"
                            :type="showPassword ? 'text' : 'password'"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            wrapper-class="mt-4 w-full"
                            :error="form.errors.password"
                        >
                            <template #append>
                                <button
                                    type="button"
                                    @click="togglePassword"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-primary-txt"
                                >
                                    <span v-if="showPassword">👁️</span>
                                    <span v-else>👁️‍🗨️</span>
                                </button>
                            </template>
                        </BaseInput>

                        <div class="mt-6 block">
                            <label class="flex items-center">
                                <Checkbox name="remember" v-model:checked="form.remember" class="text-primary" />
                                <span class="ms-2 text-sm text-primary"
                                    >Se souvenir de moi</span
                                >
                            </label>
                        </div><br>

                        <div class="mt-4 flex items-center justify-end">
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="rounded-md text-sm text-primary underline hover:text-primary-dark focus:outline-none focus:ring-primary focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Mot de passe oublié ?
                            </Link> 

                            <PrimaryButton
                                class="ms-4 bg-primary-dark text-white hover:bg-primary-dark focus:ring-primary-dark disabled:opacity-25"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                Connecter
                            </PrimaryButton>
                        </div>
                    </form> 
                </div>
            
 
    </GuestLayout>
</template>
