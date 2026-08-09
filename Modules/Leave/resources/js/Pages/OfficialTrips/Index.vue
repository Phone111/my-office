<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    trips: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});

const statusMeta = (s) =>
    ({
        draft: { label: 'ร่าง', classes: 'text-gray-500' },
        pending: { label: 'รออนุมัติ', classes: 'text-amber-600' },
        approved: { label: 'อนุมัติแล้ว', classes: 'text-green-600' },
        rejected: { label: 'ตีกลับ', classes: 'text-red-600' },
    })[s] ?? { label: s, classes: 'text-gray-500' };
</script>

<template>
    <Head title="แฟ้มไปราชการ" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">แฟ้มไปราชการ</h2>
                <Link :href="route('official-trips.create')">
                    <PrimaryButton>+ เขียนขอไปราชการ</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ flash.success }}</div>
                <div v-if="flash.error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ flash.error }}</div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <EmptyState
                        v-if="trips.length === 0"
                        title="ยังไม่มีคำขอไปราชการ"
                        icon="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z"
                    />
                    <table v-else class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-3 text-left">เรื่อง</th>
                                <th class="px-6 py-3 text-left">สถานที่ไป</th>
                                <th class="px-6 py-3 text-left">ช่วงวันที่</th>
                                <th class="px-6 py-3 text-center">สถานะ</th>
                                <th class="px-6 py-3 text-right">ดู</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="t in trips" :key="t.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium text-gray-900">{{ t.title }}</td>
                                <td class="px-6 py-3 text-gray-600">{{ t.destination }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ t.depart_thai }} – {{ t.return_thai }}</td>
                                <td class="px-6 py-3 text-center">
                                    <span class="font-medium" :class="statusMeta(t.status).classes">{{ statusMeta(t.status).label }}</span>
                                    <div v-if="t.status === 'pending' && t.current_approver" class="text-xs text-gray-400">รอ {{ t.current_approver }}</div>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <Link :href="route('official-trips.show', t.id)" class="font-medium text-indigo-600 hover:text-indigo-800">ดู</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
