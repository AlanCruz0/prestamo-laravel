<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onUnmounted, onMounted } from 'vue';
import axios from 'axios';

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

const CODE_LENGTH = 6;

// Estado del bloqueo
const isBlocked = ref(false);
const timeRemaining = ref(0);
const isProcessing = ref(false);
let timerInterval = null;

const STORAGE_KEY = 'verification-code-block';

const sanitizeCode = () => {
    form.code = form.code.replace(/\D/g, '').slice(0, CODE_LENGTH);
};

const saveBlockToStorage = (seconds) => {
    const expiresAt = Date.now() + seconds * 1000;
    localStorage.setItem(STORAGE_KEY, expiresAt.toString());
};

const getBlockFromStorage = () => {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (!stored) return null;

    const expiresAt = parseInt(stored);
    const now = Date.now();

    if (now >= expiresAt) {
        localStorage.removeItem(STORAGE_KEY);
        return null;
    }

    const remainingSeconds = Math.ceil((expiresAt - now) / 1000);
    return remainingSeconds;
};

const clearBlockStorage = () => {
    localStorage.removeItem(STORAGE_KEY);
};

const startBlockTimer = (seconds) => {
    isBlocked.value = true;
    timeRemaining.value = seconds;
    saveBlockToStorage(seconds);

    if (timerInterval) clearInterval(timerInterval);

    timerInterval = setInterval(() => {
        timeRemaining.value--;

        if (timeRemaining.value <= 0) {
            clearInterval(timerInterval);
            isBlocked.value = false;
            clearBlockStorage();
        }
    }, 1000);
};

const submit = async () => {
    if (isProcessing.value) return;
    
    sanitizeCode();
    isProcessing.value = true;

    try {
        const response = await axios.post(route('code-verification.verify'), {
            code: form.code,
        }, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        // Si llegamos aquí, fue exitoso
        clearBlockStorage();
        window.location.href = route('dashboard');
    } catch (error) {
        // Detectar si es un error 429 (Too Many Requests)
        if (error.response?.status === 429) {
            const data = error.response?.data;
            if (data?.retry_after) {
                startBlockTimer(data.retry_after);
            }
            form.clearErrors();
        } else if (error.response?.status === 422) {
            // Error de validación
            const data = error.response?.data;
            if (data?.errors?.code) {
                form.setError('code', data.errors.code[0]);
            }
        } else {
            form.setError('code', 'Ocurrió un error. Intenta de nuevo.');
        }
    } finally {
        isProcessing.value = false;
    }
};

const resend = () => {
    resendForm.post(route('code-verification.resend'));
};

onMounted(() => {
    // Verificar si hay un bloqueo previo en localStorage
    const remainingSeconds = getBlockFromStorage();
    if (remainingSeconds) {
        startBlockTimer(remainingSeconds);
    }
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});
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
                    maxlength="6"
                    minlength="6"
                    pattern="[0-9]*"
                    class="mt-1 block w-full"
                    v-model="form.code"
                    @input="sanitizeCode"
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

                <PrimaryButton :class="{ 'opacity-25': isProcessing }" :disabled="isProcessing">
                    Verificar
                </PrimaryButton>
            </div>
        </form>

        <!-- Modal de bloqueo con timer -->
        <div v-if="isBlocked" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-8 max-w-sm w-full mx-4 shadow-lg">
                <div class="text-center">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Demasiados intentos
                    </h3>

                    <p class="text-gray-600 mb-6">
                        Has excedido el límite de intentos. Por favor espera antes de intentar de nuevo.
                    </p>

                    <div class="mb-6">
                        <p class="text-4xl font-bold text-blue-600">{{ timeRemaining }}</p>
                        <p class="text-sm text-gray-500 mt-2">segundos restantes</p>
                    </div>

                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div
                            class="bg-blue-600 h-2 rounded-full transition-all duration-1000"
                            :style="{ width: (timeRemaining / 600) * 100 + '%' }"
                        ></div>
                    </div>

                    <p class="text-xs text-gray-500 mt-4">
                        Podrás intentar de nuevo cuando el contador llegue a cero.
                    </p>
                </div>
            </div>
        </div>

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
