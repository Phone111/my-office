<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    standards: { type: Array, default: () => [] },
    rounds: { type: Array, default: () => [] },
});
const flash = computed(() => usePage().props.flash?.success);

const stdForm = useForm({ code: '', name: '' });
const addStandard = () => stdForm.post(route('supervisions.standards.store'), { preserveScroll: true, onSuccess: () => stdForm.reset() });
const delStandard = (s) => { if (confirm(`ลบมาตรฐาน "${s.name}" และตัวชี้วัดทั้งหมด?`)) router.delete(route('supervisions.standards.destroy', s.id), { preserveScroll: true }); };

const indForm = useForm({ standard_id: '', name: '', weight: 1 });
const openInd = ref(null);
const addIndicator = (sid) => { indForm.standard_id = sid; indForm.post(route('supervisions.indicators.store'), { preserveScroll: true, onSuccess: () => { indForm.reset(); indForm.standard_id = sid; } }); };
const delIndicator = (i) => { if (confirm(`ลบตัวชี้วัด "${i.name}"?`)) router.delete(route('supervisions.indicators.destroy', i.id), { preserveScroll: true }); };

const roundForm = useForm({ name: '', academic_year: '', semester: '' });
const addRound = () => roundForm.post(route('supervisions.rounds.store'), { preserveScroll: true, onSuccess: () => roundForm.reset() });
const setCurrent = (r) => router.post(route('supervisions.rounds.current', r.id), {}, { preserveScroll: true });
const delRound = (r) => { if (confirm(`ลบรอบ "${r.name}"?`)) router.delete(route('supervisions.rounds.destroy', r.id), { preserveScroll: true }); };
</script>

<template>
    <Head title="ตั้งค่าการนิเทศ" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ตั้งค่ากรอบการนิเทศ (มาตรฐาน · ตัวชี้วัด · รอบ)</h2>
                <Link :href="route('supervisions.index')" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">← กลับ</Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <!-- รอบการนิเทศ -->
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <p class="mb-3 text-sm font-semibold text-gray-700">รอบการนิเทศ (รายภาคเรียน)</p>
                    <div class="mb-3 flex flex-wrap items-end gap-2">
                        <input v-model="roundForm.name" type="text" placeholder="เช่น ภาคเรียนที่ 1 ปีการศึกษา 2569 ครั้งที่ 1" class="flex-1 rounded-md border-gray-300 text-sm" />
                        <input v-model="roundForm.academic_year" type="number" placeholder="ปีการศึกษา" class="w-28 rounded-md border-gray-300 text-sm" />
                        <select v-model="roundForm.semester" class="w-24 rounded-md border-gray-300 text-sm"><option value="">ภาค</option><option value="1">1</option><option value="2">2</option></select>
                        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" @click="addRound">เพิ่ม</button>
                    </div>
                    <ul class="divide-y divide-gray-50">
                        <li v-for="r in rounds" :key="r.id" class="flex items-center justify-between py-2 text-sm">
                            <span class="text-gray-800">{{ r.name }} <span v-if="r.is_current" class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">ปัจจุบัน</span></span>
                            <span class="flex gap-3">
                                <button v-if="!r.is_current" class="text-indigo-600 hover:underline" @click="setCurrent(r)">ตั้งเป็นปัจจุบัน</button>
                                <button class="text-rose-600 hover:underline" @click="delRound(r)">ลบ</button>
                            </span>
                        </li>
                        <li v-if="rounds.length === 0" class="py-3 text-center text-sm text-gray-400">ยังไม่มีรอบ</li>
                    </ul>
                </div>

                <!-- มาตรฐาน + ตัวชี้วัด -->
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <p class="mb-3 text-sm font-semibold text-gray-700">มาตรฐาน &amp; ตัวชี้วัด</p>
                    <div class="mb-4 flex flex-wrap items-end gap-2">
                        <input v-model="stdForm.code" type="text" placeholder="รหัส (ถ้ามี)" class="w-28 rounded-md border-gray-300 text-sm" />
                        <input v-model="stdForm.name" type="text" placeholder="ชื่อมาตรฐาน เช่น ด้านผู้เรียน" class="flex-1 rounded-md border-gray-300 text-sm" />
                        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" @click="addStandard">เพิ่มมาตรฐาน</button>
                    </div>

                    <div v-for="s in standards" :key="s.id" class="mb-3 overflow-hidden rounded-lg ring-1 ring-gray-100">
                        <div class="flex items-center justify-between bg-indigo-50 px-3 py-2">
                            <p class="text-sm font-semibold text-indigo-700">{{ s.code ? s.code + ' ' : '' }}{{ s.name }}</p>
                            <span class="flex gap-3 text-xs">
                                <button class="text-indigo-600 hover:underline" @click="openInd = openInd === s.id ? null : s.id">+ ตัวชี้วัด</button>
                                <button class="text-rose-600 hover:underline" @click="delStandard(s)">ลบ</button>
                            </span>
                        </div>
                        <div v-if="openInd === s.id" class="flex items-end gap-2 bg-gray-50 px-3 py-2">
                            <input v-model="indForm.name" type="text" placeholder="ชื่อตัวชี้วัด" class="flex-1 rounded-md border-gray-300 text-sm" />
                            <input v-model="indForm.weight" type="number" min="1" placeholder="น้ำหนัก" class="w-24 rounded-md border-gray-300 text-sm" />
                            <button class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500" @click="addIndicator(s.id)">เพิ่ม</button>
                        </div>
                        <ul class="divide-y divide-gray-50">
                            <li v-for="i in s.indicators" :key="i.id" class="flex items-center justify-between px-3 py-1.5 text-sm">
                                <span class="text-gray-700">{{ i.name }} <span class="text-xs text-gray-400">(น้ำหนัก {{ i.weight }})</span></span>
                                <button class="text-rose-500 hover:underline" @click="delIndicator(i)">ลบ</button>
                            </li>
                            <li v-if="s.indicators.length === 0" class="px-3 py-2 text-xs text-gray-400">ยังไม่มีตัวชี้วัด</li>
                        </ul>
                    </div>
                    <p v-if="standards.length === 0" class="text-center text-sm text-gray-400">ยังไม่มีมาตรฐาน</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
