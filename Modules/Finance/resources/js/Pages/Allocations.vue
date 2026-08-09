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
    nextVoucher: Number,
    options: { type: Object, required: true },
});

const flash = computed(() => usePage().props.flash?.success);
const money = (n) => Number(n || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const viewYear = ref(props.year);
const changeYear = () => router.get(route('finance.allocations.index'), { year: viewYear.value }, { preserveState: false });

const showModal = ref(false);
const editingId = ref(null);
const blank = {
    doc_no: '', doc_date: '', allocation_ref: '', plan_id: '', project_id: '', activity_id: '',
    activity_extra: '', fund_source_id: '', account_code: '', expense_category_id: '',
    title: '', detail: '', amount: '', received_at: '', file: null,
};
const form = useForm({ ...blank });

function openCreate() {
    editingId.value = null;
    form.clearErrors();
    form.reset();
    form.received_at = new Date().toISOString().slice(0, 10);
    showModal.value = true;
}
function openEdit(r) {
    editingId.value = r.id;
    form.clearErrors();
    Object.keys(blank).forEach((k) => (form[k] = r[k] ?? (k === 'file' ? null : '')));
    form.file = null;
    showModal.value = true;
}
function save() {
    const opts = { preserveScroll: true, forceFormData: true, onSuccess: () => (showModal.value = false) };
    if (editingId.value) {
        form.transform((d) => ({ ...d, _method: 'put' })).post(route('finance.allocations.update', editingId.value), opts);
    } else {
        form.post(route('finance.allocations.store'), opts);
    }
}
const del = (r) => confirm(`ลบใบงวดที่ ${r.voucher_no} (${r.title})?`) && router.delete(route('finance.allocations.destroy', r.id), { preserveScroll: true });
</script>

<template>
    <Head title="ทะเบียนเงินงวด" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">ทะเบียนเงินงวด (จัดสรรงบประมาณ)</h2>                </div>
                <div class="flex items-center gap-2 text-sm">
                    <select v-model="viewYear" @change="changeYear" class="rounded-lg border-gray-300 text-sm">
                        <option v-for="y in years" :key="y.id" :value="y.year">ปี {{ y.year }}<span v-if="y.is_current"> (ปัจจุบัน)</span></option>
                        <option v-if="!years.some((y) => y.year === year)" :value="year">ปี {{ year }}</option>
                    </select>
                    <button @click="openCreate" class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">+ เพิ่มใบงวด</button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="flex flex-wrap gap-4">
                    <div class="rounded-2xl bg-white px-5 py-3 text-sm shadow-sm ring-1 ring-gray-100">
                        <span class="text-gray-400">รวมเงินจัดสรรปี {{ year }}</span>
                        <div class="text-lg font-semibold text-indigo-700">{{ money(total) }} บาท</div>
                    </div>
                    <div class="rounded-2xl bg-white px-5 py-3 text-sm shadow-sm ring-1 ring-gray-100">
                        <span class="text-gray-400">ใบงวดถัดไป</span>
                        <div class="text-lg font-semibold text-gray-700">เลขที่ {{ nextVoucher }}</div>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-3 py-3 text-center">ใบงวด</th>
                                <th class="px-3 py-3">วันที่</th>
                                <th class="px-3 py-3">รายการ</th>
                                <th class="px-3 py-3">แผนงาน / งบรายจ่าย</th>
                                <th class="px-3 py-3 text-right">จำนวนเงิน</th>
                                <th class="px-3 py-3 text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="rows.length === 0"><td colspan="6" class="px-6 py-12 text-center text-gray-400">ยังไม่มีใบงวดในปีนี้</td></tr>
                            <tr v-for="r in rows" :key="r.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-3 py-3 text-center font-semibold text-indigo-700">{{ r.voucher_no }}</td>
                                <td class="px-3 py-3 text-gray-500">{{ r.doc_date_thai ?? '—' }}<div class="text-xs text-gray-400">{{ r.doc_no }}</div></td>
                                <td class="px-3 py-3"><div class="font-medium text-gray-900">{{ r.title }}</div><div v-if="r.allocation_ref" class="text-xs text-gray-400">อ้าง: {{ r.allocation_ref }}</div></td>
                                <td class="px-3 py-3 text-gray-500">{{ r.plan ?? '—' }}<div class="text-xs text-gray-400">{{ r.expense_category ?? '' }}</div></td>
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
                            <tr><td colspan="4" class="px-3 py-2 text-right">รวม</td><td class="px-3 py-2 text-right">{{ money(total) }}</td><td></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="showModal" max-width="2xl" @close="showModal = false">
            <div class="p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800">{{ editingId ? 'แก้ไขใบงวด' : 'เพิ่มใบงวด (จัดสรรงบประมาณ)' }}</h3>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div><label class="block text-xs text-gray-500">หนังสือเลขที่</label><input v-model="form.doc_no" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div><label class="block text-xs text-gray-500">ลงวันที่</label><input v-model="form.doc_date" type="date" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div class="sm:col-span-2"><label class="block text-xs text-gray-500">อ้างถึงหนังสือจัดสรร</label><input v-model="form.allocation_ref" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div><label class="block text-xs text-gray-500">แผนงาน</label>
                        <select v-model="form.plan_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— เลือก —</option><option v-for="o in options.plan" :key="o.id" :value="o.id">{{ o.label }}</option></select></div>
                    <div><label class="block text-xs text-gray-500">ผลผลิต/โครงการ</label>
                        <select v-model="form.project_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— เลือก —</option><option v-for="o in options.project" :key="o.id" :value="o.id">{{ o.label }}</option></select></div>
                    <div><label class="block text-xs text-gray-500">กิจกรรมหลัก</label>
                        <select v-model="form.activity_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— เลือก —</option><option v-for="o in options.activity" :key="o.id" :value="o.id">{{ o.label }}</option></select></div>
                    <div><label class="block text-xs text-gray-500">กิจกรรมหลักเพิ่มเติม</label><input v-model="form.activity_extra" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div><label class="block text-xs text-gray-500">แหล่งของเงิน</label>
                        <select v-model="form.fund_source_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— เลือก —</option><option v-for="o in options.fund_source" :key="o.id" :value="o.id">{{ o.label }}</option></select></div>
                    <div><label class="block text-xs text-gray-500">รหัสทางบัญชี</label><input v-model="form.account_code" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div class="sm:col-span-2"><label class="block text-xs text-gray-500">งบรายจ่าย</label>
                        <select v-model="form.expense_category_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— เลือก —</option><option v-for="o in options.expense_category" :key="o.id" :value="o.id">{{ o.label }}</option></select></div>
                    <div class="sm:col-span-2"><label class="block text-xs text-gray-500">รายการ <span class="text-rose-400">*</span></label><input v-model="form.title" class="w-full rounded-lg border-gray-300 text-sm" /><div v-if="form.errors.title" class="text-xs text-rose-500">{{ form.errors.title }}</div></div>
                    <div class="sm:col-span-2"><label class="block text-xs text-gray-500">รายละเอียด</label><textarea v-model="form.detail" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea></div>
                    <div><label class="block text-xs text-gray-500">จำนวนเงิน (บาท) <span class="text-rose-400">*</span></label><input v-model="form.amount" type="number" step="0.01" class="w-full rounded-lg border-gray-300 text-sm" /><div v-if="form.errors.amount" class="text-xs text-rose-500">{{ form.errors.amount }}</div></div>
                    <div><label class="block text-xs text-gray-500">วันที่บันทึก</label><input v-model="form.received_at" type="date" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div class="sm:col-span-2"><label class="block text-xs text-gray-500">ไฟล์แนบ (ถ้ามี)</label><input type="file" @input="form.file = $event.target.files[0]" class="w-full text-sm" /></div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button @click="showModal = false" class="rounded-lg px-4 py-2 text-sm text-gray-500 hover:bg-gray-100">ยกเลิก</button>
                    <button @click="save" :disabled="form.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">บันทึก</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
