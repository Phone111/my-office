<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    tests: { type: Array, default: () => [] },
    subjects: { type: Array, default: () => [] },
    grades: { type: Array, default: () => [] },
    questionBank: { type: Array, default: () => [] },
});
const flash = computed(() => usePage().props.flash?.success);

const show = ref(false);
const form = useForm({ name: '', subject: '', grade: '', question_ids: [] });
const filtered = computed(() => props.questionBank.filter((q) => (!form.subject || q.subject === form.subject) && (!form.grade || q.grade === form.grade)));
const submit = () => form.post(route('exam.tests.store'), { preserveScroll: true, onSuccess: () => { show.value = false; form.reset(); } });
const remove = (t) => { if (confirm(`ลบแบบทดสอบ "${t.name}"?`)) router.delete(route('exam.tests.destroy', t.id), { preserveScroll: true }); };
</script>

<template>
    <Head title="แบบทดสอบ" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ระบบทดสอบการศึกษา · แบบทดสอบ (ต้นฉบับ)</h2>
                <div class="flex gap-2">
                    <Link :href="route('exam.questions')" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">คลังข้อสอบ</Link>
                    <Link :href="route('exam.runs')" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">รายการสอบ</Link>
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" @click="show = true">สร้างแบบทดสอบ</button>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50"><tr class="text-left text-xs font-semibold uppercase text-gray-500"><th class="px-4 py-3">ชื่อแบบทดสอบ</th><th class="px-4 py-3">กลุ่มสาระ</th><th class="px-4 py-3">ชั้น</th><th class="px-4 py-3 text-center">จำนวนข้อ</th><th class="px-4 py-3 text-center">ลบ</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="tests.length === 0"><td colspan="5" class="px-6 py-8"><EmptyState title="ยังไม่มีแบบทดสอบ" /></td></tr>
                            <tr v-for="t in tests" :key="t.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ t.name }}</td><td class="px-4 py-3 text-gray-600">{{ t.subject }}</td><td class="px-4 py-3 text-gray-600">{{ t.grade }}</td><td class="px-4 py-3 text-center text-indigo-700 font-semibold">{{ t.questions }}</td>
                                <td class="px-4 py-3 text-center"><button class="text-rose-600 hover:underline" @click="remove(t)">ลบ</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="show" @close="show = false" max-width="2xl">
            <div class="space-y-3 p-6">
                <h3 class="text-lg font-semibold text-gray-800">สร้างแบบทดสอบ</h3>
                <input v-model="form.name" type="text" placeholder="ชื่อแบบทดสอบ เช่น คณิตศาสตร์ ป.6 ชุดที่ 1" class="w-full rounded-md border-gray-300 text-sm" />
                <p v-if="form.errors.name" class="text-xs text-rose-500">{{ form.errors.name }}</p>
                <div class="grid grid-cols-2 gap-2">
                    <select v-model="form.subject" class="rounded-md border-gray-300 text-sm"><option value="">เลือกกลุ่มสาระ</option><option v-for="s in subjects" :key="s" :value="s">{{ s }}</option></select>
                    <select v-model="form.grade" class="rounded-md border-gray-300 text-sm"><option value="">เลือกชั้น</option><option v-for="g in grades" :key="g" :value="g">{{ g }}</option></select>
                </div>
                <p class="text-xs font-medium text-gray-500">เลือกข้อสอบจากคลัง ({{ form.question_ids.length }} ข้อ)</p>
                <div class="max-h-64 space-y-1 overflow-y-auto rounded-lg ring-1 ring-gray-100 p-2">
                    <p v-if="filtered.length === 0" class="py-4 text-center text-xs text-gray-400">ไม่มีข้อสอบตรงเงื่อนไข (เลือกกลุ่มสาระ/ชั้นก่อน)</p>
                    <label v-for="q in filtered" :key="q.id" class="flex items-start gap-2 rounded px-2 py-1 text-sm hover:bg-gray-50">
                        <input v-model="form.question_ids" type="checkbox" :value="q.id" class="mt-1 rounded border-gray-300 text-indigo-600" />
                        <span>{{ q.question }} <span class="text-xs text-gray-400">({{ q.subject }}/{{ q.grade }})</span></span>
                    </label>
                </div>
                <p v-if="form.errors.question_ids" class="text-xs text-rose-500">{{ form.errors.question_ids }}</p>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="show = false">ยกเลิก</SecondaryButton>
                    <button :disabled="form.processing" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60" @click="submit">สร้างแบบทดสอบ</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
