<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({ rows: { type: Array, default: () => [] } });
const flash = computed(() => usePage().props.flash?.success);
</script>

<template>
    <Head title="ใบเบิกน้ำมันเชื้อเพลิง" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ใบเบิกน้ำมันเชื้อเพลิงและน้ำมันหล่อลื่น</h2>
                <p class="text-xs text-gray-400">รายการขอใช้รถที่อนุมัติแล้ว และเบิกน้ำมันจากราชการ</p>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-4 py-3">ผู้ขอ / คนขับ</th>
                                <th class="px-4 py-3">รถ</th>
                                <th class="px-4 py-3">ช่วงเดินทาง</th>
                                <th class="px-4 py-3">แหล่งน้ำมัน</th>
                                <th class="px-4 py-3 text-center">สถานะใบเบิก</th>
                                <th class="px-4 py-3 text-center">ใบเบิก</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="rows.length === 0"><td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400">ไม่มีรายการรอเบิกน้ำมัน</td></tr>
                            <tr v-for="r in rows" :key="r.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ r.requester }}</div>
                                    <div class="text-xs text-gray-400">คนขับ: {{ r.driver_name ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ r.vehicle }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ r.when }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ r.fuel_source }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="r.filled" class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">บันทึกแล้ว · {{ r.fuel_amount }} บาท</span>
                                    <span v-else class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">ยังไม่บันทึก</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Link :href="route('booking.vehicle-flow.fuel', r.id)" class="inline-flex items-center gap-1 rounded-md bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-600">
                                        {{ r.filled ? 'เปิด/พิมพ์' : 'กรอกใบเบิก' }} →
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
