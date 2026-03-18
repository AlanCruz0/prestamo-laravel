<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { nextTick, onMounted, ref } from 'vue';

const RECAPTCHA_SCRIPT_SELECTOR = 'script[data-recaptcha-script="true"]';

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

const resendForm = useForm({
    'g-recaptcha-response': '',
});

const recaptchaPublicKey = import.meta.env.RECAPTCHA_PUBLIC_KEY || '';

const scriptLoaded = ref(false);
const recaptchaContainer = ref(null);
const recaptchaWidgetId = ref(null);

const CODE_LENGTH = 6;

const sanitizeCode = () => {
    form.code = form.code.replace(/\D/g, '').slice(0, CODE_LENGTH);
};

const setRecaptchaToken = (token = '') => {
    resendForm['g-recaptcha-response'] = token;

    if (token) {
        resendForm.clearErrors('g-recaptcha-response');
    }
};

const loadRecaptcha = () => {
    if (window.grecaptcha && typeof window.grecaptcha.render === 'function') {
        return Promise.resolve(window.grecaptcha);
    }

    if (window.__recaptchaLoaderPromise) {
        return window.__recaptchaLoaderPromise;
    }

    window.__recaptchaLoaderPromise = new Promise((resolve, reject) => {
        window.__onRecaptchaLoaded = () => {
            if (window.grecaptcha && typeof window.grecaptcha.render === 'function') {
                resolve(window.grecaptcha);
                return;
            }

            reject(new Error('reCAPTCHA no pudo inicializarse.'));
        };

        const existingScript = document.querySelector(RECAPTCHA_SCRIPT_SELECTOR);

        if (existingScript) {
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://www.google.com/recaptcha/api.js?render=explicit&onload=__onRecaptchaLoaded';
        script.async = true;
        script.defer = true;
        script.dataset.recaptchaScript = 'true';
        script.onerror = () => reject(new Error('No se pudo cargar el script de reCAPTCHA.'));
        document.head.appendChild(script);
    });

    return window.__recaptchaLoaderPromise;
};

const resetRecaptcha = (message = 'Completa reCAPTCHA nuevamente para volver a intentarlo.') => {
    setRecaptchaToken();

    if (window.grecaptcha && recaptchaWidgetId.value !== null) {
        window.grecaptcha.reset(recaptchaWidgetId.value);
    }

    if (message) {
        resendForm.setError('g-recaptcha-response', message);
    }
};

const renderRecaptcha = async () => {
    await nextTick();

    if (!scriptLoaded.value || !window.grecaptcha || typeof window.grecaptcha.render !== 'function' || !recaptchaContainer.value || recaptchaWidgetId.value !== null || !recaptchaPublicKey) {
        return;
    }

    recaptchaWidgetId.value = window.grecaptcha.render(recaptchaContainer.value, {
        sitekey: recaptchaPublicKey,
        callback: (token) => setRecaptchaToken(token),
        'expired-callback': () => resetRecaptcha('La verificacion expiro. Completa reCAPTCHA nuevamente.'),
        'error-callback': () => resetRecaptcha('No se pudo validar reCAPTCHA. Intenta nuevamente.'),
    });
};

onMounted(() => {
    loadRecaptcha()
        .then(() => {
            scriptLoaded.value = true;
            renderRecaptcha();
        })
        .catch(() => {
            resendForm.setError('g-recaptcha-response', 'reCAPTCHA no se cargo correctamente. Intenta recargar la pagina.');
        });
});

const submit = () => {
    sanitizeCode();
    form.post(route('code-verification.verify'));
};

const resend = () => {
    if (!scriptLoaded.value || !window.grecaptcha) {
        resendForm.setError('g-recaptcha-response', 'reCAPTCHA no se cargo correctamente. Intenta recargar la pagina.');
        return;
    }

    const token = resendForm['g-recaptcha-response'] || (recaptchaWidgetId.value !== null ? window.grecaptcha.getResponse(recaptchaWidgetId.value) : '');

    if (!token) {
        resendForm.setError('g-recaptcha-response', 'Por favor completa la verificacion de reCAPTCHA.');
        return;
    }

    setRecaptchaToken(token);
    resendForm.post(route('code-verification.resend'), {
        onFinish: () => resetRecaptcha(''),
        onError: () => resetRecaptcha(),
    });
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

            <div class="mt-4 flex justify-center">
                <div ref="recaptchaContainer"></div>
            </div>

            <InputError
                v-if="resendForm.errors['g-recaptcha-response']"
                class="mt-2"
                :message="resendForm.errors['g-recaptcha-response']"
            />

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
