<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    mine: { type: Array, default: () => [] },
    open: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
});
const flash = computed(() => usePage().props.flash?.success);
const statusCls = (s) => ({ draft: 'bg-gray-100 text-gray-600', open: 'bg-emerald-100 text-emerald-700', closed: 'bg-rose-100 text-rose-600' }[s] ?? 'bg-gray-100 text-gray-600');
</script>

<template>
    <Head title="แบบสอบถาม" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ระบบแบบสอบถาม</h2>
                <Link v-if="canManage" :href="route('surveys.create')" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">สร้างแบบสอบถาม</Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <!-- แบบสอบถามที่เปิดให้ตอบ -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">แบบสอบถามที่เปิดให้ตอบ ({{ open.length }})</div>
                    <ul class="divide-y divide-gray-50">
                        <li v-if="open.length === 0" class="px-6 py-8"><EmptyState title="ยังไม่มีแบบสอบถามที่เปิดอยู่" /></li>
                        <li v-for="s in open" :key="s.id" class="flex items-center justify-between gap-3 px-6 py-4 hover:bg-gray-50">
                            <div>
                                <p class="font-medium text-gray-900">{{ s.title }}</p>
                                <p v-if="s.description" class="text-sm text-gray-500">{{ s.description }}</p>
                                <p class="text-xs text-gray-400">{{ s.questions }} คำถาม<span v-if="s.closes_thai"> · ปิดรับ {{ s.closes_thai }}</span></p>
                            </div>
                            <Link v-if="!s.answered" :href="route('surveys.show', s.id)" class="shrink-0 rounded-md bg-indigo-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500">ทำแบบสอบถาม</Link>
                            <span v-else class="shrink-0 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">ตอบแล้ว</span>
                        </li>
                    </ul>
                </div>

                <!-- แบบสอบถามที่ฉันสร้าง -->
                <div v-if="canManage" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">แบบสอบถามที่สร้าง ({{ mine.length }})</div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3">ชื่อแบบสอบถาม</th><th class="px-4 py-3">สถานะ</th><th class="px-4 py-3 text-center">คำตอบ</th><th class="px-4 py-3">ปิดรับ</th><th class="px-4 py-3 text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="mine.length === 0"><td colspan="5" class="px-6 py-8"><EmptyState title="ยังไม่ได้สร้างแบบสอบถาม" /></td></tr>
                            <tr v-for="s in mine" :key="s.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ s.title }}</td>
                                <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="statusCls(s.status)">{{ s.status_label }}</span></td>
                                <td class="px-4 py-3 text-center font-semibold text-indigo-700">{{ s.responses }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ s.closes_thai ?? '—' }}</td>
                                <td class="px-4 py-3 text-center"><Link :href="route('surveys.show', s.id)" class="text-indigo-600 hover:underline">ดูผล/จัดการ</Link></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
