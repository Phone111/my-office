<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    year: { type: Number, default: 0 },
    indicators: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    rounds: { type: Array, default: () => [] },
    people: { type: Array, default: () => [] },
});
const flash = computed(() => usePage().props.flash?.success);

const selectedYear = ref(props.year);
const reload = () => router.get(route('krs.index'), { year: selectedYear.value }, { preserveState: true, preserveScroll: true });

const grouped = computed(() => {
    const g = {};
    for (const c of props.categories) g[c.key] = { label: c.label, items: [] };
    for (const i of props.indicators) (g[i.category] ??= { label: i.category_label, items: [] }).items.push(i);
    return Object.values(g).filter((x) => x.items.length);
});

const roundMeta = (r) => {
    if (!r) return { label: '—', cls: 'text-gray-300' };
    if (r.status === 'received') return { label: 'รับแล้ว ', cls: 'text-emerald-600 font-semibold' };
    return { label: 'ส่งแล้ว', cls: 'text-amber-600 font-semibold' };
};

// ===== ฟอร์มตัวชี้วัด =====
const showForm = ref(false);
const editingId = ref(null);
const form = useForm({ year: props.year, category: 'krs', code: '', name: '', reporter_id: '', receiver_id: '', is_active: true });
const openCreate = () => {
    form.reset();
    form.year = selectedYear.value;
    form.clearErrors();
    editingId.value = null;
    showForm.value = true;
};
const openEdit = (i) => {
    form.clearErrors();
    editingId.value = i.id;
    form.year = props.year;
    form.category = i.category;
    form.code = i.code;
    form.name = i.name;
    form.reporter_id = i.reporter_id ?? '';
    form.receiver_id = i.receiver_id ?? '';
    form.is_active = i.is_active;
    showForm.value = true;
};
const submit = () => {
    const opts = { preserveScroll: true, onSuccess: () => (showForm.value = false) };
    if (editingId.value) form.put(route('krs.indicators.update', editingId.value), opts);
    else form.post(route('krs.indicators.store'), opts);
};
const remove = (i) => {
    if (confirm(`ลบตัวชี้วัด ${i.code} "${i.name}" ?`)) router.delete(route('krs.indicators.destroy', i.id), { preserveScroll: true });
};
const receive = (reportId) => router.post(route('krs.reports.receive', reportId), {}, { preserveScroll: true });
</script>

<template>
    <Head title="คำรับรองปฏิบัติราชการ (KRS/ARS)" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">คำรับรองปฏิบัติราชการ (KRS/ARS)</h2>
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-gray-500">ปี พ.ศ.</span>
                    <input v-model.number="selectedYear" type="number" class="w-24 rounded-md border-gray-300 text-sm shadow-sm" @change="reload" />
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" @click="openCreate">+ เพิ่มตัวชี้วัด</button>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-6xl space-y-5 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div v-if="indicators.length === 0" class="rounded-2xl bg-white p-12 text-center text-sm text-gray-400 shadow-sm ring-1 ring-gray-100">ยังไม่มีตัวชี้วัดในปีนี้ — กด “เพิ่มตัวชี้วัด”</div>

                <div v-for="g in grouped" :key="g.label" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 bg-indigo-50/60 px-6 py-2.5 text-sm font-bold text-indigo-700">{{ g.label }}</div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-3 py-2">ตัวชี้วัด</th>
                                <th class="px-3 py-2">ผู้รายงาน</th>
                                <th v-for="r in rounds" :key="r" class="px-3 py-2 text-center">รอบ {{ r }} ด.</th>
                                <th class="px-3 py-2 text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="i in g.items" :key="i.id" class="text-gray-700 hover:bg-gray-50" :class="{ 'opacity-50': !i.is_active }">
                                <td class="px-3 py-2"><span class="font-semibold text-gray-900">{{ i.code }}</span> {{ i.name }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ i.reporter ?? '—' }}</td>
                                <td v-for="r in rounds" :key="r" class="px-3 py-2 text-center text-xs">
                                    <template v-if="i.rounds[r]">
                                        <span :class="roundMeta(i.rounds[r]).cls">{{ roundMeta(i.rounds[r]).label }}</span>
                                        <div class="mt-0.5 flex items-center justify-center gap-1.5">
                                            <a v-if="i.rounds[r].url" :href="i.rounds[r].url" target="_blank" class="text-indigo-500 hover:underline">ไฟล์</a>
                                            <button v-if="i.rounds[r].status === 'submitted'" class="text-emerald-600 hover:underline" @click="receive(i.rounds[r].report_id)">รับ</button>
                                        </div>
                                    </template>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <button class="text-indigo-600 hover:text-indigo-800" @click="openEdit(i)">แก้</button>
                                    <button class="ml-2 text-rose-600 hover:text-rose-800" @click="remove(i)">ลบ</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="showForm" @close="showForm = false">
            <form class="space-y-4 p-6" @submit.prevent="submit">
                <h2 class="text-lg font-semibold text-gray-900">{{ editingId ? 'แก้ไขตัวชี้วัด' : 'เพิ่มตัวชี้วัด' }}</h2>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">หมวด</label>
                        <select v-model="form.category" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option v-for="c in categories" :key="c.key" :value="c.key">{{ c.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">ตัวชี้วัดที่</label>
                        <input v-model="form.code" type="text" placeholder="เช่น 1.1" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        <p v-if="form.errors.code" class="mt-1 text-xs text-rose-500">{{ form.errors.code }}</p>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">ชื่อตัวชี้วัด</label>
                    <textarea v-model="form.name" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-rose-500">{{ form.errors.name }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">ผู้รายงานตัวชี้วัด</label>
                        <select v-model="form.reporter_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="">— เลือก —</option>
                            <option v-for="p in people" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">จนท.รับข้อมูล</label>
                        <select v-model="form.receiver_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="">— เลือก —</option>
                            <option v-for="p in people" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600" /> เปิดใช้งาน
                </label>
                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton type="button" @click="showForm = false">ยกเลิก</SecondaryButton>
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">บันทึก</button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
