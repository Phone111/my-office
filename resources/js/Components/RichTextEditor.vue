<script setup>
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

const editor = ref(null);

onMounted(() => {
    if (editor.value) editor.value.innerHTML = props.modelValue || '';
});

// ซิงค์ค่าจากภายนอก (เช่น reset) เฉพาะตอนไม่ได้โฟกัสอยู่
watch(
    () => props.modelValue,
    (v) => {
        if (editor.value && v !== editor.value.innerHTML && document.activeElement !== editor.value) {
            editor.value.innerHTML = v || '';
        }
    },
);

const onInput = () => emit('update:modelValue', editor.value.innerHTML);

const cmd = (command, value = null) => {
    document.execCommand(command, false, value);
    editor.value.focus();
    onInput();
};

const tools = [
    { cmd: 'bold', label: 'B', class: 'font-bold' },
    { cmd: 'italic', label: 'I', class: 'italic' },
    { cmd: 'underline', label: 'U', class: 'underline' },
    { divider: true },
    { cmd: 'insertUnorderedList', label: '• รายการ' },
    { cmd: 'insertOrderedList', label: '1. รายการ' },
    { divider: true },
    { cmd: 'justifyLeft', label: 'ซ้าย' },
    { cmd: 'justifyCenter', label: 'กึ่งกลาง' },
    { cmd: 'justifyRight', label: 'ขวา' },
];
</script>

<template>
    <div class="overflow-hidden rounded-md border border-gray-300 shadow-sm focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
        <!-- แถบเครื่องมือ -->
        <div class="flex flex-wrap items-center gap-1 border-b border-gray-200 bg-gray-50 px-2 py-1.5">
            <template v-for="(t, i) in tools" :key="i">
                <span v-if="t.divider" class="mx-1 h-5 w-px bg-gray-300" />
                <button
                    v-else
                    type="button"
                    class="rounded px-2 py-1 text-sm text-gray-600 transition hover:bg-gray-200"
                    :class="t.class"
                    @click="cmd(t.cmd)"
                >
                    {{ t.label }}
                </button>
            </template>
        </div>
        <!-- พื้นที่พิมพ์ -->
        <div
            ref="editor"
            class="rich-content min-h-[220px] px-3 py-2 text-gray-800 focus:outline-none"
            contenteditable="true"
            @input="onInput"
        />
    </div>
</template>
