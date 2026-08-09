<script setup>
defineProps({
    title: { type: String, default: 'ยังไม่มีข้อมูล' },
    description: { type: String, default: '' },
    // heroicon path (outline). ค่าเริ่มต้น = กล่อง/โฟลเดอร์ว่าง
    icon: {
        type: String,
        default: 'M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z',
    },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center px-6 py-14 text-center">
        <div class="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
            <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" :d="icon" />
            </svg>
        </div>
        <p class="text-sm font-medium text-gray-600">{{ title }}</p>
        <p v-if="description" class="mt-1 text-xs text-gray-400">{{ description }}</p>
        <div v-if="$slots.action" class="mt-5">
            <slot name="action" />
        </div>
    </div>
</template>
