<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ projects: { type: Array, default: () => [] } });

const flash = computed(() => usePage().props.flash ?? {});
const baht = (n) => Number(n).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const barColor = (p) => (p >= 90 ? 'bg-green-500' : p >= 50 ? 'bg-indigo-500' : 'bg-amber-400');

const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    return q ? props.projects.filter((p) => (p.project_name ?? '').toLowerCase().includes(q) || String(p.fiscal_year ?? '').includes(q)) : props.projects;
});

// โครงการที่กำลังบันทึกเบิกจ่าย (modal)
const selectedId = ref(null);
const selected = computed(() => props.projects.find((p) => p.id === selectedId.value) ?? null);

const editingId = ref(null);
const form = useForm({ disburse_date: new Date().toISOString().slice(0, 10), amount: '', description: '', file: null });

const open = (p) => {
    selectedId.value = p.id;
    resetForm();
};
const resetForm = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.disburse_date = new Date().toISOString().slice(0, 10);
};
const editEntry = (e) => {
    editingId.value = e.id;
    form.disburse_date = e.date;
    form.amount = e.amount;
    form.description = e.description ?? '';
    form.file = null;
};
const submit = () => {
    const opts = { preserveScroll: true, forceFormData: true, onSuccess: () => resetForm() };
    if (editingId.value) {
        form.transform((d) => ({ ...d, _method: 'put' })).post(route('executive.disbursements.update', editingId.value), opts);
    } else {
        form.post(route('executive.disbursements.store', selectedId.value), opts);
    }
};
const removeEntry = (e) => {
    if (confirm('ลบรายการเบิกจ่ายนี้?')) {
        router.delete(route('executive.disbursements.destroy', e.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="บันทึกการเบิกจ่าย" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">บันทึกการเบิกจ่าย</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash.success }}</div>

                <input v-model="search" type="text" placeholder="ค้นหาชื่อโครงการ / ปีงบ" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-80" />

                <div class="space-y-3">
                    <div v-if="filtered.length === 0" class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                        <EmptyState title="ยังไม่มีโครงการ" description="เพิ่มที่เมนู ผลเบิกจ่ายงบโครงการ" />
                    </div>
                    <div v-for="p in filtered" :key="p.id" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-medium text-gray-900">{{ p.project_name }}</div>
                                <div class="text-xs text-gray-400">ปีงบ {{ p.fiscal_year }} · {{ p.entries.length }} รายการเบิก</div>
                            </div>
                            <button type="button" class="rounded-md bg-indigo-600 px-4 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-500" @click="open(p)">บันทึกเบิกจ่าย</button>
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-3 text-sm">
                            <div><span class="text-gray-400">จัดสรร</span><div class="font-semibold tabular-nums">{{ baht(p.allocated_amount) }}</div></div>
                            <div><span class="text-gray-400">เบิกจ่าย</span><div class="font-semibold tabular-nums text-indigo-600">{{ baht(p.disbursed_amount) }}</div></div>
                            <div><span class="text-gray-400">คงเหลือ</span><div class="font-semibold tabular-nums text-green-600">{{ baht(p.remaining) }}</div></div>
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full" :class="barColor(p.percent)" :style="{ width: Math.min(p.percent, 100) + '%' }" /></div>
                            <span class="w-10 text-right text-xs text-gray-500">{{ p.percent }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- modal: รายการเบิกจ่ายของโครงการ -->
        <Modal :show="selected !== null" max-width="2xl" @close="selectedId = null">
            <div v-if="selected" class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">{{ selected.project_name }}</h2>
                <p class="text-sm text-gray-400">ปีงบ {{ selected.fiscal_year }} · เบิกแล้ว {{ baht(selected.disbursed_amount) }} / จัดสรร {{ baht(selected.allocated_amount) }} (คงเหลือ {{ baht(selected.remaining) }})</p>

                <!-- รายการที่บันทึกไว้ -->
                <div class="mt-4 max-h-64 overflow-y-auto rounded-lg ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-3 py-2">วันที่</th>
                                <th class="px-3 py-2">รายการ</th>
                                <th class="px-3 py-2 text-right">จำนวนเงิน</th>
                                <th class="px-3 py-2 text-center">ไฟล์</th>
                                <th class="px-3 py-2 text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="selected.entries.length === 0"><td colspan="5" class="px-3 py-6 text-center text-gray-400">ยังไม่มีรายการเบิกจ่าย</td></tr>
                            <tr v-for="e in selected.entries" :key="e.id" class="text-gray-700">
                                <td class="px-3 py-2 text-gray-500">{{ e.date_thai }}</td>
                                <td class="px-3 py-2">{{ e.description ?? '—' }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-indigo-600">{{ baht(e.amount) }}</td>
                                <td class="px-3 py-2 text-center"><a v-if="e.file" :href="e.file" target="_blank" class="text-indigo-600 hover:underline">เปิด</a><span v-else class="text-gray-300">—</span></td>
                                <td class="px-3 py-2 text-center">
                                    <button class="text-indigo-600 hover:underline" @click="editEntry(e)">แก้</button>
                                    <button class="ml-2 text-rose-600 hover:underline" @click="removeEntry(e)">ลบ</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ฟอร์มเพิ่ม/แก้รายการ -->
                <form class="mt-4 space-y-3 rounded-lg bg-gray-50 p-4" @submit.prevent="submit">
                    <div class="text-sm font-semibold text-gray-700">{{ editingId ? 'แก้ไขรายการเบิกจ่าย' : 'เพิ่มรายการเบิกจ่าย' }}</div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs text-gray-500">วันที่เบิกจ่าย</label>
                            <input v-model="form.disburse_date" type="date" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <p v-if="form.errors.disburse_date" class="mt-1 text-xs text-rose-500">{{ form.errors.disburse_date }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-gray-500">จำนวนเงิน (บาท)</label>
                            <input v-model="form.amount" type="number" step="0.01" min="0.01" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <p v-if="form.errors.amount" class="mt-1 text-xs text-rose-500">{{ form.errors.amount }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-gray-500">รายการ/รายละเอียด</label>
                        <input v-model="form.description" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-gray-500">แนบไฟล์ (ถ้ามี)</label>
                        <input type="file" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100" @input="form.file = $event.target.files[0]" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <SecondaryButton v-if="editingId" type="button" @click="resetForm">ยกเลิกแก้ไข</SecondaryButton>
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60">{{ editingId ? 'บันทึกการแก้ไข' : 'เพิ่มรายการ' }}</button>
                    </div>
                </form>

                <div class="mt-4 flex justify-end">
                    <SecondaryButton @click="selectedId = null">ปิด</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
