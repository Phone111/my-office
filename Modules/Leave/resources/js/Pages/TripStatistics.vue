<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    year: { type: Number, default: 0 }, // พ.ศ.
    rows: { type: Array, default: () => [] },
    monthTotals: { type: Object, default: () => ({}) },
    grandTotal: { type: Number, default: 0 },
    people: { type: Number, default: 0 },
});

const monthNames = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
const selectedYear = ref(props.year);
const reload = () => router.get(route('leave.trip-statistics'), { year: selectedYear.value }, { preserveScroll: true, preserveState: true });
const printPage = () => window.print();
</script>

<template>
    <Head title="สถิติการไปราชการ" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">สถิติการไปราชการของบุคลากร</h2>
                <div class="no-print flex items-center gap-2 text-sm">
                    <span class="text-gray-500">ปี พ.ศ.</span>
                    <input v-model.number="selectedYear" type="number" class="w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="reload" />
                    <button type="button" class="rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200" @click="printPage">พิมพ์</button>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div id="trip-stat-print" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-center text-sm font-semibold text-gray-700">
                        สถิติการไปราชการของบุคลากร · ปี พ.ศ. {{ year }} · รวม {{ grandTotal }} ครั้ง ({{ people }} คน)
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-xs font-semibold text-gray-500">
                                    <th class="border border-gray-100 px-2 py-2 text-center">ที่</th>
                                    <th class="border border-gray-100 px-3 py-2 text-left">ชื่อ - สกุล</th>
                                    <th v-for="(m, i) in monthNames" :key="i" class="border border-gray-100 px-2 py-2 text-center">{{ m }}</th>
                                    <th class="border border-gray-100 px-2 py-2 text-center">รวม</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="rows.length === 0"><td :colspan="15" class="px-6 py-10 text-center text-sm text-gray-400">ปีนี้ยังไม่มีการไปราชการ</td></tr>
                                <tr v-for="(r, i) in rows" :key="i" class="text-gray-700 hover:bg-indigo-50/40">
                                    <td class="border border-gray-100 px-2 py-2 text-center text-gray-400">{{ i + 1 }}</td>
                                    <td class="border border-gray-100 px-3 py-2 font-medium text-gray-900">{{ r.name }}<div class="text-xs text-gray-400">{{ r.position ?? '—' }}</div></td>
                                    <td v-for="mn in 12" :key="mn" class="border border-gray-100 px-2 py-2 text-center tabular-nums" :class="r.months[mn] ? 'font-semibold text-indigo-600' : 'text-gray-300'">{{ r.months[mn] || '' }}</td>
                                    <td class="border border-gray-100 px-2 py-2 text-center font-bold tabular-nums">{{ r.total }}</td>
                                </tr>
                            </tbody>
                            <tfoot v-if="rows.length">
                                <tr class="bg-gray-50 text-xs font-semibold text-gray-600">
                                    <td colspan="2" class="border border-gray-100 px-3 py-2 text-right">รวมรายเดือน</td>
                                    <td v-for="mn in 12" :key="mn" class="border border-gray-100 px-2 py-2 text-center tabular-nums">{{ monthTotals[mn] || '' }}</td>
                                    <td class="border border-gray-100 px-2 py-2 text-center tabular-nums">{{ grandTotal }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@media print {
    body * { visibility: hidden; }
    #trip-stat-print, #trip-stat-print * { visibility: visible; }
    #trip-stat-print { position: absolute; left: 0; top: 0; width: 100%; }
    .no-print { display: none !important; }
}
</style>
