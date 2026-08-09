<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    year: { type: Number, default: 0 }, // พ.ศ. (ปีงบประมาณ)
    period: { type: String, default: 'all' }, // all | h1 | h2
    summary: { type: Object, default: () => ({}) },
    types: { type: Array, default: () => [] }, // [{id,name}]
    people: { type: Array, default: () => [] }, // [{id,name,position,group,byType:{[id]:{times,days}}}]
    onLeaveToday: { type: Array, default: () => [] },
    approvedList: { type: Array, default: () => [] },
    rejectedList: { type: Array, default: () => [] },
});

const selectedYear = ref(props.year);

const periods = [
    { key: 'all', label: 'ทั้งปี' },
    { key: 'h1', label: 'ครั้งที่ 1 (ต.ค.–มี.ค.)' },
    { key: 'h2', label: 'ครั้งที่ 2 (เม.ย.–ก.ย.)' },
];

const go = (patch = {}) =>
    router.get(
        route('reports.leave-statistics'),
        { year: selectedYear.value, period: props.period, ...patch },
        { preserveScroll: true },
    );

const cards = [
    { key: 'my_pending', label: 'รอฉันอนุมัติ', cls: 'text-amber-600', href: route('leave.requests.inbox') },
    { key: 'approved', label: 'อนุมัติแล้ว (ช่วงนี้)', cls: 'text-emerald-600', list: 'approved' },
    { key: 'rejected', label: 'ไม่อนุมัติ (ช่วงนี้)', cls: 'text-rose-600', list: 'rejected' },
];

const search = ref('');

const listModal = ref(null);
const openList = (c) => {
    listModal.value = { title: c.label, items: c.list === 'approved' ? props.approvedList : props.rejectedList };
};

const totalDays = (p) => props.types.reduce((s, t) => s + (p.byType[t.id]?.days ?? 0), 0);

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.people.filter(
        (p) => !q || p.name.toLowerCase().includes(q) || (p.group ?? '').toLowerCase().includes(q) || (p.position ?? '').toLowerCase().includes(q),
    );
});

// รวมทั้งหน่วยงาน (แถวล่างสุด)
const grandTotal = computed(() => {
    const acc = {};
    props.types.forEach((t) => (acc[t.id] = { times: 0, days: 0 }));
    props.people.forEach((p) =>
        props.types.forEach((t) => {
            acc[t.id].times += p.byType[t.id]?.times ?? 0;
            acc[t.id].days += p.byType[t.id]?.days ?? 0;
        }),
    );
    return acc;
});

const periodLabel = computed(() => periods.find((p) => p.key === props.period)?.label ?? 'ทั้งปี');
const printPage = () => window.print();
</script>

<template>
    <Head title="สถิติการลาของบุคลากร" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">สถิติการลาของบุคลากร</h2>
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-gray-500">ปีงบประมาณ (พ.ศ.)</span>
                    <input v-model.number="selectedYear" type="number" class="w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="go()" />
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- สรุปสถานะ (no-print) -->
                <div class="no-print grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <component
                        :is="c.href ? Link : 'button'"
                        v-for="c in cards"
                        :key="c.key"
                        :href="c.href"
                        :type="c.href ? undefined : 'button'"
                        class="group block w-full rounded-2xl bg-white p-5 text-left shadow-sm ring-1 ring-gray-100 transition hover:shadow-md hover:ring-amber-200"
                        @click="c.list ? openList(c) : null"
                    >
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-gray-500">{{ c.label }}</p>
                            <span class="text-xs font-medium text-amber-600 transition group-hover:underline">ดูรายการ →</span>
                        </div>
                        <p class="mt-2 text-3xl font-bold" :class="c.cls">{{ summary[c.key] ?? 0 }}</p>
                    </component>
                </div>

                <!-- แท็บช่วงเวลา + ค้นหา + พิมพ์ (no-print) -->
                <div class="no-print flex flex-wrap items-center justify-between gap-3">
                    <div class="inline-flex rounded-xl bg-gray-100 p-1">
                        <Link
                            v-for="p in periods"
                            :key="p.key"
                            :href="route('reports.leave-statistics', { year: selectedYear, period: p.key })"
                            preserve-scroll
                            class="rounded-lg px-4 py-1.5 text-sm font-medium transition"
                            :class="period === p.key ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                        >
                            {{ p.label }}
                        </Link>
                    </div>
                    <div class="flex items-center gap-2">
                        <TextInput v-model="search" type="text" placeholder="ค้นหา ชื่อ / กลุ่ม / ตำแหน่ง" class="w-60 text-sm" />
                        <button type="button" class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-500" @click="printPage">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" /></svg>
                            พิมพ์
                        </button>
                    </div>
                </div>

                <!-- ตาราง matrix (พิมพ์เฉพาะส่วนนี้) -->
                <div id="leave-stat-print" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">
                        ทะเบียนแสดงสิทธิวันลาของบุคลากร · ปีงบประมาณ {{ year }} · {{ periodLabel }}
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse text-sm">
                            <thead>
                                <tr class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <th rowspan="2" class="border border-gray-100 px-3 py-2 text-center">ที่</th>
                                    <th rowspan="2" class="border border-gray-100 px-4 py-2 text-left">ชื่อ - นามสกุล</th>
                                    <th rowspan="2" class="border border-gray-100 px-4 py-2 text-left">ตำแหน่ง / กลุ่ม</th>
                                    <th v-for="t in types" :key="t.id" colspan="2" class="border border-gray-100 px-3 py-2 text-center">{{ t.name }}</th>
                                    <th rowspan="2" class="border border-gray-100 px-3 py-2 text-center">รวม (วัน)</th>
                                </tr>
                                <tr class="bg-gray-50 text-[11px] font-medium text-gray-500">
                                    <template v-for="t in types" :key="t.id">
                                        <th class="border border-gray-100 px-3 py-1 text-center font-medium">ครั้ง</th>
                                        <th class="border border-gray-100 px-3 py-1 text-center font-medium">วัน</th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="filtered.length === 0"><td :colspan="4 + types.length * 2" class="px-6 py-10 text-center text-sm text-gray-400">ไม่พบบุคลากร</td></tr>
                                <tr v-for="(p, i) in filtered" :key="p.id" class="text-gray-700 hover:bg-indigo-50/40">
                                    <td class="border border-gray-100 px-3 py-2 text-center text-gray-400">{{ i + 1 }}</td>
                                    <td class="border border-gray-100 px-4 py-2 font-medium text-gray-900">{{ p.name }}</td>
                                    <td class="border border-gray-100 px-4 py-2 text-gray-500">{{ p.position ?? '—' }}<span v-if="p.group" class="text-gray-300"> · {{ p.group }}</span></td>
                                    <template v-for="t in types" :key="t.id">
                                        <td class="border border-gray-100 px-3 py-2 text-center tabular-nums" :class="(p.byType[t.id]?.times ?? 0) ? 'text-gray-700' : 'text-gray-300'">{{ p.byType[t.id]?.times ?? 0 }}</td>
                                        <td class="border border-gray-100 px-3 py-2 text-center tabular-nums" :class="(p.byType[t.id]?.days ?? 0) ? 'font-semibold text-indigo-600' : 'text-gray-300'">{{ p.byType[t.id]?.days ?? 0 }}</td>
                                    </template>
                                    <td class="border border-gray-100 px-3 py-2 text-center font-bold tabular-nums" :class="totalDays(p) ? 'text-gray-800' : 'text-gray-300'">{{ totalDays(p) }}</td>
                                </tr>
                            </tbody>
                            <tfoot v-if="filtered.length">
                                <tr class="bg-gray-50 text-xs font-semibold text-gray-600">
                                    <td colspan="3" class="border border-gray-100 px-4 py-2 text-right">รวมทั้งหน่วยงาน</td>
                                    <template v-for="t in types" :key="t.id">
                                        <td class="border border-gray-100 px-3 py-2 text-center tabular-nums">{{ grandTotal[t.id].times }}</td>
                                        <td class="border border-gray-100 px-3 py-2 text-center tabular-nums">{{ grandTotal[t.id].days }}</td>
                                    </template>
                                    <td class="border border-gray-100 px-3 py-2 text-center tabular-nums">{{ Object.values(grandTotal).reduce((s, v) => s + v.days, 0) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- รายการใบลา อนุมัติ/ไม่อนุมัติ -->
        <Modal :show="listModal !== null" max-width="2xl" @close="listModal = null">
            <div v-if="listModal" class="p-6">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ listModal.title }} ({{ listModal.items.length }})</h2>
                <p v-if="listModal.items.length === 0" class="py-6 text-center text-sm text-gray-400">ไม่มีรายการ</p>
                <div v-else class="max-h-[60vh] overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-4 py-2 text-left">ชื่อ</th>
                                <th class="px-4 py-2 text-left">ประเภท</th>
                                <th class="px-4 py-2 text-left">ช่วงวันที่</th>
                                <th class="px-4 py-2 text-center">วัน</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(l, i) in listModal.items" :key="i">
                                <td class="px-4 py-2 font-medium text-gray-900">{{ l.name }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ l.type }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ l.start }} – {{ l.end }}</td>
                                <td class="px-4 py-2 text-center text-gray-600">{{ l.days }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="listModal = null">ปิด</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #leave-stat-print,
    #leave-stat-print * {
        visibility: visible;
    }
    #leave-stat-print {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none;
        --tw-ring-color: transparent;
    }
    .no-print {
        display: none !important;
    }
}
</style>
