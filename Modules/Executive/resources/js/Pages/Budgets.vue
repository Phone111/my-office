<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    budgets: { type: Array, default: () => [] },
    planYear: { type: Number, default: null },
    summary: { type: Object, default: () => ({ allocated: 0, disbursed: 0, remaining: 0 }) },
});

const flash = computed(() => usePage().props.flash ?? {});
const baht = (n) => Number(n).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// สิทธิ์จัดการ — ผอ. ดูได้อย่างเดียว (เพิ่ม/แก้/ลบ เฉพาะเลขาฯ/รองผอ./แอดมิน)
const userRoles = computed(() => usePage().props.auth?.roles ?? []);
const canManage = computed(() => ['secretary', 'deputy_director', 'budget_officer', 'admin'].some((r) => userRoles.value.includes(r)));

const overallPercent = computed(() =>
    props.summary.allocated > 0 ? Math.round((props.summary.disbursed / props.summary.allocated) * 100) : 0,
);

const showForm = ref(false);
const editingId = ref(null);
const form = useForm({
    project_name: '',
    fiscal_year: '',
    project_date: '',
    allocated_amount: 0,
    note: '',
    file: null,
});

const openCreate = () => {
    form.reset();
    form.clearErrors();
    if (props.planYear) form.fiscal_year = String(props.planYear); // เติมปีจัดทำแผนให้อัตโนมัติ
    editingId.value = null;
    showForm.value = true;
};

const openEdit = (b) => {
    form.clearErrors();
    editingId.value = b.id;
    form.project_name = b.project_name;
    form.fiscal_year = b.fiscal_year;
    form.project_date = b.project_date ?? '';
    form.allocated_amount = b.allocated_amount;
    form.note = b.note ?? '';
    form.file = null;
    showForm.value = true;
};

const submit = () => {
    const opts = { onSuccess: () => (showForm.value = false), preserveScroll: true, forceFormData: true };
    if (editingId.value) {
        form.transform((d) => ({ ...d, _method: 'put' })).post(route('executive.budgets.update', editingId.value), opts);
    } else {
        form.post(route('executive.budgets.store'), opts);
    }
};

const remove = (b) => {
    if (confirm(`ลบโครงการ "${b.project_name}" ?`)) {
        router.delete(route('executive.budgets.destroy', b.id), { preserveScroll: true });
    }
};

const barColor = (p) => (p >= 90 ? 'bg-green-500' : p >= 50 ? 'bg-indigo-500' : 'bg-amber-400');

// ค้นหา
const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.budgets;
    return props.budgets.filter(
        (b) =>
            (b.project_name ?? '').toLowerCase().includes(q) ||
            String(b.fiscal_year ?? '').includes(q),
    );
});

// ดูรายละเอียดโครงการ
const detail = ref(null);
</script>

<template>
    <Head title="ผลเบิกจ่ายงบโครงการ" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ผลเบิกจ่ายงบประมาณรายโครงการ</h2>
                <PrimaryButton v-if="canManage" @click="openCreate">+ เพิ่มโครงการ</PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ flash.success }}
                </div>

                <!-- การ์ดสรุปรวม -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        <p class="text-sm text-gray-500">งบจัดสรรรวม</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900">{{ baht(summary.allocated) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        <p class="text-sm text-gray-500">เบิกจ่ายแล้ว</p>
                        <p class="mt-2 text-2xl font-bold text-indigo-600">{{ baht(summary.disbursed) }}</p>
                        <p class="text-xs text-gray-400">{{ overallPercent }}% ของงบจัดสรร</p>
                    </div>
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        <p class="text-sm text-gray-500">คงเหลือ</p>
                        <p class="mt-2 text-2xl font-bold text-green-600">{{ baht(summary.remaining) }}</p>
                    </div>
                </div>

                <!-- ตารางรายโครงการ -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3">
                        <input v-model="search" type="text" placeholder="ค้นหาชื่อโครงการ / ปีงบ" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-80" />
                    </div>
                    <EmptyState v-if="filtered.length === 0" title="ไม่พบโครงการ" />
                    <table v-else class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3">โครงการ</th>
                                <th class="px-6 py-3">ปีงบ</th>
                                <th class="px-6 py-3">วัน-เดือน-ปี</th>
                                <th class="px-6 py-3 text-right">จัดสรร</th>
                                <th class="px-6 py-3 text-right">เบิกจ่าย</th>
                                <th class="px-6 py-3 text-right">คงเหลือ</th>
                                <th class="px-6 py-3 w-40">% เบิกจ่าย</th>
                                <th class="px-6 py-3 text-center">เอกสาร</th>
                                <th v-if="canManage" class="px-6 py-3 text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="b in filtered" :key="b.id" class="cursor-pointer text-sm text-gray-700 hover:bg-indigo-50/50" @click="detail = b">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ b.project_name }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ b.fiscal_year }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ b.project_date_thai ?? '—' }}</td>
                                <td class="px-6 py-4 text-right tabular-nums">{{ baht(b.allocated_amount) }}</td>
                                <td class="px-6 py-4 text-right tabular-nums text-indigo-600">{{ baht(b.disbursed_amount) }}</td>
                                <td class="px-6 py-4 text-right tabular-nums text-green-600">{{ baht(b.remaining) }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100">
                                            <div class="h-full rounded-full" :class="barColor(b.percent)" :style="{ width: Math.min(b.percent, 100) + '%' }" />
                                        </div>
                                        <span class="w-10 text-right text-xs text-gray-500">{{ b.percent }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a v-if="b.file" :href="b.file" target="_blank" class="text-indigo-600 hover:underline" @click.stop>เปิด</a>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td v-if="canManage" class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <button class="font-medium text-indigo-600 hover:text-indigo-800" @click.stop="openEdit(b)">แก้ไข</button>
                                        <button class="font-medium text-red-600 hover:text-red-800" @click.stop="remove(b)">ลบ</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="showForm" @close="showForm = false">
            <form class="space-y-5 p-6" @submit.prevent="submit">
                <h2 class="text-lg font-semibold text-gray-900">{{ editingId ? 'แก้ไขโครงการ' : 'เพิ่มโครงการ' }}</h2>
                <div>
                    <InputLabel for="proj" value="ชื่อโครงการ" />
                    <TextInput id="proj" v-model="form.project_name" type="text" class="mt-1 block w-full" autofocus />
                    <InputError :message="form.errors.project_name" class="mt-2" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <InputLabel for="fy" value="ปีงบประมาณ" />
                        <TextInput id="fy" v-model="form.fiscal_year" type="text" placeholder="2569" class="mt-1 block w-full" />
                        <InputError :message="form.errors.fiscal_year" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="pdate" value="วัน-เดือน-ปี" />
                        <TextInput id="pdate" v-model="form.project_date" type="date" class="mt-1 block w-full" />
                        <InputError :message="form.errors.project_date" class="mt-2" />
                    </div>
                </div>
                <div>
                    <InputLabel for="alloc" value="งบจัดสรร (บาท)" />
                    <TextInput id="alloc" v-model="form.allocated_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                    <InputError :message="form.errors.allocated_amount" class="mt-2" />
                    <p class="mt-1 text-xs text-gray-400">ยอดเบิกจ่ายบันทึกที่เมนู "บันทึกการเบิกจ่าย" (รวมอัตโนมัติ)</p>
                </div>
                <div>
                    <InputLabel for="note" value="หมายเหตุ" />
                    <textarea id="note" v-model="form.note" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
                <div>
                    <InputLabel for="bfile" value="แนบไฟล์เอกสาร" />
                    <input id="bfile" type="file" class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100" @input="form.file = $event.target.files[0]" />
                    <InputError :message="form.errors.file" class="mt-2" />
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton type="button" @click="showForm = false">ยกเลิก</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">บันทึก</PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- รายละเอียดโครงการ -->
        <Modal :show="detail !== null" @close="detail = null">
            <div v-if="detail" class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">{{ detail.project_name }}</h2>
                <p class="text-sm text-gray-400">ปีงบประมาณ {{ detail.fiscal_year }}</p>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">งบจัดสรร</dt><dd class="font-semibold tabular-nums text-gray-900">{{ baht(detail.allocated_amount) }}</dd></div>
                    <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">เบิกจ่ายแล้ว</dt><dd class="font-semibold tabular-nums text-indigo-600">{{ baht(detail.disbursed_amount) }}</dd></div>
                    <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">คงเหลือ</dt><dd class="font-semibold tabular-nums text-green-600">{{ baht(detail.remaining) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">% เบิกจ่าย</dt><dd class="font-semibold text-gray-900">{{ detail.percent }}%</dd></div>
                </dl>
                <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full rounded-full" :class="barColor(detail.percent)" :style="{ width: Math.min(detail.percent, 100) + '%' }" />
                </div>
                <p v-if="detail.note" class="mt-4 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-600">{{ detail.note }}</p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="detail = null">ปิด</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
