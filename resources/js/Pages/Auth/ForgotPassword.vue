<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { nextTick, onMounted, ref } from 'vue';

const RECAPTCHA_SCRIPT_SELECTOR = 'script[data-recaptcha-script="true"]';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    'g-recaptcha-response': '',
});

const recaptchaPublicKey = import.meta.env.RECAPTCHA_PUBLIC_KEY || '';

const scriptLoaded = ref(false);
const recaptchaContainer = ref(null);
const recaptchaWidgetId = ref(null);

const setRecaptchaToken = (token = '') => {
    form['g-recaptcha-response'] = token;

    if (token) {
        form.clearErrors('g-recaptcha-response');
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

const resetRecaptcha = (message = 'Completa reCAPTCHA nuevamente.') => {
    setRecaptchaToken();

    if (window.grecaptcha && recaptchaWidgetId.value !== null) {
        window.grecaptcha.reset(recaptchaWidgetId.value);
    }

    if (message) {
        form.setError('g-recaptcha-response', message);
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
            form.setError('g-recaptcha-response', 'reCAPTCHA no se cargo correctamente. Intenta recargar la pagina.');
        });
});

const submit = () => {
    if (!scriptLoaded.value || !window.grecaptcha) {
        form.setError('g-recaptcha-response', 'reCAPTCHA no se cargo correctamente. Intenta recargar la pagina.');
        return;
    }

    const token = form['g-recaptcha-response'] || (recaptchaWidgetId.value !== null ? window.grecaptcha.getResponse(recaptchaWidgetId.value) : '');

    if (!token) {
        form.setError('g-recaptcha-response', 'Por favor completa la verificacion de reCAPTCHA.');
        return;
    }

    setRecaptchaToken(token);
    form.post(route('password.email'), {
        onFinish: () => resetRecaptcha(''),
        onError: () => resetRecaptcha(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Recuperar contrasena" />

        <div class="mb-4 text-sm text-gray-600">
            Olvidaste tu contrasena? No hay problema. Ingresa tu correo electronico y te enviaremos un enlace para
            restablecerla.
        </div>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Correo electronico" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-6">
                <div ref="recaptchaContainer" class="flex justify-center"></div>
                <InputError class="mt-2" :message="form.errors['g-recaptcha-response']" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Enviar enlace de restablecimiento
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
