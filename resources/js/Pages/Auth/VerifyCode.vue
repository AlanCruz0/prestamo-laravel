<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    email: {
        type: String,
        required: true,
    },
    status: {
        type: String,
        default: null,
    },
});

const form = useForm({
    code: '',
});

const resendForm = useForm({});

const submit = () => {
    form.post(route('code-verification.verify'));
};

const resend = () => {
    resendForm.post(route('code-verification.resend'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Verificar codigo" />

        <div class="mb-4 text-sm text-gray-600">
            Enviamos un codigo de verificacion a <span class="font-semibold">{{ email }}</span>.
            Ingresalo para continuar.
        </div>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="code" value="Codigo" />

                <TextInput
                    id="code"
                    type="text"
                    inputmode="numeric"
                    class="mt-1 block w-full"
                    v-model="form.code"
                    required
                    autofocus
                    autocomplete="one-time-code"
                />

                <InputError class="mt-2" :message="form.errors.code" />
            </div>

            <div class="mt-4 flex items-center justify-between gap-2">
                <button
                    type="button"
                    class="underline text-sm text-gray-600 hover:text-gray-900"
                    @click="resend"
                    :disabled="resendForm.processing"
                >
                    Reenviar codigo
                </button>

                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Verificar
                </PrimaryButton>
            </div>
        </form>

        <div class="mt-6">
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="underline text-sm text-gray-600 hover:text-gray-900"
            >
                Cerrar sesion
            </Link>
        </div>
    </GuestLayout>
</template>
