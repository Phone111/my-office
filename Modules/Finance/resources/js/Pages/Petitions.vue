<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    type: String,
    typeLabel: String,
    year: Number,
    years: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    total: Number,
    totalNet: Number,
    options: { type: Object, required: true },
    allocations: { type: Array, default: () => [] },
    unlinked: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash?.success);
const money = (n) => Number(n || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const viewYear = ref(props.year);
const changeYear = () => router.get(route('finance.petitions.index', props.type), { year: viewYear.value }, { preserveState: false });

const showModal = ref(false);
const form = useForm({
    petition_no: '', doc_no: '', allocation_id: '', plan_id: '', project_id: '', activity_id: '',
    expense_category_id: '', title: '', amount: '', tax: '0', withdrawal_ids: [],
});
const net = computed(() => Math.max(0, (Number(form.amount) || 0) - (Number(form.tax) || 0)));

function openCreate() {
    form.clearErrors();
    form.reset();
    form.tax = '0';
    showModal.value = true;
}
function save() {
    form.post(route('finance.petitions.store', props.type), { preserveScroll: true, onSuccess: () => (showModal.value = false) });
}
const del = (r) => confirm(`ลบฎีกา ${r.petition_no}?`) && router.delete(route('finance.petitions.destroy', r.id), { preserveScroll: true });
</script>

<template>
    <Head :title="typeLabel" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ typeLabel }}</h2>                </div>
                <div class="flex items-center gap-2 text-sm">
                    <select v-model="viewYear" @change="changeYear" class="rounded-lg border-gray-300 text-sm">
                        <option v-for="y in years" :key="y.id" :value="y.year">ปี {{ y.year }}<span v-if="y.is_current"> (ปัจจุบัน)</span></option>
                        <option v-if="!years.some((y) => y.year === year)" :value="year">ปี {{ year }}</option>
                    </select>
                    <button @click="openCreate" class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">+ ลงทะเบียนฎีกา</button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>
                <div class="flex flex-wrap gap-4">
                    <div class="rounded-2xl bg-white px-5 py-3 text-sm shadow-sm ring-1 ring-gray-100"><span class="text-gray-400">รวมขอเบิกปี {{ year }}</span><div class="text-lg font-semibold text-indigo-700">{{ money(total) }}</div></div>
                    <div class="rounded-2xl bg-white px-5 py-3 text-sm shadow-sm ring-1 ring-gray-100"><span class="text-gray-400">รับจริง (สุทธิ)</span><div class="text-lg font-semibold text-emerald-700">{{ money(totalNet) }}</div></div>
                </div>

                <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-3 py-3">เลขที่ฎีกา</th>
                                <th class="px-3 py-3">รายการ</th>
                                <th class="px-3 py-3 text-center">ใบงวด</th>
                                <th class="px-3 py-3 text-center">รายการที่รวม</th>
                                <th class="px-3 py-3 text-right">ขอเบิก</th>
                                <th class="px-3 py-3 text-right">ภาษี</th>
                                <th class="px-3 py-3 text-right">รับจริง</th>
                                <th class="px-3 py-3 text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="rows.length === 0"><td colspan="8" class="px-6 py-12 text-center text-gray-400">ยังไม่มีฎีกา</td></tr>
                            <tr v-for="r in rows" :key="r.id" :class="['text-gray-700 hover:bg-gray-50', { 'opacity-50': r.cancelled }]">
                                <td class="px-3 py-3 font-semibold text-indigo-700">{{ r.petition_no }}<span v-if="r.cancelled" class="ml-1 text-xs text-rose-500">(ยกเลิก)</span></td>
                                <td class="px-3 py-3"><div class="font-medium text-gray-900">{{ r.title }}</div><div class="text-xs text-gray-400">{{ r.expense_category ?? '' }}</div></td>
                                <td class="px-3 py-3 text-center text-gray-500">{{ r.voucher_no ?? '—' }}</td>
                                <td class="px-3 py-3 text-center text-gray-500">{{ r.linked }}</td>
                                <td class="px-3 py-3 text-right text-gray-800">{{ money(r.amount) }}</td>
                                <td class="px-3 py-3 text-right text-gray-500">{{ money(r.tax) }}</td>
                                <td class="px-3 py-3 text-right font-medium text-emerald-700">{{ money(r.net) }}</td>
                                <td class="px-3 py-3 text-center"><button @click="del(r)" class="text-rose-500 hover:underline">ลบ</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="showModal" max-width="2xl" @close="showModal = false">
            <div class="p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800">ลงทะเบียน{{ typeLabel }}</h3>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div><label class="block text-xs text-gray-500">เลขที่ฎีกา <span class="text-gray-400">(ว่าง = รันอัตโนมัติ)</span></label><input v-model="form.petition_no" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div><label class="block text-xs text-gray-500">ที่เอกสาร</label><input v-model="form.doc_no" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                    <div class="sm:col-span-2"><label class="block text-xs text-gray-500">เลขที่ใบงวด</label>
                        <select v-model="form.allocation_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— เลือก —</option><option v-for="o in allocations" :key="o.id" :value="o.id">{{ o.label }}</option></select></div>
                    <div><label class="block text-xs text-gray-500">แผนงาน</label>
                        <select v-model="form.plan_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— เลือก —</option><option v-for="o in options.plan" :key="o.id" :value="o.id">{{ o.label }}</option></select></div>
                    <div><label class="block text-xs text-gray-500">ผลผลิต/โครงการ</label>
                        <select v-model="form.project_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— เลือก —</option><option v-for="o in options.project" :key="o.id" :value="o.id">{{ o.label }}</option></select></div>
                    <div><label class="block text-xs text-gray-500">กิจกรรมหลัก</label>
                        <select v-model="form.activity_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— เลือก —</option><option v-for="o in options.activity" :key="o.id" :value="o.id">{{ o.label }}</option></select></div>
                    <div><label class="block text-xs text-gray-500">รายการจ่าย (งบรายจ่าย)</label>
                        <select v-model="form.expense_category_id" class="w-full rounded-lg border-gray-300 text-sm"><option value="">— เลือก —</option><option v-for="o in options.expense_category" :key="o.id" :value="o.id">{{ o.label }}</option></select></div>
                    <div class="sm:col-span-2"><label class="block text-xs text-gray-500">รายการ <span class="text-rose-400">*</span></label><input v-model="form.title" class="w-full rounded-lg border-gray-300 text-sm" /><div v-if="form.errors.title" class="text-xs text-rose-500">{{ form.errors.title }}</div></div>
                    <div><label class="block text-xs text-gray-500">จำนวนเงินขอเบิก <span class="text-rose-400">*</span></label><input v-model="form.amount" type="number" step="0.01" class="w-full rounded-lg border-gray-300 text-sm" /><div v-if="form.errors.amount" class="text-xs text-rose-500">{{ form.errors.amount }}</div></div>
                    <div><label class="block text-xs text-gray-500">ภาษี</label><input v-model="form.tax" type="number" step="0.01" class="w-full rounded-lg border-gray-300 text-sm" /></div>
                </div>
                <div class="mt-2 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">รับจริง (สุทธิ) = {{ money(net) }} บาท</div>

                <div v-if="unlinked.length" class="mt-4">
                    <p class="mb-1 text-xs font-semibold text-gray-600">รวมรายการขอเบิก/ขอยืมเข้าฎีกานี้ (เลือกได้หลายรายการ)</p>
                    <div class="max-h-40 space-y-1 overflow-y-auto rounded-lg border border-gray-100 p-2">
                        <label v-for="w in unlinked" :key="w.id" class="flex items-center justify-between gap-2 rounded px-2 py-1 text-sm hover:bg-gray-50">
                            <span class="flex items-center gap-2 text-gray-700"><input type="checkbox" :value="w.id" v-model="form.withdrawal_ids" class="rounded" /> <span class="text-xs text-gray-400">{{ w.kind_label }}</span> {{ w.label }}</span>
                            <span class="text-gray-500">{{ money(w.amount) }}</span>
                        </label>
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
