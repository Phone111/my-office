<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    grades: { type: Array, default: () => [] },
    genders: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    unitName: { type: String, default: null },
    units: { type: Array, default: () => [] },
    selectedUnit: { type: [Number, String], default: null },
    canPickSchool: { type: Boolean, default: false },
    stats: { type: Object, default: () => ({}) },
});
const flash = computed(() => usePage().props.flash?.success);

const unitParam = computed(() => (props.canPickSchool ? { unit: props.selectedUnit } : {}));

const show = ref(false);
const editingId = ref(null);
const form = useForm({ student_code: '', prename: '', name: '', surname: '', gender: '', birthdate: '', grade: 'ป.1', room: '', citizen_id: '', status: 'studying', note: '' });
const openCreate = () => { editingId.value = null; form.reset(); show.value = true; };
const openEdit = (r) => { editingId.value = r.id; Object.assign(form, { student_code: r.student_code ?? '', name: '', surname: '', grade: r.grade, room: r.room ?? '', status: r.status }); show.value = true; };
const submit = () => {
    const opt = { preserveScroll: true, onSuccess: () => { show.value = false; form.reset(); } };
    if (editingId.value) form.transform((d) => ({ ...d, ...unitParam.value })).put(route('students.update', editingId.value), opt);
    else form.transform((d) => ({ ...d, ...unitParam.value })).post(route('students.store'), opt);
};
const remove = (r) => { if (confirm(`ลบนักเรียน "${r.fullname}"?`)) router.delete(route('students.destroy', r.id), { preserveScroll: true, data: unitParam.value }); };

const filterBy = (key, val) => router.get(route('students.index'), { ...props.filters, ...unitParam.value, [key]: val || undefined }, { preserveState: true, replace: true });
const switchUnit = (e) => router.get(route('students.index'), { unit: e.target.value }, { preserveState: true, replace: true });

const importForm = useForm({ file: null });
const showImport = ref(false);
const doImport = () => importForm.transform((d) => ({ ...d, ...unitParam.value })).post(route('students.import'), { forceFormData: true, preserveScroll: true, onSuccess: () => { showImport.value = false; importForm.reset(); } });
</script>

<template>
    <Head title="ข้อมูลนักเรียน" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">ข้อมูลนักเรียน</h2>
                    <p class="text-xs text-gray-400">{{ unitName }}</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('students.report', unitParam)" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">สรุปรายชั้น</Link>
                    <button class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-emerald-600 ring-1 ring-emerald-200 hover:bg-emerald-50" @click="showImport = true">นำเข้า CSV</button>
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" @click="openCreate">เพิ่มนักเรียน</button>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div v-if="canPickSchool" class="flex items-center gap-2">
                    <label class="text-sm text-gray-500">โรงเรียน:</label>
                    <select :value="selectedUnit" class="rounded-md border-gray-300 text-sm" @change="switchUnit"><option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option></select>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-100"><p class="text-2xl font-bold text-gray-800">{{ stats.total }}</p><p class="text-xs text-gray-400">นักเรียน (กำลังเรียน)</p></div>
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-blue-100"><p class="text-2xl font-bold text-blue-600">{{ stats.male }}</p><p class="text-xs text-gray-400">ชาย</p></div>
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-pink-100"><p class="text-2xl font-bold text-pink-600">{{ stats.female }}</p><p class="text-xs text-gray-400">หญิง</p></div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <input :value="filters.q" type="text" placeholder="ค้นหาชื่อ/เลขประจำตัว" class="rounded-md border-gray-300 text-sm" @input="filterBy('q', $event.target.value)" />
                    <select :value="filters.grade" class="rounded-md border-gray-300 text-sm" @change="filterBy('grade', $event.target.value)"><option value="">ทุกชั้น</option><option v-for="g in grades" :key="g" :value="g">{{ g }}</option></select>
                    <select :value="filters.status" class="rounded-md border-gray-300 text-sm" @change="filterBy('status', $event.target.value)"><option value="">ทุกสถานะ</option><option v-for="s in statuses" :key="s.key" :value="s.key">{{ s.label }}</option></select>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">รายชื่อนักเรียน ({{ rows.length }})</div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50"><tr class="text-left text-xs font-semibold uppercase text-gray-500"><th class="px-4 py-3">เลขประจำตัว</th><th class="px-4 py-3">ชื่อ-สกุล</th><th class="px-4 py-3">เพศ</th><th class="px-4 py-3">ชั้น/ห้อง</th><th class="px-4 py-3">สถานะ</th><th class="px-4 py-3 text-center">จัดการ</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="rows.length === 0"><td colspan="6" class="px-6 py-8"><EmptyState title="ยังไม่มีข้อมูลนักเรียน" /></td></tr>
                            <tr v-for="r in rows" :key="r.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-500">{{ r.student_code ?? '—' }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ r.fullname }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ r.gender ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ r.grade }}{{ r.room ? '/'+r.room : '' }}</td>
                                <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="r.status === 'studying' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'">{{ r.status_label }}</span></td>
                                <td class="px-4 py-3 text-center"><button class="text-rose-600 hover:underline" @click="remove(r)">ลบ</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- เพิ่มนักเรียน -->
        <Modal :show="show" @close="show = false">
            <div class="space-y-3 p-6">
                <h3 class="text-lg font-semibold text-gray-800">เพิ่มนักเรียน</h3>
                <div class="grid grid-cols-2 gap-2">
                    <input v-model="form.student_code" type="text" placeholder="เลขประจำตัวนักเรียน" class="rounded-md border-gray-300 text-sm" />
                    <input v-model="form.citizen_id" type="text" placeholder="เลขบัตรประชาชน" class="rounded-md border-gray-300 text-sm" />
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <input v-model="form.prename" type="text" placeholder="คำนำหน้า" class="rounded-md border-gray-300 text-sm" />
                    <input v-model="form.name" type="text" placeholder="ชื่อ" class="rounded-md border-gray-300 text-sm" />
                    <input v-model="form.surname" type="text" placeholder="สกุล" class="rounded-md border-gray-300 text-sm" />
                </div>
                <p v-if="form.errors.name" class="text-xs text-rose-500">{{ form.errors.name }}</p>
                <div class="grid grid-cols-4 gap-2">
                    <select v-model="form.gender" class="rounded-md border-gray-300 text-sm"><option value="">เพศ</option><option v-for="g in genders" :key="g.key" :value="g.key">{{ g.label }}</option></select>
                    <select v-model="form.grade" class="rounded-md border-gray-300 text-sm"><option v-for="g in grades" :key="g" :value="g">{{ g }}</option></select>
                    <input v-model="form.room" type="text" placeholder="ห้อง" class="rounded-md border-gray-300 text-sm" />
                    <select v-model="form.status" class="rounded-md border-gray-300 text-sm"><option v-for="s in statuses" :key="s.key" :value="s.key">{{ s.label }}</option></select>
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="show = false">ยกเลิก</SecondaryButton>
                    <button :disabled="form.processing" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60" @click="submit">บันทึก</button>
                </div>
            </div>
        </Modal>

        <!-- นำเข้า CSV -->
        <Modal :show="showImport" @close="showImport = false">
            <div class="space-y-3 p-6">
                <h3 class="text-lg font-semibold text-gray-800">นำเข้านักเรียนจาก CSV</h3>
                <p class="text-sm text-gray-500">คอลัมน์: เลขประจำตัว, คำนำหน้า, ชื่อ, สกุล, เพศ(ชาย/หญิง), ชั้น, ห้อง, เลขบัตรประชาชน</p>
                <a :href="route('students.template')" class="inline-block text-sm font-medium text-indigo-600 hover:underline">↓ ดาวน์โหลดไฟล์ตัวอย่าง</a>
                <input type="file" accept=".csv,.txt" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700" @change="importForm.file = $event.target.files[0]" />
                <p v-if="importForm.errors.file" class="text-xs text-rose-500">{{ importForm.errors.file }}</p>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="showImport = false">ยกเลิก</SecondaryButton>
                    <button :disabled="importForm.processing || !importForm.file" class="rounded-md bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50" @click="doImport">นำเข้า</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
