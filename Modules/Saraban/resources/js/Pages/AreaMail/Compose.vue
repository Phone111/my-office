<script setup>
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    units: { type: Array, default: () => [] },
    myUnit: { type: String, default: null },
    bookPrefix: { type: String, default: null },
    priorities: { type: Array, default: () => [] },
    prefill: { type: Object, default: null },
    schoolGroups: { type: Array, default: () => [] },
});

const area = computed(() => props.units.filter((u) => u.type === 'area'));
const schools = computed(() => props.units.filter((u) => u.type === 'school'));

const today = new Date().toISOString().slice(0, 10);
const pf = props.prefill;
const form = useForm({
    to_unit_ids: [],
    number: pf?.number ?? '',
    auto_number: !pf,
    doc_date: pf?.doc_date || today,
    subject: pf?.subject ?? '',
    detail: '',
    reference: '',
    priority: 'normal',
    confidential: false,
    attachments: [],
    source_outgoing_id: pf?.source_outgoing_id ?? null,
});

// ค้นหา/กรองโรงเรียน (มีหลายร้อยแห่ง)
const search = ref('');
const filteredSchools = computed(() => {
    const q = search.value.trim().toLowerCase();
    return q ? schools.value.filter((s) => s.name.toLowerCase().includes(q)) : schools.value;
});

const allSchoolsSelected = computed(() => schools.value.length > 0 && schools.value.every((s) => form.to_unit_ids.includes(s.id)));
const toggleAllSchools = () => {
    const ids = schools.value.map((s) => s.id);
    if (allSchoolsSelected.value) form.to_unit_ids = form.to_unit_ids.filter((i) => !ids.includes(i));
    else form.to_unit_ids = [...new Set([...form.to_unit_ids, ...ids])];
};
// เลือกเฉพาะที่ค้นเจอ / เฉพาะเขต / ล้าง
const selectFiltered = () => (form.to_unit_ids = [...new Set([...form.to_unit_ids, ...filteredSchools.value.map((s) => s.id)])]);
const selectAreas = () => (form.to_unit_ids = [...new Set([...form.to_unit_ids, ...area.value.map((a) => a.id)])]);
const clearAll = () => (form.to_unit_ids = []);
// เลือกทั้งกลุ่มโรงเรียนทีเดียว (AMSS)
const addGroup = (g) => (form.to_unit_ids = [...new Set([...form.to_unit_ids, ...g.unit_ids])]);

const onFiles = (e) => (form.attachments = Array.from(e.target.files));
const submit = () => form.post(route('saraban.area-mail.store'), { forceFormData: true });
</script>

<template>
    <Head title="ส่งหนังสือราชการ (ระหว่างหน่วยงาน)" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ส่งหนังสือราชการ (ระหว่างหน่วยงาน)</h2>
                <p class="text-xs text-gray-400">จาก: {{ myUnit ?? '—' }}</p>
            </div>
        </template>

        <div class="py-10">
            <form class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8" @submit.prevent="submit">
                <!-- อ้างอิงจากทะเบียนหนังสือส่ง (ปุ่ม "ส่ง ร.ร.") -->
                <div v-if="pf" class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">
                    <p class="font-semibold">อ้างอิงจากทะเบียนหนังสือส่ง เลขที่ {{ pf.number }}</p>
                    <p v-if="pf.files && pf.files.length" class="mt-0.5 text-xs text-amber-700">ไฟล์ที่จะส่งไปด้วย: {{ pf.files.join(', ') }}</p>
                    <p v-else class="mt-0.5 text-xs text-amber-600">ยังไม่มีไฟล์แนบในทะเบียนส่ง — แนบเพิ่มได้ด้านล่าง</p>
                </div>

                <!-- ผู้รับ -->
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-2 flex items-center justify-between">
                        <label class="text-sm font-semibold text-gray-700">ส่งถึง (เลือกได้หลายหน่วยงาน)</label>
                        <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700">เลือกแล้ว {{ form.to_unit_ids.length }}</span>
                    </div>

                    <!-- ปุ่มเลือกเร็ว -->
                    <div class="mb-3 flex flex-wrap gap-2">
                        <button v-if="schools.length" type="button" class="rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 transition hover:bg-indigo-100" @click="toggleAllSchools">
                            {{ allSchoolsSelected ? '✕ ยกเลิกทุกโรงเรียน' : 'เลือกทุกโรงเรียน (' + schools.length + ')' }}
                        </button>
                        <button v-if="area.length" type="button" class="rounded-full border border-gray-200 px-3 py-1 text-xs font-medium text-gray-600 transition hover:bg-gray-50" @click="selectAreas">+ เฉพาะเขต</button>
                        <button v-if="search && filteredSchools.length" type="button" class="rounded-full border border-gray-200 px-3 py-1 text-xs font-medium text-gray-600 transition hover:bg-gray-50" @click="selectFiltered">+ เลือกที่ค้นเจอ ({{ filteredSchools.length }})</button>
                        <button v-if="form.to_unit_ids.length" type="button" class="rounded-full border border-rose-200 px-3 py-1 text-xs font-medium text-rose-600 transition hover:bg-rose-50" @click="clearAll">ล้างทั้งหมด</button>
                    </div>

                    <!-- กลุ่มโรงเรียน (เลือกทั้งกลุ่มทีเดียว) -->
                    <div v-if="schoolGroups.length" class="mb-3 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3">
                        <span class="text-xs font-medium text-gray-400">กลุ่มโรงเรียน:</span>
                        <button v-for="g in schoolGroups" :key="g.id" type="button" class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100" @click="addGroup(g)">+ {{ g.name }} ({{ g.unit_ids.length }})</button>
                    </div>

                    <!-- เขต -->
                    <div v-if="area.length" class="mb-2">
                        <label v-for="u in area" :key="u.id" class="flex items-center gap-2 rounded-md px-2 py-1 text-sm hover:bg-gray-50">
                            <input v-model="form.to_unit_ids" type="checkbox" :value="u.id" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            <span class="font-medium text-indigo-700">{{ u.name }}</span>
                        </label>
                    </div>

                    <!-- ค้นหาโรงเรียน -->
                    <div v-if="schools.length" class="relative mb-2">
                        <svg class="pointer-events-none absolute left-2.5 top-2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                        <input v-model="search" type="text" placeholder="ค้นหาโรงเรียน..." class="w-full rounded-md border-gray-300 py-1.5 pl-8 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <div class="grid max-h-56 grid-cols-1 gap-1 overflow-y-auto sm:grid-cols-2">
                        <label v-for="u in filteredSchools" :key="u.id" class="flex items-center gap-2 rounded-md px-2 py-1 text-sm hover:bg-gray-50">
                            <input v-model="form.to_unit_ids" type="checkbox" :value="u.id" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            {{ u.name }}
                        </label>
                        <p v-if="schools.length && !filteredSchools.length" class="col-span-full px-2 py-3 text-center text-xs text-gray-400">ไม่พบโรงเรียนที่ค้นหา</p>
                    </div>
                    <p v-if="form.errors.to_unit_ids" class="mt-1 text-xs text-rose-500">{{ form.errors.to_unit_ids }}</p>
                </div>

                <!-- รายละเอียดหนังสือ -->
                <div class="space-y-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">เลขที่หนังสือ</label>
                            <input v-model="form.number" type="text" :disabled="form.auto_number" :placeholder="form.auto_number ? (bookPrefix ? bookPrefix + '/… (ออกอัตโนมัติ)' : 'ออกอัตโนมัติเมื่อส่ง') : ''" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-400" />
                            <label class="mt-1 flex items-center gap-1.5 text-xs text-gray-500">
                                <input v-model="form.auto_number" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" /> ออกเลขอัตโนมัติ
                            </label>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">ลงวันที่</label>
                            <input v-model="form.doc_date" type="date" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <p v-if="form.errors.doc_date" class="mt-1 text-xs text-rose-500">{{ form.errors.doc_date }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">ความสำคัญ</label>
                            <select v-model="form.priority" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="p in priorities" :key="p.key" :value="p.key">{{ p.label }}</option>
                            </select>
                        </div>
                        <label class="mt-6 flex items-center gap-2 text-sm text-gray-700">
                            <input v-model="form.confidential" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" /> ลับ
                        </label>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">เรื่อง</label>
                        <input v-model="form.subject" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.subject" class="mt-1 text-xs text-rose-500">{{ form.errors.subject }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">อ้างถึง (ถ้ามี)</label>
                        <input v-model="form.reference" type="text" placeholder="เช่น ที่ ศธ 04066/1234 ลงวันที่..." class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">เนื้อหาโดยสรุป</label>
                        <textarea v-model="form.detail" rows="3" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">แนบไฟล์ (หลายไฟล์ได้)</label>
                        <input type="file" multiple class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700" @change="onFiles" />
                        <p v-if="form.attachments.length" class="mt-1 text-xs text-gray-400">{{ form.attachments.length }} ไฟล์</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <SecondaryButton type="button" @click="$inertia.visit(route('saraban.area-mail.outbox'))">ยกเลิก</SecondaryButton>
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60">ส่งหนังสือ</button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
