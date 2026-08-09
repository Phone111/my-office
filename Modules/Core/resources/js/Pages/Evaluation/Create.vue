<script setup>
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    people: { type: Array, default: () => [] },
    rounds: { type: Array, default: () => [] },
    criteria: { type: Array, default: () => [] },
    currentRound: { type: [Number, String], default: null },
});

const initScores = {};
props.criteria.forEach((c) => (initScores[c.id] = ''));
const form = useForm({ evaluee_id: '', round_id: props.currentRound ?? '', scores: initScores, strengths: '', improvements: '', evaluator_comment: '' });

const total = computed(() => props.criteria.reduce((a, c) => a + (parseFloat(form.scores[c.id]) || 0), 0));
const maxTotal = computed(() => props.criteria.reduce((a, c) => a + parseFloat(c.max_score), 0));
const percent = computed(() => (maxTotal.value ? Math.round((total.value / maxTotal.value) * 10000) / 100 : 0));
const grade = computed(() => (percent.value >= 90 ? 'ดีเด่น' : percent.value >= 80 ? 'ดีมาก' : percent.value >= 70 ? 'ดี' : percent.value >= 60 ? 'พอใช้' : 'ต้องปรับปรุง'));

const submit = () => form.post(route('evaluations.store'));
</script>

<template>
    <Head title="ประเมินบุคลากร" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">ประเมินผลการปฏิบัติงาน</h2>
        </template>

        <div class="py-10">
            <form class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8" @submit.prevent="submit">
                <div class="grid grid-cols-2 gap-3 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">ผู้รับการประเมิน</label>
                        <select v-model="form.evaluee_id" class="w-full rounded-md border-gray-300 text-sm"><option value="">— เลือกบุคลากร —</option><option v-for="p in people" :key="p.id" :value="p.id">{{ p.name }}</option></select>
                        <p v-if="form.errors.evaluee_id" class="mt-1 text-xs text-rose-500">{{ form.errors.evaluee_id }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">รอบการประเมิน</label>
                        <select v-model="form.round_id" class="w-full rounded-md border-gray-300 text-sm"><option value="">— ไม่ระบุ —</option><option v-for="r in rounds" :key="r.id" :value="r.id">{{ r.name }}{{ r.is_current ? ' (ปัจจุบัน)' : '' }}</option></select>
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <p class="mb-3 text-sm font-semibold text-gray-700">ให้คะแนนรายองค์ประกอบ</p>
                    <div v-for="c in criteria" :key="c.id" class="mb-3 flex items-center gap-3 border-b border-gray-50 pb-3">
                        <span class="flex-1 text-sm text-gray-700">{{ c.name }}</span>
                        <div class="flex shrink-0 items-center gap-1">
                            <input v-model="form.scores[c.id]" type="number" step="0.01" min="0" :max="c.max_score" class="w-24 rounded-md border-gray-300 text-sm text-right" />
                            <span class="text-sm text-gray-400">/ {{ c.max_score }}</span>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center justify-end gap-4 text-sm">
                        <span class="text-gray-500">รวม <span class="font-bold text-gray-800">{{ total }}</span> / {{ maxTotal }}</span>
                        <span class="text-gray-500">ร้อยละ <span class="font-bold text-indigo-700">{{ percent }}</span></span>
                        <span class="rounded-full bg-indigo-50 px-3 py-1 font-semibold text-indigo-700">{{ grade }}</span>
                    </div>
                    <p v-if="form.errors.scores" class="mt-1 text-xs text-rose-500">{{ form.errors.scores }}</p>
                </div>

                <div class="space-y-3 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div><label class="mb-1 block text-sm font-medium text-gray-700">จุดเด่น</label><textarea v-model="form.strengths" rows="2" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="mb-1 block text-sm font-medium text-gray-700">สิ่งที่ควรพัฒนา</label><textarea v-model="form.improvements" rows="2" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="mb-1 block text-sm font-medium text-gray-700">ความเห็นผู้ประเมิน</label><textarea v-model="form.evaluator_comment" rows="2" class="w-full rounded-md border-gray-300 text-sm" /></div>
                </div>

                <div class="flex justify-end gap-3">
                    <SecondaryButton type="button" @click="$inertia.visit(route('evaluations.index'))">ยกเลิก</SecondaryButton>
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-6 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">บันทึก + แจ้งผู้รับการประเมิน</button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
