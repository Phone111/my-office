<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    year: Number,
    years: { type: Array, default: () => [] },
    currentYear: Number,
    masters: { type: Object, default: () => ({}) },
    officers: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
    meta: { type: Object, required: true },
});

const flash = computed(() => usePage().props.flash?.success);

// ---------- แท็บ ---------- (แทรก "ประเภท(หลัก)ของเงิน" อ่านอย่างเดียว ก่อนประเภทย่อย)
const tabs = [
    { key: 'years', label: 'ปีงบประมาณ' },
    { key: 'officers', label: 'เจ้าหน้าที่การเงิน' },
    ...Object.entries(props.meta.types).flatMap(([key, label]) =>
        key === 'money_type'
            ? [{ key: 'money_main', label: 'ประเภท(หลัก)ของเงิน' }, { key, label }]
            : [{ key, label }],
    ),
];
const tab = ref('years');
const isMaster = (k) => k in props.meta.types;
const isYearBound = (k) => props.meta.yearBound.includes(k);

// ---------- เลือกปีงบประมาณที่แสดง ----------
const viewYear = ref(props.year);
function changeYear() {
    router.get(route('finance.settings.index'), { year: viewYear.value }, { preserveState: false, preserveScroll: true });
}

// ---------- ปีงบประมาณ ----------
const yearForm = useForm({ year: props.currentYear, is_current: false });
function addYear() {
    yearForm.post(route('finance.settings.years.add'), { preserveScroll: true, onSuccess: () => yearForm.reset() });
}
const setCurrent = (y) => router.post(route('finance.settings.years.current', y.id), {}, { preserveScroll: true });
const delYear = (y) => confirm(`ลบปีงบประมาณ ${y.year}?`) && router.delete(route('finance.settings.years.delete', y.id), { preserveScroll: true });

// ---------- เจ้าหน้าที่การเงิน ----------
const rightKeys = Object.keys(props.meta.rights);
const officerModal = ref(false);
const officerForm = useForm({ user_id: '', ...Object.fromEntries(rightKeys.map((k) => [k, false])) });
function openOfficer(o = null) {
    if (o) {
        officerForm.user_id = o.user_id;
        rightKeys.forEach((k) => (officerForm[k] = !!o[k]));
    } else {
        officerForm.reset();
    }
    officerModal.value = true;
}
function saveOfficer() {
    officerForm.post(route('finance.settings.officers.save'), { preserveScroll: true, onSuccess: () => (officerModal.value = false) });
}
const delOfficer = (o) => confirm(`ลบสิทธิ์เจ้าหน้าที่ของ ${o.name}?`) && router.delete(route('finance.settings.officers.delete', o.id), { preserveScroll: true });

// ---------- master data ----------
const masterModal = ref(false);
const editingId = ref(null);
const masterForm = useForm({ type: '', code: '', name: '', main_type: '', fiscal_year: props.year, sort_order: 0, is_active: true });
function openMaster(type, row = null) {
    masterForm.clearErrors();
    masterForm.type = type;
    masterForm.fiscal_year = props.year;
    if (row) {
        editingId.value = row.id;
        masterForm.code = row.code ?? '';
        masterForm.name = row.name;
        masterForm.main_type = row.main_type ?? '';
        masterForm.is_active = row.is_active;
    } else {
        editingId.value = null;
        masterForm.code = '';
        masterForm.name = '';
        masterForm.main_type = '';
        masterForm.is_active = true;
    }
    masterModal.value = true;
}
function saveMaster() {
    const opts = { preserveScroll: true, onSuccess: () => (masterModal.value = false) };
    if (editingId.value) {
        masterForm.put(route('finance.settings.masters.update', editingId.value), opts);
    } else {
        masterForm.post(route('finance.settings.masters.store'), opts);
    }
}
const delMaster = (m) => confirm(`ลบ "${m.name}"?`) && router.delete(route('finance.settings.masters.destroy', m.id), { preserveScroll: true });

const rowsFor = (type) => props.masters[type] ?? [];
const mainOptions = (type) => (type === 'expense_category' ? props.meta.expenseMain : type === 'money_type' ? props.meta.moneyMain : null);
const mainLabel = (type, code) => {
    const opts = mainOptions(type);
    return opts ? (opts[code] ?? code) : null;
};
</script>

<template>
    <Head title="ตั้งค่าระบบการเงิน" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">ตั้งค่าระบบการเงินและบัญชี</h2>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-gray-500">ปีงบประมาณ</span>
                    <select v-model="viewYear" @change="changeYear" class="rounded-lg border-gray-300 text-sm">
                        <option v-for="y in years" :key="y.id" :value="y.year">{{ y.year }}<span v-if="y.is_current"> (ปัจจุบัน)</span></option>
                        <option v-if="!years.some((y) => y.year === year)" :value="year">{{ year }}</option>
                    </select>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="flex flex-col gap-6 lg:flex-row">
                    <!-- แท็บซ้าย -->
                    <nav class="flex shrink-0 gap-1 overflow-x-auto lg:w-56 lg:flex-col">
                        <button
                            v-for="t in tabs"
                            :key="t.key"
                            @click="tab = t.key"
                            :class="['whitespace-nowrap rounded-lg px-3 py-2 text-left text-sm font-medium transition', tab === t.key ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100']"
                        >
                            {{ t.label }}
                        </button>
                    </nav>

                    <!-- เนื้อหา -->
                    <div class="min-w-0 flex-1 space-y-4">
                        <!-- ปีงบประมาณ -->
                        <section v-show="tab === 'years'" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                            <h3 class="mb-3 text-sm font-semibold text-gray-700">ปีงบประมาณ</h3>
                            <form @submit.prevent="addYear" class="mb-4 flex flex-wrap items-end gap-3">
                                <div>
                                    <label class="block text-xs text-gray-500">ปี (พ.ศ.)</label>
                                    <input v-model.number="yearForm.year" type="number" class="w-32 rounded-lg border-gray-300 text-sm" />
                                </div>
                                <label class="flex items-center gap-2 text-sm text-gray-600"><input v-model="yearForm.is_current" type="checkbox" class="rounded" /> ปีทำงานปัจจุบัน</label>
                                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">เพิ่มปี</button>
                            </form>
                            <div v-if="yearForm.errors.year" class="mb-2 text-xs text-rose-500">{{ yearForm.errors.year }}</div>
                            <table class="min-w-full divide-y divide-gray-100 text-sm">
                                <thead class="text-left text-xs uppercase text-gray-400"><tr><th class="py-2">ปีงบประมาณ</th><th>สถานะ</th><th class="text-right">จัดการ</th></tr></thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr v-for="y in years" :key="y.id">
                                        <td class="py-2 font-medium text-gray-800">{{ y.year }}</td>
                                        <td><span v-if="y.is_current" class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">ปัจจุบัน</span></td>
                                        <td class="space-x-3 text-right">
                                            <button v-if="!y.is_current" @click="setCurrent(y)" class="text-indigo-600 hover:underline">ตั้งเป็นปัจจุบัน</button>
                                            <button @click="delYear(y)" class="text-rose-500 hover:underline">ลบ</button>
                                        </td>
                                    </tr>
                                    <tr v-if="years.length === 0"><td colspan="3" class="py-6 text-center text-gray-400">ยังไม่มีปีงบประมาณ — ระบบใช้ปี {{ currentYear }} เป็นค่าเริ่มต้น</td></tr>
                                </tbody>
                            </table>
                        </section>

                        <!-- เจ้าหน้าที่การเงิน -->
                        <section v-show="tab === 'officers'" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-700">เจ้าหน้าที่การเงินและสิทธิ์</h3>
                                <button @click="openOfficer()" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700">+ เพิ่มเจ้าหน้าที่</button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-100 text-sm">
                                    <thead class="text-left text-xs text-gray-400">
                                        <tr><th class="py-2 pr-3">ชื่อ</th><th v-for="(label, k) in meta.rights" :key="k" class="px-1 text-center" :title="label">{{ label.split(' ')[0] }}</th><th class="text-right">จัดการ</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-for="o in officers" :key="o.id">
                                            <td class="py-2 pr-3 font-medium text-gray-800">{{ o.name }}</td>
                                            <td v-for="k in rightKeys" :key="k" class="px-1 text-center">
                                                <span v-if="o[k]" class="text-emerald-500">✓</span><span v-else class="text-gray-200">·</span>
                                            </td>
                                            <td class="space-x-3 whitespace-nowrap text-right">
                                                <button @click="openOfficer(o)" class="text-indigo-600 hover:underline">แก้ไข</button>
                                                <button @click="delOfficer(o)" class="text-rose-500 hover:underline">ลบ</button>
                                            </td>
                                        </tr>
                                        <tr v-if="officers.length === 0"><td :colspan="rightKeys.length + 2" class="py-6 text-center text-gray-400">ยังไม่มีเจ้าหน้าที่การเงิน</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <!-- master data -->
                        <section v-for="(label, type) in meta.types" :key="type" v-show="tab === type" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                            <div class="mb-3 flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-700">{{ label }}</h3>
                                    <p v-if="isYearBound(type)" class="text-xs text-gray-400">ปีงบประมาณ {{ year }}</p>
                                    <p v-else class="text-xs text-gray-400">ใช้ได้ทุกปีงบประมาณ</p>
                                </div>
                                <button @click="openMaster(type)" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700">+ เพิ่ม</button>
                            </div>
                            <table class="min-w-full divide-y divide-gray-100 text-sm">
                                <thead class="text-left text-xs text-gray-400">
                                    <tr>
                                        <th class="w-28 py-2">รหัส</th>
                                        <th>ชื่อ</th>
                                        <th v-if="mainOptions(type)" class="w-40">กลุ่มหลัก</th>
                                        <th class="w-28 text-right">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr v-for="m in rowsFor(type)" :key="m.id" :class="{ 'opacity-40': !m.is_active }">
                                        <td class="py-2 text-gray-500">{{ m.code || '—' }}</td>
                                        <td class="font-medium text-gray-800">{{ m.name }}</td>
                                        <td v-if="mainOptions(type)" class="text-gray-500">{{ mainLabel(type, m.main_type) || '—' }}</td>
                                        <td class="space-x-3 whitespace-nowrap text-right">
                                            <button @click="openMaster(type, m)" class="text-indigo-600 hover:underline">แก้ไข</button>
                                            <button @click="delMaster(m)" class="text-rose-500 hover:underline">ลบ</button>
                                        </td>
                                    </tr>
                                    <tr v-if="rowsFor(type).length === 0"><td :colspan="mainOptions(type) ? 4 : 3" class="py-6 text-center text-gray-400">ยังไม่มีข้อมูล</td></tr>
                                </tbody>
                            </table>
                        </section>

                        <!-- ประเภท(หลัก)ของเงิน — ระบบกำหนด แก้ไข/เพิ่มไม่ได้ (AMSS 1.8) -->
                        <section v-show="tab === 'money_main'" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                            <h3 class="text-sm font-semibold text-gray-700">ประเภท(หลัก)ของเงิน</h3>
                            <p class="mb-3 text-xs text-gray-400">ระบบกำหนดไว้ 3 ประเภท — แก้ไข/เพิ่ม/ลบไม่ได้ ใช้เป็นกลุ่มหลักของ "ประเภท(ย่อย)ของเงิน"</p>
                            <table class="min-w-full divide-y divide-gray-100 text-sm">
                                <thead class="text-left text-xs text-gray-400"><tr><th class="w-20 py-2">รหัส</th><th>ประเภท(หลัก)</th></tr></thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr v-for="(label, code) in meta.moneyMain" :key="code">
                                        <td class="py-2 text-gray-500">{{ code }}</td>
                                        <td class="font-medium text-gray-800">{{ label }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </section>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: เจ้าหน้าที่การเงิน -->
        <Modal :show="officerModal" @close="officerModal = false">
            <div class="p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800">สิทธิ์เจ้าหน้าที่การเงิน</h3>
                <label class="block text-sm text-gray-600">บุคลากร</label>
                <select v-model="officerForm.user_id" class="mb-1 w-full rounded-lg border-gray-300 text-sm">
                    <option value="">— เลือกบุคลากร —</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
                <div v-if="officerForm.errors.user_id" class="mb-2 text-xs text-rose-500">{{ officerForm.errors.user_id }}</div>
                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <label v-for="(lbl, k) in meta.rights" :key="k" class="flex items-center gap-2 rounded-lg border border-gray-100 px-3 py-2 text-sm text-gray-700">
                        <input v-model="officerForm[k]" type="checkbox" class="rounded" /> {{ lbl }}
                    </label>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button @click="officerModal = false" class="rounded-lg px-4 py-2 text-sm text-gray-500 hover:bg-gray-100">ยกเลิก</button>
                    <button @click="saveOfficer" :disabled="officerForm.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">บันทึก</button>
                </div>
            </div>
        </Modal>

        <!-- Modal: master -->
        <Modal :show="masterModal" @close="masterModal = false">
            <div class="p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800">{{ editingId ? 'แก้ไข' : 'เพิ่ม' }}{{ meta.types[masterForm.type] }}</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm text-gray-600">รหัส <span class="text-gray-400">(ถ้ามี)</span></label>
                        <input v-model="masterForm.code" type="text" class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600">ชื่อ</label>
                        <input v-model="masterForm.name" type="text" class="w-full rounded-lg border-gray-300 text-sm" />
                        <div v-if="masterForm.errors.name" class="text-xs text-rose-500">{{ masterForm.errors.name }}</div>
                    </div>
                    <div v-if="mainOptions(masterForm.type)">
                        <label class="block text-sm text-gray-600">กลุ่มหลัก</label>
                        <select v-model="masterForm.main_type" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">— เลือก —</option>
                            <option v-for="(lbl, code) in mainOptions(masterForm.type)" :key="code" :value="code">{{ code }} {{ lbl }}</option>
                        </select>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-600"><input v-model="masterForm.is_active" type="checkbox" class="rounded" /> เปิดใช้งาน</label>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button @click="masterModal = false" class="rounded-lg px-4 py-2 text-sm text-gray-500 hover:bg-gray-100">ยกเลิก</button>
                    <button @click="saveMaster" :disabled="masterForm.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">บันทึก</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
