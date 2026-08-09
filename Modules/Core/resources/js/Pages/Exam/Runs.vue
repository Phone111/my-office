<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    runs: { type: Array, default: () => [] },
    tests: { type: Array, default: () => [] },
});
const flash = computed(() => usePage().props.flash?.success);

const show = ref(false);
const form = useForm({ exam_test_id: '', name: '', academic_year: '', round: '' });
const submit = () => form.post(route('exam.runs.store'), { preserveScroll: true, onSuccess: () => { show.value = false; form.reset(); } });
const remove = (r) => { if (confirm(`ลบรายการสอบ "${r.name}"?`)) router.delete(route('exam.runs.destroy', r.id), { preserveScroll: true }); };
</script>

<template>
    <Head title="รายการสอบ" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ระบบทดสอบการศึกษา · รายการสอบ</h2>
                <div class="flex gap-2">
                    <Link :href="route('exam.questions')" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">คลังข้อสอบ</Link>
                    <Link :href="route('exam.tests')" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">แบบทดสอบ</Link>
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" @click="show = true">สร้างรายการสอบ</button>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50"><tr class="text-left text-xs font-semibold uppercase text-gray-500"><th class="px-4 py-3">รายการสอบ</th><th class="px-4 py-3">แบบทดสอบ</th><th class="px-4 py-3">ปี/ครั้ง</th><th class="px-4 py-3 text-center">ผล(ร.ร.)</th><th class="px-4 py-3 text-center">จัดการ</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="runs.length === 0"><td colspan="5" class="px-6 py-8"><EmptyState title="ยังไม่มีรายการสอบ" /></td></tr>
                            <tr v-for="r in runs" :key="r.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ r.name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ r.test ?? '—' }}<span v-if="r.subject" class="block text-xs text-gray-400">{{ r.subject }} · {{ r.grade }}</span></td>
                                <td class="px-4 py-3 text-gray-500">{{ r.academic_year ?? '—' }}{{ r.round ? ' ครั้งที่ '+r.round : '' }}</td>
                                <td class="px-4 py-3 text-center text-indigo-700 font-semibold">{{ r.results }}</td>
                                <td class="px-4 py-3 text-center">
                                    <Link :href="route('exam.run', r.id)" class="text-indigo-600 hover:underline">ผล/รายงาน</Link>
                                    <button class="ml-2 text-rose-600 hover:underline" @click="remove(r)">ลบ</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="show" @close="show = false">
            <div class="space-y-3 p-6">
                <h3 class="text-lg font-semibold text-gray-800">สร้างรายการสอบ</h3>
                <select v-model="form.exam_test_id" class="w-full rounded-md border-gray-300 text-sm"><option value="">เลือกแบบทดสอบ</option><option v-for="t in tests" :key="t.id" :value="t.id">{{ t.name }} ({{ t.subject }}/{{ t.grade }})</option></select>
                <p v-if="form.errors.exam_test_id" class="text-xs text-rose-500">{{ form.errors.exam_test_id }}</p>
                <input v-model="form.name" type="text" placeholder="ชื่อรายการสอบ เช่น คณิตศาสตร์ ป.6 ปีการศึกษา 2569 ครั้งที่ 1" class="w-full rounded-md border-gray-300 text-sm" />
                <p v-if="form.errors.name" class="text-xs text-rose-500">{{ form.errors.name }}</p>
                <div class="grid grid-cols-2 gap-2">
                    <input v-model="form.academic_year" type="number" placeholder="ปีการศึกษา" class="rounded-md border-gray-300 text-sm" />
                    <input v-model="form.round" type="text" placeholder="ครั้งที่" class="rounded-md border-gray-300 text-sm" />
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="show = false">ยกเลิก</SecondaryButton>
                    <button :disabled="form.processing" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60" @click="submit">สร้าง</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
