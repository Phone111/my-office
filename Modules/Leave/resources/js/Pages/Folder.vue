<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    requests: { type: Array, default: () => [] },
    title: { type: String, default: 'แฟ้มการลา' },
    navLinks: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});

const statusMeta = (status) =>
    ({
        draft: { label: 'เสนอแฟ้มลา', classes: 'text-rose-600' },
        pending: { label: 'รออนุมัติ', classes: 'text-amber-600' },
        approved: { label: 'อนุมัติแล้ว', classes: 'text-green-600' },
        rejected: { label: 'ตีกลับ', classes: 'text-red-600' },
        cancelled: { label: 'ยกเลิกแล้ว', classes: 'text-gray-400' },
    })[status] ?? { label: status, classes: 'text-gray-500' };

const cancel = (r) => {
    if (confirm(`ยืนยันการยกเลิก "${r.subject}" ?\nหากยกเลิกแล้วจะไม่สามารถเรียกคืนได้`)) {
        router.post(route('leave.requests.cancel', r.id), {}, { preserveScroll: true });
    }
};
</script>

<template>
    <Head :title="title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ title }}</h2>
                <div class="flex gap-2">
                    <Link
                        v-for="lnk in navLinks"
                        :key="lnk.routeName"
                        :href="route(lnk.routeName)"
                        class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200"
                    >
                        {{ lnk.label }}
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ flash.success }}
                </div>
                <div v-if="flash.error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ flash.error }}
                </div>

                <div v-if="requests.length === 0" class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <EmptyState title="ยังไม่มีคำขอลาในแฟ้มนี้">
                        <template #action>
                            <Link :href="route('leave.requests.index')" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">+ เขียนใบลา</Link>
                        </template>
                    </EmptyState>
                </div>

                <div v-else class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center gap-2 border-b border-gray-100 bg-indigo-50/60 px-6 py-3">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" /></svg>
                        <span class="font-semibold text-gray-800">{{ title }}</span>
                        <span class="rounded-full bg-indigo-600 px-2 py-0.5 text-xs font-bold text-white">{{ requests.length }} เรื่อง</span>
                    </div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-4 py-3 text-center">ปีงบลา</th>
                                <th class="px-4 py-3 text-left">เรื่อง</th>
                                <th class="px-4 py-3 text-left">ผู้ส่งเรื่อง</th>
                                <th class="px-4 py-3 text-left">ผู้ผ่านเรื่อง</th>
                                <th class="px-4 py-3 text-center">สถานะ</th>
                                <th class="px-4 py-3 text-center">แฟ้มเสนอ</th>
                                <th class="px-4 py-3 text-center">ยกเลิก</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="r in requests" :key="r.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 text-center font-medium text-gray-900">{{ r.fiscal_year }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ r.subject }}</td>
                                <td class="px-4 py-3">
                                    <div class="text-gray-800">{{ r.sender }}</div>
                                    <div class="text-xs text-gray-400">{{ r.sent_thai }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ r.passed_by ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="font-medium" :class="statusMeta(r.status).classes">{{ statusMeta(r.status).label }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Link
                                        v-if="r.can_propose"
                                        :href="route('leave.requests.proposal', r.id)"
                                        class="font-semibold text-rose-600 hover:text-rose-800"
                                    >
                                        เสนอแฟ้ม
                                    </Link>
                                    <Link
                                        v-else
                                        :href="route('leave.requests.proposal', r.id)"
                                        class="font-medium text-indigo-600 hover:text-indigo-800"
                                    >
                                        ดู
                                    </Link>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button
                                        v-if="r.can_cancel"
                                        type="button"
                                        class="text-rose-500 hover:text-rose-700"
                                        title="ยกเลิก"
                                        @click="cancel(r)"
                                    >
                                        <svg class="mx-auto h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    </button>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
