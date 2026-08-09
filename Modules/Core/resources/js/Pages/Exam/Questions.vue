<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    subjects: { type: Array, default: () => [] },
    grades: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    canManage: { type: Boolean, default: false },
});
const flash = computed(() => usePage().props.flash?.success);

const show = ref(false);
const form = useForm({ subject: '', grade: '', standard: '', indicator: '', question: '', options: ['', '', '', ''], answer: 0, score: 1 });
const addOption = () => form.options.push('');
const submit = () => form.post(route('exam.questions.store'), { preserveScroll: true, onSuccess: () => { show.value = false; form.reset(); } });
const remove = (r) => { if (confirm('ลบข้อสอบนี้?')) router.delete(route('exam.questions.destroy', r.id), { preserveScroll: true }); };
const filterBy = (key, val) => router.get(route('exam.questions'), { ...props.filters, [key]: val || undefined }, { preserveState: true, replace: true });
</script>

<template>
    <Head title="คลังข้อสอบ" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ระบบทดสอบการศึกษา · คลังข้อสอบ</h2>
                <div class="flex gap-2">
                    <Link :href="route('exam.tests')" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">แบบทดสอบ</Link>
                    <Link :href="route('exam.runs')" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">รายการสอบ</Link>
                    <button v-if="canManage" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" @click="show = true">เพิ่มข้อสอบ</button>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="flex flex-wrap gap-2">
                    <select :value="filters.subject" class="rounded-md border-gray-300 text-sm" @change="filterBy('subject', $event.target.value)"><option value="">ทุกกลุ่มสาระ</option><option v-for="s in subjects" :key="s" :value="s">{{ s }}</option></select>
                    <select :value="filters.grade" class="rounded-md border-gray-300 text-sm" @change="filterBy('grade', $event.target.value)"><option value="">ทุกชั้น</option><option v-for="g in grades" :key="g" :value="g">{{ g }}</option></select>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">ข้อสอบในคลัง ({{ rows.length }})</div>
                    <ul class="divide-y divide-gray-50">
                        <li v-if="rows.length === 0" class="px-6 py-8"><EmptyState title="ยังไม่มีข้อสอบ" /></li>
                        <li v-for="r in rows" :key="r.id" class="px-6 py-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ r.question }}</p>
                                    <p class="text-xs text-gray-400">{{ r.subject }} · {{ r.grade }}<span v-if="r.indicator"> · {{ r.indicator }}</span> · {{ r.score }} คะแนน</p>
                                    <ul class="mt-1 space-y-0.5">
                                        <li v-for="(o, oi) in r.options" :key="oi" class="text-xs" :class="oi === r.answer ? 'font-semibold text-emerald-600' : 'text-gray-500'">{{ oi === r.answer ? '✔ ' : '• ' }}{{ o }}</li>
                                    </ul>
                                </div>
                                <button v-if="canManage" class="shrink-0 text-rose-600 hover:underline" @click="remove(r)">ลบ</button>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <Modal :show="show" @close="show = false">
            <div class="space-y-3 p-6">
                <h3 class="text-lg font-semibold text-gray-800">เพิ่มข้อสอบ</h3>
                <div class="grid grid-cols-2 gap-2">
                    <select v-model="form.subject" class="rounded-md border-gray-300 text-sm"><option value="">เลือกกลุ่มสาระ</option><option v-for="s in subjects" :key="s" :value="s">{{ s }}</option></select>
                    <select v-model="form.grade" class="rounded-md border-gray-300 text-sm"><option value="">เลือกชั้น</option><option v-for="g in grades" :key="g" :value="g">{{ g }}</option></select>
                </div>
                <input v-model="form.indicator" type="text" placeholder="ตัวชี้วัด (ถ้ามี)" class="w-full rounded-md border-gray-300 text-sm" />
                <textarea v-model="form.question" rows="2" placeholder="โจทย์" class="w-full rounded-md border-gray-300 text-sm" />
                <p v-if="form.errors.question" class="text-xs text-rose-500">{{ form.errors.question }}</p>
                <p class="text-xs font-medium text-gray-500">ตัวเลือก (เลือกข้อที่ถูก)</p>
                <div v-for="(o, oi) in form.options" :key="oi" class="flex items-center gap-2">
                    <input type="radio" :value="oi" v-model="form.answer" class="text-indigo-600" />
                    <input v-model="form.options[oi]" type="text" :placeholder="`ตัวเลือก ${oi + 1}`" class="flex-1 rounded-md border-gray-300 text-sm" />
                </div>
                <button type="button" class="text-xs font-medium text-indigo-600 hover:underline" @click="addOption">+ เพิ่มตัวเลือก</button>
                <div class="flex items-center gap-2"><span class="text-sm text-gray-600">คะแนน</span><input v-model="form.score" type="number" min="1" class="w-20 rounded-md border-gray-300 text-sm" /></div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="show = false">ยกเลิก</SecondaryButton>
                    <button :disabled="form.processing" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60" @click="submit">บันทึกเข้าคลัง</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
