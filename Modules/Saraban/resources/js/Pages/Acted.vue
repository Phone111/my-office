<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { thaiDate } from '@/utils/format';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    routes: { type: Array, default: () => [] },
});

// หนังสือ 'rejected' แสดงคำว่า "ตีกลับ" → ใช้สถานะกลาง returned
const badgeStatus = (status) => (status === 'rejected' ? 'returned' : status);
</script>

<template>
    <Head title="เอกสารที่ดำเนินการแล้ว" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">เอกสารที่ดำเนินการแล้ว</h2>
                <Link
                    :href="route('saraban.documents.inbox')"
                    class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200"
                >
                    ← แฟ้มเอกสารเสนอ (รอดำเนินการ)
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <EmptyState
                        v-if="routes.length === 0"
                        title="ยังไม่มีเอกสารที่ดำเนินการ"
                        description="เอกสารที่คุณอนุมัติหรือตีกลับแล้วจะถูกเก็บที่นี่"
                        icon="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                    />
                    <table v-else class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3">เรื่อง</th>
                                <th class="px-6 py-3">เจ้าของเรื่อง</th>
                                <th class="px-6 py-3">ผลดำเนินการ</th>
                                <th class="px-6 py-3">วันที่ดำเนินการ</th>
                                <th class="px-6 py-3 text-right">ดู</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="item in routes" :key="item.document_id + '-' + item.acted_at" class="text-sm text-gray-700 hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ item.title }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ item.creator }}</td>
                                <td class="px-6 py-4">
                                    <StatusBadge :status="badgeStatus(item.status)" />
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ item.acted_at ? thaiDate(item.acted_at) : '—' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="route('saraban.documents.show', item.document_id)" class="font-medium text-indigo-600 hover:text-indigo-800">
                                        ดู
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
