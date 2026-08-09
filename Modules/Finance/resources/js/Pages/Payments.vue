<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    class: String,
    classLabel: String,
    isAdvance: Boolean,
    year: Number,
    years: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    total: Number,
    usesMoneyType: Boolean,
    moneyTypes: { type: Array, default: () => [] },
    expenseCategories: { type: Array, default: () => [] },
    petitions: { type: Array, default: () => [] },
    withdrawals: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash?.success);
const money = (n) => Number(n || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const statusColor = (s) => ({ pending: 'bg-amber-50 text-amber-700', approved: 'bg-emerald-50 text-emerald-700', rejected: 'bg-rose-50 text-rose-600' }[s] || 'bg-gray-50 text-gray-500');

const viewYear = ref(props.year);
const changeYear = () => router.get(route('finance.payments.index', props.class), { year: viewYear.value }, { preserveState: false });

const showModal = ref(false);
const editingId = ref(null);
const blank = { doc_no: '', petition_id: '', withdrawal_id: '', money_type_id: '', expense_category_id: '', title: '', amount: '', payee: '', order_date: '', is_advance_return: false };
const form = useForm({ ...blank });

function openCreate() {
    editingId.value = null;
    form.clearErrors();
    form.reset();
    form.order_date = new Date().toISOString().slice(0, 10);
    showModal.value = true;
}
function openEdit(r) {
    editingId.value = r.id;
    form.clearErrors();
    Object.keys(blank).forEach((k) => (form[k] = r[k] ?? (k === 'is_advance_return' ? false : '')));
    showModal.value = true;
}
function save() {
    const opts = { preserveScroll: true, onSuccess: () => (showModal.value = false) };
    editingId.value
        ? form.put(route('finance.payments.update', editingId.value), opts)
        : form.post(route('finance.payments.store', props.class), opts);
}
const del = (r) => confirm(`ลบรายการสั่งจ่าย "${r.title}"?`) && router.delete(route('finance.payments.destroy', r.id), { preserveScroll: true });
</script>

<template>
    <Head :title="classLabel" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ classLabel }}</h2>                </div>
                <div class="flex items-center gap-2 text-sm">
                    <select v-model="viewYear" @change="changeYear" class="rounded-lg border-gray-300 text-sm">
                        <option v-for="y in years" :key="y.id" :value="y.year">ปี {{ y.year }}<span v-if="y.is_current"> (ปัจจุบัน)</span></option>
                        <option v-if="!years.some((y) => y.year === year)" :value="year">ปี {{ year }}</option>
                    </select>
                    <button @click="openCreate" class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">+ สั่งจ่าย</button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>
                <div class="rounded-2xl bg-white px-5 py-3 text-sm shadow-sm ring-1 ring-gray-100 inline-block"><span class="text-gray-400">รวมสั่งจ่ายปี {{ year }}</span><div class="text-lg font-semibold text-indigo-700">{{ money(total) }} บาท</div></div>

                <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-3 py-3">วันที่</th>
                                <th class="px-3 py-3">รายการ</th>
                                <th class="px-3 py-3">ผู้รับเงิน</th>
                                <th class="px-3 py-3 text-center">อนุมัติ</th>
                                <th class="px-3 py-3 text-center">จ่ายแล้ว</th>
                                <th class="px-3 py-3 text-right">จำนวนเงิน</th>
                                <th class="px-3 py-3 text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="rows.length === 0"><td colspan="7" class="px-6 py-12 text-center text-gray-400">ยังไม่มีรายการสั่งจ่าย</td></tr>
                            <tr v-for="r in rows" :key="r.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-3 py-3 text-gray-500">{{ r.order_date_thai ?? '—' }}<div class="text-xs text-gray-400">{{ r.doc_no }}</div></td>
                                <td class="px-3 py-3">
                                    <div class="font-medium text-gray-900">{{ r.title }}<span v-if="r.is_advance_return" class="ml-1 rounded bg-sky-50 px-1.5 text-xs text-sky-600">คืนยืม</span></div>
                                    <div class="text-xs text-gray-400">{{ r.petition_no ? 'ฎีกา ' + r.petition_no : '' }} {{ r.expense_category ?? '' }} {{ r.money_type ?? '' }}</div>
                                </td>
                                <td class="px-3 py-3 text-gray-500">{{ r.payee ?? '—' }}</td>
                                <td class="px-3 py-3 text-center"><span :class="['rounded-full px-2 py-0.5 text-xs font-semibold', statusColor(r.approval_status)]">{{ r.approval_label }}</span></td>
                                <td class="px-3 py-3 text-center"><span v-if="r.paid" class="text-emerald-500">✓</span><span v-else class="text-gray-300">·</span></td>
                                <td class="px-3 py-3 text-right font-medium text-gray-800">{{ money(r.amount) }}</td>
                                <td class="px-3 py-3 text-center">
                                    <button @click="openEdit(r)" class="text-indigo-600 hover:underline">แก้ไข</button>
                                    <button @click="del(r)" class="ml-2 text-rose-500 hover:underline">ลบ</button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="rows.length" class="bg-gray-50 font-semibold text-gray-700"><tr><td colspan="5" class="px-3 py-2 text-right">รวม</td><td class="px-3 py-2 text-right">{{ money(total) }}</td><td></td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="showModal" max-width="2xl" @close="showModal = false">
            <div class="p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800">{{ editingId ? 'แก้ไข' : '' }}{{ classLabel }}</h3>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div><label class="block text-xs text-gray-500">ที่เอกสาร</label><input v-model="form.doc_no" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div><label class="block text-xs text-gray-500">อ้างอิงเลขที่ฎีกา</label><select v-model="form.petition_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— เลือก —</option><option v-for="o in petitions" :key="o.id" :value="o.id">{{ o.label }}</option></select></div>
                    <div><label class="block text-xs text-gray-500">อ้างอิงทะเบียนขอเบิก/ขอยืม</label><select v-model="form.withdrawal_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— เลือก —</option><option v-for="o in withdrawals" :key="o.id" :value="o.id">{{ o.label }}</option></select></div>
                    <div v-if="usesMoneyType"><label class="block text-xs text-gray-500">ประเภทของเงิน <span class="text-rose-400">*</span></label><select v-model="form.money_type_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— เลือก —</option><option v-for="o in moneyTypes" :key="o.id" :value="o.id">{{ o.label }}</option></select><div v-if="form.errors.money_type_id" class="text-xs text-rose-500">{{ form.errors.money_type_id }}</div></div>
                    <div><label class="block text-xs text-gray-500">งบรายจ่าย</label><select v-model="form.expense_category_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— เลือก —</option><option v-for="o in expenseCategories" :key="o.id" :value="o.id">{{ o.label }}</option></select></div>
                    <div class="sm:col-span-2"><label class="block text-xs text-gray-500">รายการจ่าย <span class="text-rose-400">*</span></label><input v-model="form.title" class="w-full rounded-lg border-gray-300 text-sm" /><div v-if="form.errors.title" class="text-xs text-rose-500">{{ form.errors.title }}</div></div>
                    <div><label class="block text-xs text-gray-500">จำนวนเงิน <span class="text-rose-400">*</span></label><input v-model="form.amount" type="number" step="0.01" class="w-full rounded-lg border-gray-300 text-sm" /><div v-if="form.errors.amount" class="text-xs text-rose-500">{{ form.errors.amount }}</div></div>
                    <div><label class="block text-xs text-gray-500">ผู้รับเงิน</label><input v-model="form.payee" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div><label class="block text-xs text-gray-500">วันที่สั่งจ่าย</label><input v-model="form.order_date" type="date" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    <label v-if="isAdvance" class="flex items-center gap-2 text-sm text-gray-600"><input v-model="form.is_advance_return" type="checkbox" class="rounded" /> เป็นการคืนเงินยืมทดรองราชการ</label>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button @click="showModal = false" class="rounded-lg px-4 py-2 text-sm text-gray-500 hover:bg-gray-100">ยกเลิก</button>
                    <button @click="save" :disabled="form.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">บันทึก</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
