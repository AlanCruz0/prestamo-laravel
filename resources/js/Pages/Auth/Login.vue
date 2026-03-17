<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { nextTick, onMounted, ref } from 'vue';

const RECAPTCHA_SCRIPT_SELECTOR = 'script[data-recaptcha-script="true"]';

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

const resetRecaptcha = (message = 'Completa reCAPTCHA nuevamente para volver a intentarlo.') => {
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
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
        onError: () => resetRecaptcha(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar sesion" />

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

            <div class="mt-4">
                <InputLabel for="password" value="Contrasena" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="block mt-4">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600">Recordarme</span>
                </label>
            </div>

            <div class="flex justify-center mt-4">
                <div ref="recaptchaContainer"></div>
            </div>

            <InputError v-if="form.errors['g-recaptcha-response']" class="mt-2" :message="form.errors['g-recaptcha-response']" />

            <div class="flex items-center justify-end mt-4">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Olvidaste tu contrasena?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Iniciar sesion
                </PrimaryButton>
            </div>
        </form>

        <p class="mt-6 text-sm text-gray-600 text-center">
            No tienes cuenta?
            <Link :href="route('register')" class="font-medium text-gray-900 underline hover:text-gray-700">
                Registrate aqui
            </Link>
        </p>
    </GuestLayout>
</template>
