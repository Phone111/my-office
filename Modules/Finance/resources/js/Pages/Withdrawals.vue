<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    year: Number,
    years: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    total: Number,
    kinds: { type: Object, default: () => ({}) },
    options: { type: Object, required: true },
});

const flash = computed(() => usePage().props.flash?.success);
const money = (n) => Number(n || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const viewYear = ref(props.year);
const changeYear = () => router.get(route('finance.withdrawals.index'), { year: viewYear.value }, { preserveState: false });

const showModal = ref(false);
const editingId = ref(null);
const blank = { doc_no: '', kind: 'withdraw', title: '', project_id: '', activity_id: '', expense_category_id: '', amount: '', borrower: '', doc_date: '' };
const form = useForm({ ...blank });

function openCreate() {
    editingId.value = null;
    form.clearErrors();
    form.reset();
    showModal.value = true;
}
function openEdit(r) {
    editingId.value = r.id;
    form.clearErrors();
    form.doc_no = r.doc_no ?? '';
    form.kind = r.kind;
    form.title = r.title;
    form.project_id = r.project_id ?? '';
    form.activity_id = r.activity_id ?? '';
    form.expense_category_id = r.expense_category_id ?? '';
    form.amount = r.amount;
    form.borrower = r.borrower ?? '';
    showModal.value = true;
}
function save() {
    const opts = { preserveScroll: true, onSuccess: () => (showModal.value = false) };
    editingId.value
        ? form.put(route('finance.withdrawals.update', editingId.value), opts)
        : form.post(route('finance.withdrawals.store'), opts);
}
const settle = (r) => router.post(route('finance.withdrawals.settle', r.id), {}, { preserveScroll: true });
const del = (r) => confirm(`ลบรายการ "${r.title}"?`) && router.delete(route('finance.withdrawals.destroy', r.id), { preserveScroll: true });
</script>

<template>
    <Head title="ทะเบียนขอเบิก/ขอยืม" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">ทะเบียนขอเบิก/ขอยืมโครงการ</h2>                </div>
                <div class="flex items-center gap-2 text-sm">
                    <select v-model="viewYear" @change="changeYear" class="rounded-lg border-gray-300 text-sm">
                        <option v-for="y in years" :key="y.id" :value="y.year">ปี {{ y.year }}<span v-if="y.is_current"> (ปัจจุบัน)</span></option>
                        <option v-if="!years.some((y) => y.year === year)" :value="year">ปี {{ year }}</option>
                    </select>
                    <button @click="openCreate" class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">+ ลงทะเบียน</button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>
                <div class="rounded-2xl bg-white px-5 py-3 text-sm shadow-sm ring-1 ring-gray-100 inline-block">
                    <span class="text-gray-400">รวมก่อหนี้ผูกพันปี {{ year }}</span>
                    <div class="text-lg font-semibold text-indigo-700">{{ money(total) }} บาท</div>
                </div>

                <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-3 py-3">สถานะ</th>
                                <th class="px-3 py-3">รายการ</th>
                                <th class="px-3 py-3">โครงการ / งบรายจ่าย</th>
                                <th class="px-3 py-3">ผู้ขอ</th>
                                <th class="px-3 py-3 text-center">ฎีกา</th>
                                <th class="px-3 py-3 text-right">จำนวนเงิน</th>
                                <th class="px-3 py-3 text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="rows.length === 0"><td colspan="7" class="px-6 py-12 text-center text-gray-400">ยังไม่มีรายการ</td></tr>
                            <tr v-for="r in rows" :key="r.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-3 py-3">
                                    <span v-if="!r.is_borrow" class="rounded-full bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700">{{ r.kind_label }}</span>
                                    <span v-else :class="['rounded-full px-2 py-0.5 text-xs font-semibold', r.settled ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-600']">{{ r.kind_label }}{{ r.settled ? ' · ส่งใช้แล้ว' : '' }}</span>
                                    <div v-if="r.doc_no" class="mt-0.5 text-xs text-gray-400">{{ r.doc_no }}</div>
                                </td>
                                <td class="px-3 py-3 font-medium text-gray-900">{{ r.title }}</td>
                                <td class="px-3 py-3 text-gray-500">{{ r.project ?? '—' }}<div class="text-xs text-gray-400">{{ r.expense_category ?? '' }}</div></td>
                                <td class="px-3 py-3 text-gray-500">{{ r.borrower ?? '—' }}</td>
                                <td class="px-3 py-3 text-center text-gray-500">{{ r.petition_no ?? '—' }}</td>
                                <td class="px-3 py-3 text-right font-medium text-gray-800">{{ money(r.amount) }}</td>
                                <td class="px-3 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button v-if="r.is_borrow" @click="settle(r)" class="text-xs text-emerald-600 hover:underline">{{ r.settled ? 'ยกเลิกส่งใช้' : 'ส่งใช้เงินยืม' }}</button>
                                        <button @click="openEdit(r)" class="text-indigo-600 hover:underline">แก้ไข</button>
                                        <button @click="del(r)" class="text-rose-500 hover:underline">ลบ</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="rows.length" class="bg-gray-50 font-semibold text-gray-700">
                            <tr><td colspan="5" class="px-3 py-2 text-right">รวม</td><td class="px-3 py-2 text-right">{{ money(total) }}</td><td></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800">{{ editingId ? 'แก้ไข' : 'ลงทะเบียน' }}ขอเบิก/ขอยืม</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs text-gray-500">ประเภท</label>
                        <select v-model="form.kind" class="w-full rounded-lg border-gray-300 text-sm">
                            <option v-for="(lbl, k) in kinds" :key="k" :value="k">{{ lbl }}</option>
                        </select>
                    </div>
                    <div><label class="block text-xs text-gray-500">ที่เอกสาร</label><input v-model="form.doc_no" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div><label class="block text-xs text-gray-500">รายการ <span class="text-rose-400">*</span></label><input v-model="form.title" class="w-full rounded-lg border-gray-300 text-sm" /><div v-if="form.errors.title" class="text-xs text-rose-500">{{ form.errors.title }}</div></div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="block text-xs text-gray-500">โครงการ</label>
                            <select v-model="form.project_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— เลือก —</option><option v-for="o in options.project" :key="o.id" :value="o.id">{{ o.label }}</option></select></div>
                        <div><label class="block text-xs text-gray-500">กิจกรรม</label>
                            <select v-model="form.activity_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— เลือก —</option><option v-for="o in options.activity" :key="o.id" :value="o.id">{{ o.label }}</option></select></div>
                    </div>
                    <div><label class="block text-xs text-gray-500">ประเภทรายการจ่าย (งบรายจ่าย)</label>
                        <select v-model="form.expense_category_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— เลือก —</option><option v-for="o in options.expense_category" :key="o.id" :value="o.id">{{ o.label }}</option></select></div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="block text-xs text-gray-500">จำนวนเงิน <span class="text-rose-400">*</span></label><input v-model="form.amount" type="number" step="0.01" class="w-full rounded-lg border-gray-300 text-sm" /><div v-if="form.errors.amount" class="text-xs text-rose-500">{{ form.errors.amount }}</div></div>
                        <div><label class="block text-xs text-gray-500">ชื่อผู้ขอเบิก/ขอยืม</label><input v-model="form.borrower" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button @click="showModal = false" class="rounded-lg px-4 py-2 text-sm text-gray-500 hover:bg-gray-100">ยกเลิก</button>
                    <button @click="save" :disabled="form.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">บันทึก</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
