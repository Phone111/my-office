<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    year: Number,
    years: { type: Array, default: () => [] },
    allocationCheck: { type: Array, default: () => [] },
    payMain: { type: Array, default: () => [] },
    payAdvance: { type: Array, default: () => [] },
    missing: { type: Array, default: () => [] },
    petitionByVoucher: { type: Array, default: () => [] },
    petitionVsWithdrawal: { type: Array, default: () => [] },
    noPetition: { type: Array, default: () => [] },
    wrongVoucher: { type: Array, default: () => [] },
});

const money = (n) => Number(n || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const viewYear = ref(props.year);
const changeYear = () => router.get(route('finance.audit.index'), { year: viewYear.value }, { preserveState: false });

const tabs = [
    { key: 'alloc', label: '6.1–6.2 จัดสรร/เงินงวด' },
    { key: 'paymain', label: '6.3 จ่ายเงินหลัก' },
    { key: 'payadv', label: '6.4 จ่ายเงินทดรอง' },
    { key: 'missing', label: '6.5 เลขฎีกาขาด' },
    { key: 'petvoucher', label: '6.6 ฎีกา×ใบงวด' },
    { key: 'petwd', label: '6.7 ฎีกา×ขอเบิก' },
    { key: 'nopet', label: '6.8 ยังไม่วางฎีกา' },
    { key: 'wrong', label: '6.9 วางฎีกาผิด' },
];
const tab = ref('alloc');
const payColor = (s) => ({ approved: 'text-emerald-600', rejected: 'text-rose-500', pending: 'text-amber-500' }[s] || 'text-gray-400');
</script>

<template>
    <Head title="ตรวจสอบการเงิน" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">ตรวจสอบ</h2>                </div>
                <select v-model="viewYear" @change="changeYear" class="rounded-lg border-gray-300 text-sm">
                    <option v-for="y in years" :key="y.id" :value="y.year">ปี {{ y.year }}<span v-if="y.is_current"> (ปัจจุบัน)</span></option>
                    <option v-if="!years.some((y) => y.year === year)" :value="year">ปี {{ year }}</option>
                </select>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <nav class="mb-4 flex flex-wrap gap-1">
                    <button v-for="t in tabs" :key="t.key" @click="tab = t.key" :class="['rounded-lg px-3 py-1.5 text-xs font-medium', tab === t.key ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-100 hover:bg-gray-50']">{{ t.label }}</button>
                </nav>

                <div class="overflow-x-auto rounded-2xl bg-white p-1 shadow-sm ring-1 ring-gray-100">
                    <!-- 6.1-6.2 -->
                    <table v-show="tab === 'alloc'" class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="text-left text-xs text-gray-400"><tr><th class="px-3 py-2 text-center">ใบงวด</th><th class="px-3 py-2">รายการ</th><th class="px-3 py-2 text-right">จัดสรร</th><th class="px-3 py-2 text-right">เบิกฎีกา</th><th class="px-3 py-2 text-right">คืนคลัง</th><th class="px-3 py-2 text-right">คงเหลือ</th><th class="px-3 py-2 text-right">%เบิก</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="r in allocationCheck" :key="r.voucher_no"><td class="px-3 py-2 text-center font-semibold text-indigo-700">{{ r.voucher_no }}</td><td class="px-3 py-2 text-gray-700">{{ r.title }}</td><td class="px-3 py-2 text-right">{{ money(r.allocated) }}</td><td class="px-3 py-2 text-right">{{ money(r.petitioned) }}</td><td class="px-3 py-2 text-right">{{ money(r.returned) }}</td><td class="px-3 py-2 text-right font-medium" :class="r.complete ? 'text-emerald-600' : 'text-gray-800'">{{ money(r.remaining) }}</td><td class="px-3 py-2 text-right text-gray-500">{{ r.percent }}%</td></tr>
                            <tr v-if="allocationCheck.length === 0"><td colspan="7" class="py-10 text-center text-gray-400">ไม่มีข้อมูล</td></tr>
                        </tbody>
                    </table>
                    <!-- 6.3 -->
                    <table v-show="tab === 'paymain'" class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="text-left text-xs text-gray-400"><tr><th class="px-3 py-2">ประเภท</th><th class="px-3 py-2">รายการ</th><th class="px-3 py-2 text-right">จำนวนเงิน</th><th class="px-3 py-2 text-center">อนุมัติ</th><th class="px-3 py-2 text-center">จ่ายแล้ว</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(r, i) in payMain" :key="i"><td class="px-3 py-2 text-gray-500">{{ r.class }}</td><td class="px-3 py-2 text-gray-700">{{ r.title }}</td><td class="px-3 py-2 text-right">{{ money(r.amount) }}</td><td class="px-3 py-2 text-center" :class="payColor(r.approval_status)">{{ r.approval }}</td><td class="px-3 py-2 text-center">{{ r.paid ? '✓' : '·' }}</td></tr>
                            <tr v-if="payMain.length === 0"><td colspan="5" class="py-10 text-center text-gray-400">ไม่มีข้อมูล</td></tr>
                        </tbody>
                    </table>
                    <!-- 6.4 -->
                    <table v-show="tab === 'payadv'" class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="text-left text-xs text-gray-400"><tr><th class="px-3 py-2">รายการ</th><th class="px-3 py-2 text-center">คืนยืม</th><th class="px-3 py-2 text-right">จำนวนเงิน</th><th class="px-3 py-2 text-center">อนุมัติ</th><th class="px-3 py-2 text-center">จ่ายแล้ว</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(r, i) in payAdvance" :key="i"><td class="px-3 py-2 text-gray-700">{{ r.title }}</td><td class="px-3 py-2 text-center">{{ r.is_return ? '✓' : '·' }}</td><td class="px-3 py-2 text-right">{{ money(r.amount) }}</td><td class="px-3 py-2 text-center" :class="payColor(r.approval_status)">{{ r.approval }}</td><td class="px-3 py-2 text-center">{{ r.paid ? '✓' : '·' }}</td></tr>
                            <tr v-if="payAdvance.length === 0"><td colspan="5" class="py-10 text-center text-gray-400">ไม่มีข้อมูล</td></tr>
                        </tbody>
                    </table>
                    <!-- 6.5 -->
                    <div v-show="tab === 'missing'" class="p-4">
                        <p v-if="missing.length === 0" class="py-8 text-center text-emerald-600">ไม่มีเลขฎีกาขาดหาย ✓</p>
                        <div v-else class="flex flex-wrap gap-2">
                            <span class="text-sm text-gray-500">เลขฎีกาที่ยังไม่มีในระบบ:</span>
                            <span v-for="n in missing" :key="n" class="rounded-md bg-rose-50 px-2 py-0.5 text-sm font-semibold text-rose-600">{{ n }}</span>
                        </div>
                    </div>
                    <!-- 6.6 -->
                    <table v-show="tab === 'petvoucher'" class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="text-left text-xs text-gray-400"><tr><th class="px-3 py-2 text-center">ใบงวด</th><th class="px-3 py-2 text-center">จำนวนฎีกา</th><th class="px-3 py-2 text-right">เบิกตามฎีกา</th><th class="px-3 py-2 text-right">รับจริง</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(r, i) in petitionByVoucher" :key="i"><td class="px-3 py-2 text-center font-semibold text-indigo-700">{{ r.voucher_no }}</td><td class="px-3 py-2 text-center text-gray-500">{{ r.count }}</td><td class="px-3 py-2 text-right">{{ money(r.amount) }}</td><td class="px-3 py-2 text-right">{{ money(r.net) }}</td></tr>
                            <tr v-if="petitionByVoucher.length === 0"><td colspan="4" class="py-10 text-center text-gray-400">ไม่มีข้อมูล</td></tr>
                        </tbody>
                    </table>
                    <!-- 6.7 -->
                    <table v-show="tab === 'petwd'" class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="text-left text-xs text-gray-400"><tr><th class="px-3 py-2">เลขฎีกา</th><th class="px-3 py-2">รายการ</th><th class="px-3 py-2 text-right">เงินฎีกา</th><th class="px-3 py-2 text-right">ก่อหนี้ (ขอเบิก)</th><th class="px-3 py-2 text-right">ส่วนต่าง</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(r, i) in petitionVsWithdrawal" :key="i"><td class="px-3 py-2 font-medium text-gray-700">{{ r.petition_no }}</td><td class="px-3 py-2 text-gray-500">{{ r.title }}</td><td class="px-3 py-2 text-right">{{ money(r.petition_amount) }}</td><td class="px-3 py-2 text-right">{{ money(r.committed) }}</td><td class="px-3 py-2 text-right" :class="Math.abs(r.diff) < 0.01 ? 'text-emerald-600' : 'text-rose-500'">{{ money(r.diff) }}</td></tr>
                            <tr v-if="petitionVsWithdrawal.length === 0"><td colspan="5" class="py-10 text-center text-gray-400">ไม่มีข้อมูล</td></tr>
                        </tbody>
                    </table>
                    <!-- 6.8 -->
                    <table v-show="tab === 'nopet'" class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="text-left text-xs text-gray-400"><tr><th class="px-3 py-2">ที่เอกสาร</th><th class="px-3 py-2">รายการ</th><th class="px-3 py-2">ประเภท</th><th class="px-3 py-2 text-right">จำนวนเงิน</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(r, i) in noPetition" :key="i"><td class="px-3 py-2 text-gray-500">{{ r.doc_no ?? '—' }}</td><td class="px-3 py-2 text-gray-700">{{ r.title }}</td><td class="px-3 py-2 text-gray-500">{{ r.kind }}</td><td class="px-3 py-2 text-right">{{ money(r.amount) }}</td></tr>
                            <tr v-if="noPetition.length === 0"><td colspan="4" class="py-10 text-center text-emerald-600">วางฎีกาครบทุกรายการ ✓</td></tr>
                        </tbody>
                    </table>
                    <!-- 6.9 -->
                    <table v-show="tab === 'wrong'" class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="text-left text-xs text-gray-400"><tr><th class="px-3 py-2">รายการ</th><th class="px-3 py-2">เลขฎีกา</th><th class="px-3 py-2">งบรายจ่ายขอเบิก</th><th class="px-3 py-2 text-right">จำนวนเงิน</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(r, i) in wrongVoucher" :key="i"><td class="px-3 py-2 text-gray-700">{{ r.title }}</td><td class="px-3 py-2 text-gray-500">{{ r.petition_no }}</td><td class="px-3 py-2 text-rose-500">{{ r.withdrawal_expense ?? '—' }}</td><td class="px-3 py-2 text-right">{{ money(r.amount) }}</td></tr>
                            <tr v-if="wrongVoucher.length === 0"><td colspan="4" class="py-10 text-center text-emerald-600">ไม่พบงบรายจ่ายไม่ตรงกับฎีกา ✓</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
