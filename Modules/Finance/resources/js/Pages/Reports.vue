<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    year: Number,
    years: { type: Array, default: () => [] },
    allocationReport: { type: Array, default: () => [] },
    allocationTotal: Number,
    projectReport: { type: Array, default: () => [] },
    byExpense: { type: Array, default: () => [] },
    totalPaid: Number,
    balanceByClass: { type: Array, default: () => [] },
    cashBook: { type: Array, default: () => [] },
    budget: { type: Object, default: () => ({}) },
    nonbudget: { type: Array, default: () => [] },
    stateRevenue: { type: Array, default: () => [] },
    loans: { type: Array, default: () => [] },
    loanTotal: Number,
});

const money = (n) => Number(n || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const viewYear = ref(props.year);
const changeYear = () => router.get(route('finance.reports.index'), { year: viewYear.value }, { preserveState: false });
const doPrint = () => window.print();

const tabs = [
    { key: 'alloc', label: '7.1/7.3 จัดสรร/ทะเบียนเงินงวด' },
    { key: 'project', label: '7.2 ตามโครงการ' },
    { key: 'expense', label: '7.4/7.5 ตามงบรายจ่าย' },
    { key: 'daily', label: '7.6 เงินคงเหลือ' },
    { key: 'cash', label: '7.7 สมุดเงินสด' },
    { key: 'budget', label: '7.8 เงินงบประมาณ' },
    { key: 'nonbudget', label: '7.9 เงินนอกงบ' },
    { key: 'state', label: '7.10 รายได้แผ่นดิน' },
    { key: 'loans', label: '7.11 ลูกหนี้เงินยืม' },
];
const tab = ref('alloc');
</script>

<template>
    <Head title="รายงานการเงิน" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3 print:hidden">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">รายงาน</h2>                </div>
                <div class="flex items-center gap-2 text-sm">
                    <select v-model="viewYear" @change="changeYear" class="rounded-lg border-gray-300 text-sm">
                        <option v-for="y in years" :key="y.id" :value="y.year">ปี {{ y.year }}<span v-if="y.is_current"> (ปัจจุบัน)</span></option>
                        <option v-if="!years.some((y) => y.year === year)" :value="year">ปี {{ year }}</option>
                    </select>
                    <button @click="doPrint" class="rounded-lg bg-gray-700 px-4 py-2 font-semibold text-white hover:bg-gray-800">พิมพ์</button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <nav class="mb-4 flex flex-wrap gap-1 print:hidden">
                    <button v-for="t in tabs" :key="t.key" @click="tab = t.key" :class="['rounded-lg px-3 py-1.5 text-xs font-medium', tab === t.key ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-100 hover:bg-gray-50']">{{ t.label }}</button>
                </nav>

                <div class="overflow-x-auto rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                    <!-- 7.1/7.3 -->
                    <div v-show="tab === 'alloc'">
                        <h3 class="mb-2 font-semibold text-gray-700">รายงานการจัดสรรงบประมาณ / ทะเบียนเงินงวด ปี {{ year }}</h3>
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="text-left text-xs text-gray-400"><tr><th class="px-2 py-2 text-center">ใบงวด</th><th class="px-2 py-2">วันที่</th><th class="px-2 py-2">แผนงาน/โครงการ</th><th class="px-2 py-2">รายการ</th><th class="px-2 py-2 text-right">จำนวนเงิน</th></tr></thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="r in allocationReport" :key="r.voucher_no"><td class="px-2 py-2 text-center font-semibold text-indigo-700">{{ r.voucher_no }}</td><td class="px-2 py-2 text-gray-500">{{ r.date ?? '—' }}</td><td class="px-2 py-2 text-gray-500">{{ r.plan ?? r.project ?? '—' }}</td><td class="px-2 py-2 text-gray-700">{{ r.title }}</td><td class="px-2 py-2 text-right">{{ money(r.amount) }}</td></tr>
                                <tr v-if="allocationReport.length === 0"><td colspan="5" class="py-10 text-center text-gray-400">ไม่มีข้อมูล</td></tr>
                            </tbody>
                            <tfoot v-if="allocationReport.length" class="font-semibold text-gray-700"><tr><td colspan="4" class="px-2 py-2 text-right">รวม</td><td class="px-2 py-2 text-right">{{ money(allocationTotal) }}</td></tr></tfoot>
                        </table>
                    </div>
                    <!-- 7.2 -->
                    <div v-show="tab === 'project'">
                        <h3 class="mb-2 font-semibold text-gray-700">รายงานการใช้จ่ายจำแนกตามโครงการ</h3>
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="text-left text-xs text-gray-400"><tr><th class="px-2 py-2">โครงการ</th><th class="px-2 py-2 text-right">ก่อหนี้ (ขอเบิก)</th><th class="px-2 py-2 text-right">เบิกตามฎีกา</th><th class="px-2 py-2 text-right">รับจริง</th></tr></thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(r, i) in projectReport" :key="i"><td class="px-2 py-2 text-gray-700">{{ r.name }}</td><td class="px-2 py-2 text-right">{{ money(r.committed) }}</td><td class="px-2 py-2 text-right">{{ money(r.petitioned) }}</td><td class="px-2 py-2 text-right">{{ money(r.net) }}</td></tr>
                                <tr v-if="projectReport.length === 0"><td colspan="4" class="py-10 text-center text-gray-400">ไม่มีข้อมูล</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- 7.4/7.5 -->
                    <div v-show="tab === 'expense'">
                        <h3 class="mb-2 font-semibold text-gray-700">รายงานการใช้จ่ายจำแนกตามงบรายจ่าย (จ่ายแล้ว)</h3>
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="text-left text-xs text-gray-400"><tr><th class="px-2 py-2">งบรายจ่าย</th><th class="px-2 py-2 text-right">จำนวนเงิน</th><th class="px-2 py-2 text-right">ร้อยละ</th></tr></thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(r, i) in byExpense" :key="i"><td class="px-2 py-2 text-gray-700">{{ r.name }}</td><td class="px-2 py-2 text-right">{{ money(r.amount) }}</td><td class="px-2 py-2 text-right text-gray-500">{{ r.percent }}%</td></tr>
                                <tr v-if="byExpense.length === 0"><td colspan="3" class="py-10 text-center text-gray-400">ยังไม่มีการจ่ายเงิน</td></tr>
                            </tbody>
                            <tfoot v-if="byExpense.length" class="font-semibold text-gray-700"><tr><td class="px-2 py-2 text-right">รวมจ่าย</td><td class="px-2 py-2 text-right">{{ money(totalPaid) }}</td><td></td></tr></tfoot>
                        </table>
                    </div>
                    <!-- 7.6 -->
                    <div v-show="tab === 'daily'">
                        <h3 class="mb-2 font-semibold text-gray-700">รายงานเงินคงเหลือประจำวัน</h3>
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="text-left text-xs text-gray-400"><tr><th class="px-2 py-2">ประเภทเงิน</th><th class="px-2 py-2 text-right">รับ</th><th class="px-2 py-2 text-right">จ่าย</th><th class="px-2 py-2 text-right">คงเหลือ</th></tr></thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(r, i) in balanceByClass" :key="i"><td class="px-2 py-2 text-gray-700">{{ r.class }}</td><td class="px-2 py-2 text-right text-emerald-600">{{ money(r.received) }}</td><td class="px-2 py-2 text-right text-rose-500">{{ money(r.paid) }}</td><td class="px-2 py-2 text-right font-medium">{{ money(r.balance) }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- 7.7 -->
                    <div v-show="tab === 'cash'">
                        <h3 class="mb-2 font-semibold text-gray-700">สมุดเงินสด (เงินงบประมาณ)</h3>
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="text-left text-xs text-gray-400"><tr><th class="px-2 py-2">วันที่</th><th class="px-2 py-2">รายการ</th><th class="px-2 py-2 text-right">รับ</th><th class="px-2 py-2 text-right">จ่าย</th><th class="px-2 py-2 text-right">คงเหลือ</th></tr></thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(r, i) in cashBook" :key="i"><td class="px-2 py-2 text-gray-500">{{ r.date ?? '—' }}</td><td class="px-2 py-2 text-gray-700">{{ r.title }}</td><td class="px-2 py-2 text-right text-emerald-600">{{ r.in ? money(r.in) : '' }}</td><td class="px-2 py-2 text-right text-rose-500">{{ r.out ? money(r.out) : '' }}</td><td class="px-2 py-2 text-right font-medium">{{ money(r.balance) }}</td></tr>
                                <tr v-if="cashBook.length === 0"><td colspan="5" class="py-10 text-center text-gray-400">ไม่มีข้อมูล</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- 7.8 -->
                    <div v-show="tab === 'budget'" class="space-y-2">
                        <h3 class="mb-2 font-semibold text-gray-700">รายงานเงินงบประมาณ</h3>
                        <div class="flex flex-wrap gap-4">
                            <div class="rounded-xl bg-emerald-50 px-5 py-3"><div class="text-xs text-emerald-600">รับ</div><div class="text-lg font-semibold text-emerald-700">{{ money(budget.received) }}</div></div>
                            <div class="rounded-xl bg-rose-50 px-5 py-3"><div class="text-xs text-rose-500">จ่าย</div><div class="text-lg font-semibold text-rose-600">{{ money(budget.paid) }}</div></div>
                            <div class="rounded-xl bg-indigo-50 px-5 py-3"><div class="text-xs text-indigo-500">คงเหลือ</div><div class="text-lg font-semibold text-indigo-700">{{ money(budget.balance) }}</div></div>
                        </div>
                    </div>
                    <!-- 7.9 -->
                    <div v-show="tab === 'nonbudget'">
                        <h3 class="mb-2 font-semibold text-gray-700">รายงานเงินนอกงบประมาณ</h3>
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="text-left text-xs text-gray-400"><tr><th class="px-2 py-2">ประเภทเงิน</th><th class="px-2 py-2 text-right">รับ</th><th class="px-2 py-2 text-right">จ่าย</th><th class="px-2 py-2 text-right">คงเหลือ</th></tr></thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(r, i) in nonbudget" :key="i"><td class="px-2 py-2 text-gray-700">{{ r.name }}</td><td class="px-2 py-2 text-right text-emerald-600">{{ money(r.received) }}</td><td class="px-2 py-2 text-right text-rose-500">{{ money(r.paid) }}</td><td class="px-2 py-2 text-right font-medium">{{ money(r.balance) }}</td></tr>
                                <tr v-if="nonbudget.length === 0"><td colspan="4" class="py-10 text-center text-gray-400">ไม่มีข้อมูล</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- 7.10 -->
                    <div v-show="tab === 'state'">
                        <h3 class="mb-2 font-semibold text-gray-700">รายงานเงินรายได้แผ่นดิน</h3>
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="text-left text-xs text-gray-400"><tr><th class="px-2 py-2">ประเภทเงิน</th><th class="px-2 py-2 text-right">รับ</th><th class="px-2 py-2 text-right">จ่าย/นำส่ง</th><th class="px-2 py-2 text-right">คงเหลือ</th></tr></thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(r, i) in stateRevenue" :key="i"><td class="px-2 py-2 text-gray-700">{{ r.name }}</td><td class="px-2 py-2 text-right text-emerald-600">{{ money(r.received) }}</td><td class="px-2 py-2 text-right text-rose-500">{{ money(r.paid) }}</td><td class="px-2 py-2 text-right font-medium">{{ money(r.balance) }}</td></tr>
                                <tr v-if="stateRevenue.length === 0"><td colspan="4" class="py-10 text-center text-gray-400">ไม่มีข้อมูล</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- 7.11 -->
                    <div v-show="tab === 'loans'">
                        <h3 class="mb-2 font-semibold text-gray-700">รายงานลูกหนี้เงินยืม (ยังไม่ส่งใช้)</h3>
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="text-left text-xs text-gray-400"><tr><th class="px-2 py-2">ผู้ยืม</th><th class="px-2 py-2">ประเภท</th><th class="px-2 py-2">รายการ</th><th class="px-2 py-2 text-right">จำนวนเงิน</th></tr></thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(r, i) in loans" :key="i"><td class="px-2 py-2 text-gray-700">{{ r.borrower ?? '—' }}</td><td class="px-2 py-2 text-gray-500">{{ r.kind }}</td><td class="px-2 py-2 text-gray-500">{{ r.title }}</td><td class="px-2 py-2 text-right">{{ money(r.amount) }}</td></tr>
                                <tr v-if="loans.length === 0"><td colspan="4" class="py-10 text-center text-emerald-600">ไม่มีลูกหนี้เงินยืมค้าง ✓</td></tr>
                            </tbody>
                            <tfoot v-if="loans.length" class="font-semibold text-gray-700"><tr><td colspan="3" class="px-2 py-2 text-right">รวม</td><td class="px-2 py-2 text-right">{{ money(loanTotal) }}</td></tr></tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
