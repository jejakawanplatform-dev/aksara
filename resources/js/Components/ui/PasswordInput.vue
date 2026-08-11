<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    id: { type: String, required: true },
    modelValue: { type: String, default: '' },
    autocomplete: { type: String, default: 'current-password' },
    placeholder: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    autofocus: { type: Boolean, default: false },
    invalid: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'blur', 'input']);

const visible = ref(false);

const inputType = computed(() => (visible.value ? 'text' : 'password'));

function onInput(event) {
    emit('update:modelValue', event.target.value);
    emit('input', event);
}

function toggle() {
    visible.value = !visible.value;
}
</script>

<template>
    <div class="relative">
        <input
            :id="id"
            :value="modelValue"
            :type="inputType"
            :autocomplete="autocomplete"
            :placeholder="placeholder"
            :disabled="disabled"
            :autofocus="autofocus"
            :required="required"
            :aria-invalid="invalid ? 'true' : 'false'"
            :class="[
                'aksara-input pr-11',
                invalid ? 'aksara-input--error' : '',
            ]"
            @input="onInput"
            @blur="emit('blur', $event)"
        />
        <button
            type="button"
            class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-aksara-muted transition hover:text-aksara-ink focus:outline-none focus-visible:text-aksara-teal"
            :aria-label="visible ? 'Sembunyikan password' : 'Tampilkan password'"
            :aria-pressed="visible"
            tabindex="-1"
            @click="toggle"
        >
            <!-- eye / eye-off (inline SVG, tanpa lib ikon baru) -->
            <svg
                v-if="!visible"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.75"
                class="h-4 w-4"
                aria-hidden="true"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M2.036 12.322a1.012 1.012 0 0 1 0-.644C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .638C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"
                />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                />
            </svg>
            <svg
                v-else
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.75"
                class="h-4 w-4"
                aria-hidden="true"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"
                />
            </svg>
        </button>
    </div>
</template>
