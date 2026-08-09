<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    people: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] }, // [{id,name}]
    fiscalYear: { type: Number, default: 0 }, // พ.ศ.
    school: { type: String, default: '' },
});

const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.people.filter(
        (p) => !q || p.name.toLowerCase().includes(q) || (p.position ?? '').toLowerCase().includes(q) || (p.affiliation ?? '').toLowerCase().includes(q),
    );
});

const hasVacation = computed(() => props.types.some((t) => t.name.includes('พักผ่อน')));
const printPage = () => window.print();
</script>

<template>
    <Head title="ทะเบียนลา" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ทะเบียนลา</h2>
                <div class="flex items-center gap-2">
                    <input v-model="search" type="text" placeholder="ค้นหา ชื่อ / ตำแหน่ง / สังกัด" class="w-60 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-500" @click="printPage">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" /></svg>
                        พิมพ์
                    </button>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <!-- ====== บนจอ: รายชื่อ + ดูใบลา ====== -->
                <div class="no-print overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-center text-sm font-semibold text-gray-700">
                        ทะเบียนการลาของบุคลากร · {{ school }}
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <th class="border border-gray-100 px-3 py-2 text-center">ที่</th>
                                    <th class="border border-gray-100 px-4 py-2 text-left">ชื่อ - สกุล</th>
                                    <th class="border border-gray-100 px-4 py-2 text-left">ตำแหน่ง</th>
                                    <th class="border border-gray-100 px-4 py-2 text-left">สังกัด</th>
                                    <th class="border border-gray-100 px-3 py-2 text-center">ดูใบลา</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="filtered.length === 0"><td colspan="5" class="px-6 py-10 text-center text-sm text-gray-400">ไม่พบบุคลากร</td></tr>
                                <tr v-for="(p, i) in filtered" :key="p.id" class="text-gray-700 hover:bg-indigo-50/40">
                                    <td class="border border-gray-100 px-3 py-2 text-center text-gray-400">{{ i + 1 }}</td>
                                    <td class="border border-gray-100 px-4 py-2 font-medium text-gray-900">{{ p.name }}</td>
                                    <td class="border border-gray-100 px-4 py-2 text-gray-500">{{ p.position ?? '—' }}</td>
                                    <td class="border border-gray-100 px-4 py-2 text-gray-500">{{ p.affiliation ?? '—' }}</td>
                                    <td class="border border-gray-100 px-3 py-2 text-center">
                                        <Link :href="route('leave.registry.show', p.id)" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800" title="ดูใบลา">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <p class="no-print mt-3 text-xs text-gray-400">จำนวน {{ filtered.length }} คน · กด ดูใบลา เพื่อดูประวัติรายบุคคล · กด "พิมพ์" เพื่อออกสรุปสถิติการลา</p>

                <!-- ====== สำหรับพิมพ์: สรุปสถิติการลาของบุคลากร ====== -->
                <div id="leave-reg-print" class="print-only">
                    <div class="print-head">
                        <div class="text-center font-semibold">{{ school }}</div>
                        <div class="text-center text-rose-600">สถิติการลาของบุคลากร · ปีงบประมาณ {{ fiscalYear }}</div>
                    </div>
                    <table class="print-table">
                        <thead>
                            <tr>
                                <th rowspan="2">ที่</th>
                                <th rowspan="2">ชื่อ - นามสกุล</th>
                                <th rowspan="2">กลุ่ม</th>
                                <th v-for="t in types" :key="t.id" colspan="2">{{ t.name }}</th>
                                <th v-if="hasVacation" rowspan="2">คงเหลือ</th>
                            </tr>
                            <tr>
                                <template v-for="t in types" :key="t.id">
                                    <th>ครั้ง</th>
                                    <th>วัน</th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(p, i) in filtered" :key="p.id">
                                <td class="c">{{ i + 1 }}</td>
                                <td>{{ p.name }}</td>
                                <td>{{ p.affiliation ?? '—' }}</td>
                                <template v-for="t in types" :key="t.id">
                                    <td class="c">{{ p.byType[t.id]?.times ?? 0 }}</td>
                                    <td class="c">{{ p.byType[t.id]?.days ?? 0 }}</td>
                                </template>
                                <td v-if="hasVacation" class="c">{{ p.remaining ?? '' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.print-only {
    display: none;
}
@media print {
    body * {
        visibility: hidden;
    }
    #leave-reg-print,
    #leave-reg-print * {
        visibility: visible;
    }
    #leave-reg-print {
        display: block;
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .no-print {
        display: none !important;
    }
    .print-head {
        margin-bottom: 8px;
        font-size: 14px;
    }
    .print-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
    }
    .print-table th,
    .print-table td {
        border: 1px solid #555;
        padding: 2px 5px;
        text-align: left;
    }
    .print-table th {
        text-align: center;
        background: #f0f0f0;
    }
    .print-table td.c {
        text-align: center;
    }
}
</style>
