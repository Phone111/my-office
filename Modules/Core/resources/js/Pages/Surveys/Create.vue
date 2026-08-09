<script setup>
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    types: { type: Array, default: () => [] },
});

const form = useForm({
    title: '',
    description: '',
    anonymous: false,
    opens_at: '',
    closes_at: '',
    open_now: true,
    questions: [{ text: '', type: 'rating', required: true, options: ['', ''] }],
});

const addQuestion = () => form.questions.push({ text: '', type: 'rating', required: true, options: ['', ''] });
const removeQuestion = (i) => form.questions.splice(i, 1);
const addOption = (q) => q.options.push('');
const removeOption = (q, i) => q.options.splice(i, 1);
const submit = () => form.post(route('surveys.store'));
</script>

<template>
    <Head title="สร้างแบบสอบถาม" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">สร้างแบบสอบถาม</h2>
        </template>

        <div class="py-10">
            <form class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8" @submit.prevent="submit">
                <!-- ข้อมูลทั่วไป -->
                <div class="space-y-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">ชื่อแบบสอบถาม</label>
                        <input v-model="form.title" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-rose-500">{{ form.errors.title }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">คำอธิบาย</label>
                        <textarea v-model="form.description" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">เปิดรับ (ถ้ามี)</label>
                            <input v-model="form.opens_at" type="date" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">ปิดรับ (ถ้ามี)</label>
                            <input v-model="form.closes_at" type="date" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-5">
                        <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.open_now" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" /> เปิดให้ตอบทันที</label>
                        <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.anonymous" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" /> ตอบแบบไม่ระบุชื่อ</label>
                    </div>
                </div>

                <!-- คำถาม -->
                <div v-for="(q, i) in form.questions" :key="i" class="space-y-3 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-700">คำถามที่ {{ i + 1 }}</span>
                        <button v-if="form.questions.length > 1" type="button" class="text-xs font-medium text-rose-600 hover:underline" @click="removeQuestion(i)">ลบคำถาม</button>
                    </div>
                    <input v-model="q.text" type="text" placeholder="ข้อความคำถาม" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <div class="flex flex-wrap items-center gap-3">
                        <select v-model="q.type" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option v-for="t in types" :key="t.key" :value="t.key">{{ t.label }}</option>
                        </select>
                        <label class="flex items-center gap-2 text-sm text-gray-600"><input v-model="q.required" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" /> จำเป็นต้องตอบ</label>
                    </div>
                    <!-- ตัวเลือก (สำหรับ choice) -->
                    <div v-if="q.type === 'choice'" class="space-y-2 rounded-lg bg-gray-50 p-3">
                        <p class="text-xs font-medium text-gray-500">ตัวเลือก</p>
                        <div v-for="(o, oi) in q.options" :key="oi" class="flex items-center gap-2">
                            <input v-model="q.options[oi]" type="text" :placeholder="`ตัวเลือกที่ ${oi + 1}`" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <button v-if="q.options.length > 1" type="button" class="shrink-0 text-rose-500 hover:text-rose-700" @click="removeOption(q, oi)">✕</button>
                        </div>
                        <button type="button" class="text-xs font-medium text-indigo-600 hover:underline" @click="addOption(q)">+ เพิ่มตัวเลือก</button>
                    </div>
                    <p v-if="q.type === 'rating'" class="text-xs text-gray-400">ผู้ตอบเลือกระดับ 1 (น้อยที่สุด) ถึง 5 (มากที่สุด)</p>
                </div>

                <button type="button" class="w-full rounded-xl border-2 border-dashed border-gray-200 py-3 text-sm font-medium text-indigo-600 hover:bg-indigo-50" @click="addQuestion">+ เพิ่มคำถาม</button>
                <p v-if="form.errors.questions" class="text-xs text-rose-500">{{ form.errors.questions }}</p>

                <div class="flex justify-end gap-3">
                    <SecondaryButton type="button" @click="$inertia.visit(route('surveys.index'))">ยกเลิก</SecondaryButton>
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60">บันทึกแบบสอบถาม</button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
