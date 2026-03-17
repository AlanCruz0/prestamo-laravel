<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref } from 'vue';

const RECAPTCHA_SCRIPT_SELECTOR = 'script[data-recaptcha-script="true"]';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    'g-recaptcha-response': '',
});

const recaptchaPublicKey = import.meta.env.RECAPTCHA_PUBLIC_KEY || '';

const scriptLoaded = ref(false);
const recaptchaContainer = ref(null);
const recaptchaWidgetId = ref(null);

const passwordRequirements = computed(() => {
    const p = form.password;
    return [
        { label: 'Minimo 8 caracteres',                    met: p.length >= 8 },
        { label: 'Al menos una letra minuscula',           met: /[a-z]/.test(p) },
        { label: 'Al menos una letra mayuscula',           met: /[A-Z]/.test(p) },
        { label: 'Al menos un numero',                     met: /[0-9]/.test(p) },
        { label: 'Al menos un simbolo (ej. @, #, !)',      met: /[^a-zA-Z0-9]/.test(p) },
        { label: 'Sin numeros consecutivos (ej. 123)',     met: p.length > 0 && !/(?:(?:0(?=1)|1(?=2)|2(?=3)|3(?=4)|4(?=5)|5(?=6)|6(?=7)|7(?=8)|8(?=9)){2}|(?:9(?=8)|8(?=7)|7(?=6)|6(?=5)|5(?=4)|4(?=3)|3(?=2)|2(?=1)|1(?=0)){2})/.test(p) },
    ];
});

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
    form.clearErrors('name');

    if (form.name.trim().length < 3) {
        form.setError('name', 'El nombre debe tener al menos 3 caracteres.');
        return;
    }

    if (/[0-9]/.test(form.name)) {
        form.setError('name', 'El nombre no puede contener numeros.');
        return;
    }

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
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
        onError: () => resetRecaptcha(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Registro" />

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Nombre" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Correo electronico" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
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
                    autocomplete="new-password"
                />

                <ul class="mt-2 space-y-1 text-xs">
                    <li
                        v-for="req in passwordRequirements"
                        :key="req.label"
                        class="flex items-center gap-1.5"
                        :class="req.met ? 'text-green-600' : 'text-gray-400'"
                    >
                        <span class="text-base leading-none" aria-hidden="true">{{ req.met ? '✓' : '○' }}</span>
                        {{ req.label }}
                    </li>
                </ul>

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirmar contrasena" />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="flex justify-center mt-4">
                <div ref="recaptchaContainer"></div>
            </div>

            <InputError v-if="form.errors['g-recaptcha-response']" class="mt-2" :message="form.errors['g-recaptcha-response']" />

            <div class="flex items-center justify-end mt-4">
                <Link
                    :href="route('login')"
                    class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Ya tienes cuenta?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Registrarse
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
