<script setup lang="ts">
import { PhBackspace } from '@phosphor-icons/vue';

const props = withDefaults(defineProps<{
    modelValue: string;
    allowDecimal?: boolean;
    maxLength?: number;
    disabled?: boolean;
    label?: string;
    clearLabel?: string;
}>(), {
    allowDecimal: false,
    maxLength: 8,
    disabled: false,
    label: 'Nummernfeld',
    clearLabel: 'Eingabe löschen',
});

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
const digits = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];

function append(value: string) {
    if (props.disabled || props.modelValue.length >= props.maxLength) return;
    if (value === ',' && (!props.allowDecimal || props.modelValue.includes(',') || props.modelValue.includes('.'))) return;
    emit('update:modelValue', `${props.modelValue}${value}`);
}
</script>

<template>
    <div class="atm-keypad" role="group" :aria-label="label">
        <button v-for="digit in digits" :key="digit" type="button" class="atm-key" :disabled="disabled || modelValue.length >= maxLength" :aria-label="`Ziffer ${digit}`" @click="append(digit)">{{ digit }}</button>
        <button type="button" class="atm-key atm-key--muted" :disabled="disabled || !allowDecimal || modelValue.includes(',') || modelValue.includes('.')" aria-label="Komma" @click="append(',')">,</button>
        <button type="button" class="atm-key" :disabled="disabled || modelValue.length >= maxLength" aria-label="Ziffer 0" @click="append('0')">0</button>
        <button type="button" class="atm-key atm-key--muted" :disabled="disabled || modelValue.length === 0" aria-label="Letzte Ziffer löschen" @click="emit('update:modelValue', modelValue.slice(0, -1))">
            <PhBackspace :size="22" weight="bold" aria-hidden="true" />
        </button>
        <button type="button" class="atm-key atm-key--clear" :disabled="disabled || modelValue.length === 0" :aria-label="clearLabel" @click="emit('update:modelValue', '')">{{ clearLabel }}</button>
    </div>
</template>
