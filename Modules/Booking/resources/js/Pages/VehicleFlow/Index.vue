<script setup>
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({ rows: { type: Array, default: () => [] } });
const flash = computed(() => usePage().props.flash?.success);
</script>

<template>
    <Head title="แฟ้มขอใช้รถยนต์" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">แฟ้มขอใช้รถยนต์</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3">เรื่อง</th>
                                <th class="px-4 py-3">ผู้ส่งเรื่อง</th>
                                <th class="px-4 py-3">วันที่</th>
                                <th class="px-4 py-3">รถ</th>
                                <th class="px-4 py-3 text-center">สถานะ</th>
                                <th class="px-4 py-3 text-center">แฟ้มเสนอ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="rows.length === 0"><td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400">ไม่มีรายการ</td></tr>
                            <tr v-for="r in rows" :key="r.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ r.subject }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ r.requester }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ r.created_thai }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ r.vehicle ?? '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <StatusBadge :status="r.status" :label="r.status_label" />
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <Link :href="route('booking.vehicle-flow.show', r.id)" class="font-medium text-indigo-600 hover:underline">{{ r.status === 'booked' ? 'เปิดดู' : 'ดำเนินการต่อ' }}</Link>
                                        <Link v-if="r.can_fuel" :href="route('booking.vehicle-flow.fuel', r.id)" class="rounded-md bg-amber-500 px-2.5 py-1 text-xs font-semibold text-white hover:bg-amber-600">ใบเบิกน้ำมัน</Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
