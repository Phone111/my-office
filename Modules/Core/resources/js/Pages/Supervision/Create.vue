<script setup>
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    schools: { type: Array, default: () => [] },
    aspects: { type: Array, default: () => [] },
    ratings: { type: Array, default: () => [] },
    standards: { type: Array, default: () => [] },
    rounds: { type: Array, default: () => [] },
    qualityOptions: { type: Array, default: () => [] },
    currentRound: { type: [Number, String], default: null },
});

const today = new Date().toISOString().slice(0, 10);
const initialScores = {};
props.standards.forEach((s) => s.indicators.forEach((i) => (initialScores[i.id] = { practiced: false, quality: '' })));

const form = useForm({
    school_unit_id: '',
    round_id: props.currentRound ?? '',
    visit_date: today,
    aspect: 'academic',
    topic: '',
    objective: '',
    scores: initialScores,
    findings: '',
    recommendations: '',
    rating: '',
    attachments: [],
});

const onFiles = (e) => (form.attachments = Array.from(e.target.files));
const submit = () => form.post(route('supervisions.store'), { forceFormData: true });
</script>

<template>
    <Head title="บันทึกการนิเทศ" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">บันทึก / วางแผนการนิเทศ</h2>
        </template>

        <div class="py-10">
            <form class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8" @submit.prevent="submit">
                <!-- ข้อมูลทั่วไป -->
                <div class="space-y-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">โรงเรียนที่นิเทศ</label>
                            <select v-model="form.school_unit_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">— เลือกโรงเรียน —</option>
                                <option v-for="s in schools" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <p v-if="form.errors.school_unit_id" class="mt-1 text-xs text-rose-500">{{ form.errors.school_unit_id }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">รอบการนิเทศ</label>
                            <select v-model="form.round_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">— ไม่ระบุรอบ —</option>
                                <option v-for="r in rounds" :key="r.id" :value="r.id">{{ r.name }}{{ r.is_current ? ' (ปัจจุบัน)' : '' }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">วันที่นิเทศ</label>
                            <input v-model="form.visit_date" type="date" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <p v-if="form.errors.visit_date" class="mt-1 text-xs text-rose-500">{{ form.errors.visit_date }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">ด้านหลัก</label>
                            <select v-model="form.aspect" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="a in aspects" :key="a.key" :value="a.key">{{ a.label }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">ประเด็น/ชื่อการนิเทศ</label>
                        <input v-model="form.topic" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.topic" class="mt-1 text-xs text-rose-500">{{ form.errors.topic }}</p>
                    </div>
                </div>

                <!-- ประเมินตามมาตรฐาน → ตัวชี้วัด -->
                <div v-if="standards.length" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <p class="mb-1 text-sm font-semibold text-gray-700">บันทึกผลตามมาตรฐานการจัดการศึกษา</p>
                    <p class="mb-3 text-xs text-gray-400">ทำเครื่องหมาย "ปฏิบัติ" และเลือกระดับคุณภาพ 1 (น้อยที่สุด) – 5 (มากที่สุด) รายตัวชี้วัด</p>

                    <div v-for="std in standards" :key="std.id" class="mb-4">
                        <p class="mb-1 rounded-md bg-indigo-50 px-3 py-1.5 text-sm font-semibold text-indigo-700">{{ std.name }}</p>
                        <div v-for="ind in std.indicators" :key="ind.id" class="flex items-center gap-3 border-b border-gray-50 px-2 py-2 text-sm">
                            <label class="flex flex-1 items-center gap-2 text-gray-700">
                                <input v-model="form.scores[ind.id].practiced" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <span>{{ ind.name }}</span>
                            </label>
                            <select v-model="form.scores[ind.id].quality" class="w-36 shrink-0 rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">— คุณภาพ —</option>
                                <option v-for="q in qualityOptions" :key="q.key" :value="q.key">{{ q.key }} - {{ q.label }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div v-else class="rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-700 ring-1 ring-amber-100">
                    ยังไม่มีมาตรฐาน/ตัวชี้วัด — ไปตั้งค่าที่เมนู "ตั้งค่าการนิเทศ" ก่อน หรือบันทึกแบบสรุปด้านล่างได้
                </div>

                <!-- สรุป/ข้อเสนอแนะ -->
                <div class="space-y-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">วัตถุประสงค์</label>
                        <textarea v-model="form.objective" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">สภาพที่พบ / สรุปผลการนิเทศ</label>
                        <textarea v-model="form.findings" rows="3" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p class="mt-1 text-xs text-gray-400">เว้นว่างทั้งคะแนนและสรุป = บันทึกเป็นแผนรอนิเทศ</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">ข้อเสนอแนะ</label>
                        <textarea v-model="form.recommendations" rows="3" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">แนบไฟล์/ภาพถ่าย (หลายไฟล์ได้)</label>
                        <input type="file" multiple class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700" @change="onFiles" />
                        <p v-if="form.attachments.length" class="mt-1 text-xs text-gray-400">{{ form.attachments.length }} ไฟล์</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <SecondaryButton type="button" @click="$inertia.visit(route('supervisions.index'))">ยกเลิก</SecondaryButton>
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60">บันทึกการนิเทศ</button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
