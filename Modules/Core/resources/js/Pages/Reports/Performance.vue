<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    year: { type: Number, default: 0 }, // พ.ศ.
    rows: { type: Array, default: () => [] },
    groupName: { type: String, default: null },
});

const cols = [
    { key: 'memo', label: 'บันทึก/รายงาน' },
    { key: 'send', label: 'หนังสือส่ง' },
    { key: 'recv', label: 'หนังสือรับ' },
    { key: 'order', label: 'คำสั่ง' },
    { key: 'leave', label: 'ลา' },
    { key: 'trip', label: 'ไปราชการ' },
];

const selectedYear = ref(props.year);
const reload = () => router.get(route('reports.performance'), { year: selectedYear.value }, { preserveScroll: true, preserveState: true });
const printPage = () => window.print();
</script>

<template>
    <Head title="ผลการปฏิบัติงาน" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ผลการปฏิบัติงานของบุคลากร</h2>
                <div class="no-print flex items-center gap-2 text-sm">
                    <span class="text-gray-500">ปี พ.ศ.</span>
                    <input v-model.number="selectedYear" type="number" class="w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="reload" />
                    <button type="button" class="rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200" @click="printPage">พิมพ์</button>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div id="perf-print" class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-center text-sm font-semibold text-gray-700">
                        ผลการปฏิบัติงาน (ปริมาณงาน) · {{ groupName ?? 'ทั้งหน่วยงาน' }} · ปี พ.ศ. {{ year }}
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-xs font-semibold text-gray-500">
                                    <th class="border border-gray-100 px-2 py-2 text-center">ที่</th>
                                    <th class="border border-gray-100 px-3 py-2 text-left">ชื่อ - สกุล</th>
                                    <th v-for="c in cols" :key="c.key" class="border border-gray-100 px-2 py-2 text-center">{{ c.label }}</th>
                                    <th class="border border-gray-100 px-2 py-2 text-center">รวม</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="rows.length === 0"><td :colspan="cols.length + 3" class="px-6 py-10 text-center text-sm text-gray-400">ไม่มีข้อมูล</td></tr>
                                <tr v-for="(r, i) in rows" :key="i" class="text-gray-700 hover:bg-indigo-50/40">
                                    <td class="border border-gray-100 px-2 py-2 text-center text-gray-400">{{ i + 1 }}</td>
                                    <td class="border border-gray-100 px-3 py-2 font-medium text-gray-900">{{ r.name }}<div class="text-xs text-gray-400">{{ r.position ?? '—' }}</div></td>
                                    <td v-for="c in cols" :key="c.key" class="border border-gray-100 px-2 py-2 text-center tabular-nums" :class="r[c.key] ? 'text-gray-700' : 'text-gray-300'">{{ r[c.key] || '' }}</td>
                                    <td class="border border-gray-100 px-2 py-2 text-center font-bold tabular-nums" :class="r.total ? 'text-indigo-600' : 'text-gray-300'">{{ r.total }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="px-6 py-2 text-xs text-gray-400">นับเฉพาะเอกสารที่สร้าง/ลา/ไปราชการ ในปีที่เลือก</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@media print {
    body * { visibility: hidden; }
    #perf-print, #perf-print * { visibility: visible; }
    #perf-print { position: absolute; left: 0; top: 0; width: 100%; }
    .no-print { display: none !important; }
}
</style>
