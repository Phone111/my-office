<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    run: { type: Object, required: true },
    results: { type: Array, default: () => [] },
    schools: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    canManage: { type: Boolean, default: false },
});
const flash = computed(() => usePage().props.flash?.success);

const show = ref(false);
const form = useForm({ school_unit_id: '', students: '', passed: '', avg_percent: '', note: '' });
const submit = () => form.post(route('exam.run.result', props.run.id), { preserveScroll: true, onSuccess: () => { show.value = false; form.reset(); } });
const remove = (r) => { if (confirm(`ลบผลของ "${r.school}"?`)) router.delete(route('exam.run.result.destroy', [props.run.id, r.id]), { preserveScroll: true }); };
</script>

<template>
    <Head :title="run.name" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ run.name }}</h2>
                    <p class="text-xs text-gray-400">{{ run.test }} · {{ run.subject }} · {{ run.grade }}</p>
                </div>
                <Link :href="route('exam.runs')" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">← กลับ</Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-100"><p class="text-2xl font-bold text-gray-800">{{ summary.schools }}</p><p class="text-xs text-gray-400">โรงเรียนที่ส่งผล</p></div>
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-100"><p class="text-2xl font-bold text-gray-700">{{ summary.students }}</p><p class="text-xs text-gray-400">ผู้เข้าสอบรวม</p></div>
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-emerald-100"><p class="text-2xl font-bold text-emerald-600">{{ summary.pass_rate ?? '—' }}<span v-if="summary.pass_rate">%</span></p><p class="text-xs text-gray-400">ผ่านเกณฑ์</p></div>
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-indigo-100"><p class="text-2xl font-bold text-indigo-700">{{ summary.avg_percent ?? '—' }}</p><p class="text-xs text-gray-400">คะแนนเฉลี่ย %</p></div>
                </div>

                <div class="flex justify-end">
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" @click="show = true">บันทึก/ป้อนผลโรงเรียน</button>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">ผลการสอบรายโรงเรียน ({{ results.length }})</div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50"><tr class="text-left text-xs font-semibold uppercase text-gray-500"><th class="px-4 py-3">โรงเรียน</th><th class="px-4 py-3 text-center">ผู้เข้าสอบ</th><th class="px-4 py-3 text-center">ผ่านเกณฑ์</th><th class="px-4 py-3 text-center">เฉลี่ย %</th><th class="px-4 py-3 text-center">ลบ</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="results.length === 0"><td colspan="5" class="px-6 py-8"><EmptyState title="ยังไม่มีผล" /></td></tr>
                            <tr v-for="r in results" :key="r.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ r.school }}</td>
                                <td class="px-4 py-3 text-center">{{ r.students }}</td>
                                <td class="px-4 py-3 text-center text-emerald-600">{{ r.passed }}</td>
                                <td class="px-4 py-3 text-center font-semibold text-indigo-700">{{ r.avg_percent ?? '—' }}</td>
                                <td class="px-4 py-3 text-center"><button v-if="canManage" class="text-rose-600 hover:underline" @click="remove(r)">ลบ</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="show" @close="show = false">
            <div class="space-y-3 p-6">
                <h3 class="text-lg font-semibold text-gray-800">ป้อนผลการสอบรายโรงเรียน</h3>
                <select v-model="form.school_unit_id" class="w-full rounded-md border-gray-300 text-sm"><option value="">เลือกโรงเรียน</option><option v-for="s in schools" :key="s.id" :value="s.id">{{ s.name }}</option></select>
                <p v-if="form.errors.school_unit_id" class="text-xs text-rose-500">{{ form.errors.school_unit_id }}</p>
                <div class="grid grid-cols-3 gap-2">
                    <div><label class="mb-1 block text-xs text-gray-500">ผู้เข้าสอบ</label><input v-model="form.students" type="number" min="0" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="mb-1 block text-xs text-gray-500">ผ่านเกณฑ์</label><input v-model="form.passed" type="number" min="0" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="mb-1 block text-xs text-gray-500">เฉลี่ย %</label><input v-model="form.avg_percent" type="number" step="0.01" min="0" max="100" class="w-full rounded-md border-gray-300 text-sm" /></div>
                </div>
                <input v-model="form.note" type="text" placeholder="หมายเหตุ" class="w-full rounded-md border-gray-300 text-sm" />
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="show = false">ยกเลิก</SecondaryButton>
                    <button :disabled="form.processing" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60" @click="submit">บันทึกผล</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
