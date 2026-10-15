<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-primary-txt">
                Supprimer Compte
            </h2>

            <p class="mt-1 text-sm text-primary-txt">
                Une fois votre compte supprimé, toutes ses ressources et données 
                seront définitivement effacées. Avant de supprimer votre compte, 
                veuillez télécharger toutes les données ou informations que vous souhaitez conserver. 
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion">Delete Account</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2
                    class="text-lg font-medium text-primary-txt"
                >
                    Êtes-vous sûr de vouloir supprimer votre compte ?
                </h2>

                <p class="mt-1 text-sm text-primary-txt">
                    Une fois votre compte supprimé, toutes ses ressources et données seront 
                    définitivement effacées. Veuillez entrer votre mot de passe pour confirmer
                    que vous souhaitez supprimer votre compte de façon permanente. 
                </p>

                <div class="mt-6">
                    <BaseInput
                        id="password"
                        name="password"
                        ref="passwordInput"
                        label="Password"
                        v-model="form.password"
                        type="password"
                        placeholder="Password"
                        wrapper-class="w-3/4"
                        :error="form.errors.password"
                        @keyup.enter="deleteUser"
                    />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">
                        Annuler
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Supprimer Compte
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
