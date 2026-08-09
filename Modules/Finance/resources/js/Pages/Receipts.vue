<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    class: String,
    classLabel: String,
    year: Number,
    years: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    total: Number,
    natures: { type: Object, default: () => ({}) },
    moneyTypes: { type: Array, default: () => [] },
    usesMoneyType: Boolean,
});

const flash = computed(() => usePage().props.flash?.success);
const money = (n) => Number(n || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const viewYear = ref(props.year);
const changeYear = () => router.get(route('finance.receipts.index', props.class), { year: viewYear.value }, { preserveState: false });

const showModal = ref(false);
const editingId = ref(null);
const blank = { doc_no: '', money_type_id: '', title: '', nature: '', amount: '', receive_date: '', file: null };
const form = useForm({ ...blank });

function openCreate() {
    editingId.value = null;
    form.clearErrors();
    form.reset();
    form.receive_date = new Date().toISOString().slice(0, 10);
    showModal.value = true;
}
function openEdit(r) {
    editingId.value = r.id;
    form.clearErrors();
    form.doc_no = r.doc_no ?? '';
    form.money_type_id = r.money_type_id ?? '';
    form.title = r.title;
    form.nature = r.nature ?? '';
    form.amount = r.amount;
    form.receive_date = r.receive_date ?? '';
    form.file = null;
    showModal.value = true;
}
function save() {
    const opts = { preserveScroll: true, forceFormData: true, onSuccess: () => (showModal.value = false) };
    if (editingId.value) {
        form.transform((d) => ({ ...d, _method: 'put' })).post(route('finance.receipts.update', editingId.value), opts);
    } else {
        form.post(route('finance.receipts.store', props.class), opts);
    }
}
const del = (r) => confirm(`ลบรายการ "${r.title}"?`) && router.delete(route('finance.receipts.destroy', r.id), { preserveScroll: true });
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
                    <button @click="openCreate" class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">+ เพิ่มการรับ</button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="rounded-2xl bg-white px-5 py-3 text-sm shadow-sm ring-1 ring-gray-100 inline-block">
                    <span class="text-gray-400">รวมรับปี {{ year }}</span>
                    <div class="text-lg font-semibold text-emerald-700">{{ money(total) }} บาท</div>
                </div>

                <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-3 py-3">วันที่</th>
                                <th class="px-3 py-3">ที่เอกสาร</th>
                                <th class="px-3 py-3">รายการ</th>
                                <th v-if="usesMoneyType" class="px-3 py-3">ประเภทเงิน</th>
                                <th class="px-3 py-3">ลักษณะ</th>
                                <th class="px-3 py-3 text-right">จำนวนเงิน</th>
                                <th class="px-3 py-3 text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="rows.length === 0"><td :colspan="usesMoneyType ? 7 : 6" class="px-6 py-12 text-center text-gray-400">ยังไม่มีรายการรับในปีนี้</td></tr>
                            <tr v-for="r in rows" :key="r.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-3 py-3 text-gray-500">{{ r.receive_date_thai ?? '—' }}</td>
                                <td class="px-3 py-3 text-gray-500">{{ r.doc_no ?? '—' }}</td>
                                <td class="px-3 py-3 font-medium text-gray-900">{{ r.title }}</td>
                                <td v-if="usesMoneyType" class="px-3 py-3 text-gray-500">{{ r.money_type ?? '—' }}</td>
                                <td class="px-3 py-3 text-gray-500">{{ r.nature_label ?? '—' }}</td>
                                <td class="px-3 py-3 text-right font-medium text-gray-800">{{ money(r.amount) }}</td>
                                <td class="px-3 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a v-if="r.file" :href="r.file" target="_blank" class="text-gray-400 hover:text-indigo-600" title="ไฟล์แนบ">📎</a>
                                        <button @click="openEdit(r)" class="text-indigo-600 hover:underline">แก้ไข</button>
                                        <button @click="del(r)" class="text-rose-500 hover:underline">ลบ</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="rows.length" class="bg-gray-50 font-semibold text-gray-700">
                            <tr><td :colspan="usesMoneyType ? 5 : 4" class="px-3 py-2 text-right">รวม</td><td class="px-3 py-2 text-right">{{ money(total) }}</td><td></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800">{{ editingId ? 'แก้ไขรายการรับ' : 'เพิ่มการรับ' }} — {{ classLabel }}</h3>
                <div class="space-y-3">
                    <div><label class="block text-xs text-gray-500">ที่เอกสาร</label><input v-model="form.doc_no" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div v-if="usesMoneyType">
                        <label class="block text-xs text-gray-500">ประเภทของเงิน <span class="text-rose-400">*</span></label>
                        <select v-model="form.money_type_id" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">— เลือก —</option>
                            <option v-for="o in moneyTypes" :key="o.id" :value="o.id">{{ o.label }}</option>
                        </select>
                        <div v-if="form.errors.money_type_id" class="text-xs text-rose-500">{{ form.errors.money_type_id }}</div>
                        <p v-if="moneyTypes.length === 0" class="text-xs text-amber-500">ยังไม่ได้ตั้งค่า "ประเภทของเงิน" — เพิ่มที่เมนูตั้งค่าระบบการเงินก่อน</p>
                    </div>
                    <div><label class="block text-xs text-gray-500">รายการ <span class="text-rose-400">*</span></label><input v-model="form.title" class="w-full rounded-lg border-gray-300 text-sm" /><div v-if="form.errors.title" class="text-xs text-rose-500">{{ form.errors.title }}</div></div>
                    <div><label class="block text-xs text-gray-500">ลักษณะรายการ</label>
                        <select v-model="form.nature" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">— เลือก —</option>
                            <option v-for="(lbl, k) in natures" :key="k" :value="k">{{ lbl }}</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="block text-xs text-gray-500">จำนวนเงิน (บาท) <span class="text-rose-400">*</span></label><input v-model="form.amount" type="number" step="0.01" class="w-full rounded-lg border-gray-300 text-sm" /><div v-if="form.errors.amount" class="text-xs text-rose-500">{{ form.errors.amount }}</div></div>
                        <div><label class="block text-xs text-gray-500">วันที่รับ</label><input v-model="form.receive_date" type="date" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    </div>
                    <div><label class="block text-xs text-gray-500">ไฟล์แนบ (ถ้ามี)</label><input type="file" @input="form.file = $event.target.files[0]" class="w-full text-sm" /></div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button @click="showModal = false" class="rounded-lg px-4 py-2 text-sm text-gray-500 hover:bg-gray-100">ยกเลิก</button>
                    <button @click="save" :disabled="form.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">บันทึก</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
