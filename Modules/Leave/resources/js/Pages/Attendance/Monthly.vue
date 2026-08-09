<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    days: { type: Number, default: 30 },
    month: { type: Number, default: 1 },
    year: { type: Number, default: 2026 },
    yearThai: { type: Number, default: 2569 },
    monthName: { type: String, default: '' },
    abbr: { type: Object, default: () => ({}) },
    statuses: { type: Object, default: () => ({}) },
});

const THAI_MONTHS = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];

const pickMonth = ref(props.month);
const pickYear = ref(props.year);
const go = () => router.get(route('leave.attendance.monthly'), { month: pickMonth.value, year: pickYear.value }, { preserveScroll: true });

const dayList = computed(() => Array.from({ length: props.days }, (_, i) => i + 1));
const legend = computed(() => Object.entries(props.statuses).map(([code, label]) => ({ abbr: props.abbr[code], label })));

// รวมวันลาทุกประเภทเป็นคอลัมน์เดียว
const leaveTotal = (c) => (c.sick ?? 0) + (c.personal ?? 0) + (c.maternity ?? 0) + (c.other_leave ?? 0);

const print = () => window.print();
</script>

<template>
    <Head title="รายงานการปฏิบัติราชการรอบเดือน" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800">สรุปการปฏิบัติราชการรอบเดือน</h2>
                <Link :href="route('leave.attendance.daily')" class="text-sm text-gray-500 hover:text-gray-700">รายงานรายวัน ›</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-full space-y-4 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 print:hidden">
                    <div class="flex items-center gap-2">
                        <select v-model.number="pickMonth" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="go">
                            <option v-for="(m, i) in THAI_MONTHS" :key="i" :value="i + 1">{{ m }}</option>
                        </select>
                        <input v-model.number="pickYear" type="number" class="w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="go" />
                        <span class="text-xs text-gray-400">(ค.ศ. · พ.ศ. {{ pickYear + 543 }})</span>
                    </div>
                    <button type="button" class="rounded-md border border-gray-300 px-4 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="print">🖨 พิมพ์</button>
                </div>

                <!-- คำอธิบายอักษรย่อ -->
                <div class="flex flex-wrap gap-x-4 gap-y-1 rounded-xl bg-gray-50 px-4 py-2 text-xs text-gray-600 print:bg-white">
                    <span v-for="(l, i) in legend" :key="i"><b class="text-gray-800">{{ l.abbr }}</b> = {{ l.label }}</span>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                    <h3 class="mb-3 text-center text-lg font-bold text-gray-800">การปฏิบัติราชการ เดือน{{ monthName }} {{ yearThai }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse text-center text-xs">
                            <thead>
                                <tr class="bg-gray-50 text-gray-500">
                                    <th class="sticky left-0 z-10 border border-gray-100 bg-gray-50 px-2 py-1.5 text-left">ชื่อ-สกุล</th>
                                    <th v-for="d in dayList" :key="d" class="border border-gray-100 px-1 py-1.5 w-6">{{ d }}</th>
                                    <th class="border border-gray-100 px-2 py-1.5 text-emerald-600">มา</th>
                                    <th class="border border-gray-100 px-2 py-1.5 text-indigo-600">ราชการ</th>
                                    <th class="border border-gray-100 px-2 py-1.5 text-amber-600">ลา</th>
                                    <th class="border border-gray-100 px-2 py-1.5 text-orange-600">สาย</th>
                                    <th class="border border-gray-100 px-2 py-1.5 text-rose-600">ไม่มา</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(r, i) in rows" :key="i" class="hover:bg-gray-50">
                                    <td class="sticky left-0 z-10 border border-gray-100 bg-white px-2 py-1 text-left font-medium text-gray-800">{{ r.name }}</td>
                                    <td v-for="d in dayList" :key="d" class="border border-gray-100 px-1 py-1 text-gray-600">{{ r.cells[d] }}</td>
                                    <td class="border border-gray-100 px-2 py-1 font-semibold text-emerald-700">{{ r.counts.present || '' }}</td>
                                    <td class="border border-gray-100 px-2 py-1 font-semibold text-indigo-700">{{ r.counts.trip || '' }}</td>
                                    <td class="border border-gray-100 px-2 py-1 font-semibold text-amber-700">{{ leaveTotal(r.counts) || '' }}</td>
                                    <td class="border border-gray-100 px-2 py-1 font-semibold text-orange-700">{{ r.counts.late || '' }}</td>
                                    <td class="border border-gray-100 px-2 py-1 font-semibold text-rose-700">{{ r.counts.absent || '' }}</td>
                                </tr>
                                <tr v-if="rows.length === 0"><td :colspan="days + 6" class="py-10 text-gray-400">ไม่มีข้อมูล</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
