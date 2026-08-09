<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    year: Number,
    years: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    pendingTotal: Number,
});

const flash = computed(() => usePage().props.flash?.success);
const money = (n) => Number(n || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const statusColor = (s) => ({ pending: 'bg-amber-50 text-amber-700', approved: 'bg-emerald-50 text-emerald-700', rejected: 'bg-rose-50 text-rose-600' }[s] || 'bg-gray-50 text-gray-500');

const viewYear = ref(props.year);
const changeYear = () => router.get(route('finance.payments.approvals'), { year: viewYear.value }, { preserveState: false });

const approve = (r) => router.post(route('finance.payments.approve', r.id), { decision: 'approved' }, { preserveScroll: true });
function reject(r) {
    const note = window.prompt('เหตุผลที่ไม่อนุมัติ (ถ้ามี):', '');
    if (note === null) return;
    router.post(route('finance.payments.approve', r.id), { decision: 'rejected', approve_note: note }, { preserveScroll: true });
}
</script>

<template>
    <Head title="อนุมัติจ่ายเงิน" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">อนุมัติจ่ายเงิน</h2>                </div>
                <select v-model="viewYear" @change="changeYear" class="rounded-lg border-gray-300 text-sm">
                    <option v-for="y in years" :key="y.id" :value="y.year">ปี {{ y.year }}<span v-if="y.is_current"> (ปัจจุบัน)</span></option>
                    <option v-if="!years.some((y) => y.year === year)" :value="year">ปี {{ year }}</option>
                </select>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>
                <div class="rounded-2xl bg-white px-5 py-3 text-sm shadow-sm ring-1 ring-gray-100 inline-block"><span class="text-gray-400">รออนุมัติ</span><div class="text-lg font-semibold text-amber-600">{{ money(pendingTotal) }} บาท</div></div>

                <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                            <tr><th class="px-3 py-3">วันที่</th><th class="px-3 py-3">ประเภท</th><th class="px-3 py-3">รายการ</th><th class="px-3 py-3 text-right">จำนวนเงิน</th><th class="px-3 py-3 text-center">สถานะ</th><th class="px-3 py-3 text-center">ดำเนินการ</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="rows.length === 0"><td colspan="6" class="px-6 py-12 text-center text-gray-400">ไม่มีรายการ</td></tr>
                            <tr v-for="r in rows" :key="r.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-3 py-3 text-gray-500">{{ r.order_date_thai ?? '—' }}</td>
                                <td class="px-3 py-3 text-gray-500">{{ r.class_label }}</td>
                                <td class="px-3 py-3"><div class="font-medium text-gray-900">{{ r.title }}</div><div v-if="r.approve_note" class="text-xs text-rose-400">หมายเหตุ: {{ r.approve_note }}</div></td>
                                <td class="px-3 py-3 text-right font-medium text-gray-800">{{ money(r.amount) }}</td>
                                <td class="px-3 py-3 text-center"><span :class="['rounded-full px-2 py-0.5 text-xs font-semibold', statusColor(r.approval_status)]">{{ r.approval_label }}</span></td>
                                <td class="px-3 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button v-if="r.approval_status !== 'approved'" @click="approve(r)" class="rounded-md bg-emerald-500 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-600">อนุมัติ</button>
                                        <button v-if="r.approval_status !== 'rejected'" @click="reject(r)" class="rounded-md bg-rose-500 px-2.5 py-1 text-xs font-semibold text-white hover:bg-rose-600">ไม่อนุมัติ</button>
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
