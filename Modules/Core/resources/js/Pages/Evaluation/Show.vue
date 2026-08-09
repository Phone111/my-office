<script setup>
import SecondaryButton from '@/Components/SecondaryButton.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    item: { type: Object, required: true },
    canEdit: { type: Boolean, default: false },
    canAck: { type: Boolean, default: false },
    criteria: { type: Array, default: () => [] },
});
const flash = computed(() => usePage().props.flash?.success);
const statusLabel = { draft: 'ฉบับร่าง', evaluated: 'ประเมินแล้ว (รอรับทราบ)', acknowledged: 'รับทราบแล้ว' };

const editing = ref(false);
const initScores = {};
props.criteria.forEach((c) => (initScores[c.id] = c.score ?? ''));
const edit = useForm({ scores: initScores, strengths: props.item.strengths ?? '', improvements: props.item.improvements ?? '', evaluator_comment: props.item.evaluator_comment ?? '' });
const saveEdit = () => edit.put(route('evaluations.update', props.item.id), { preserveScroll: true, onSuccess: () => (editing.value = false) });

const ack = useForm({ evaluee_note: '' });
const submitAck = () => ack.post(route('evaluations.acknowledge', props.item.id), { preserveScroll: true });

const remove = () => { if (confirm('ลบการประเมินนี้?')) router.delete(route('evaluations.destroy', props.item.id)); };
</script>

<template>
    <Head title="ผลการประเมิน" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">ผลการประเมินการปฏิบัติงาน</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div v-if="!editing" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-lg font-semibold text-gray-900">{{ item.evaluee }}</p>
                            <p class="text-sm text-gray-500">ผู้ประเมิน: {{ item.evaluator ?? '—' }}<span v-if="item.round"> · {{ item.round }}</span></p>
                        </div>
                        <StatusBadge :status="item.status" :label="statusLabel[item.status] ?? item.status" />
                    </div>

                    <div class="mt-4 flex items-center gap-6 rounded-lg bg-indigo-50 px-4 py-3">
                        <div><p class="text-xs text-gray-500">คะแนนรวม</p><p class="text-xl font-bold text-gray-800">{{ item.total_score ?? '—' }}</p></div>
                        <div><p class="text-xs text-gray-500">ร้อยละ</p><p class="text-xl font-bold text-indigo-700">{{ item.percent ?? '—' }}</p></div>
                        <div><p class="text-xs text-gray-500">ระดับ</p><p class="text-xl font-bold text-indigo-700">{{ item.grade ?? '—' }}</p></div>
                    </div>

                    <div class="mt-4">
                        <p class="mb-1 text-sm font-medium text-gray-700">คะแนนรายองค์ประกอบ</p>
                        <div v-for="(s, i) in item.scores" :key="i" class="flex items-center justify-between border-b border-gray-50 px-1 py-1.5 text-sm">
                            <span class="flex-1 text-gray-700">{{ s.name }}</span>
                            <span class="shrink-0 font-semibold text-gray-800">{{ s.score ?? '—' }} / {{ s.max_score }}</span>
                        </div>
                    </div>

                    <div v-if="item.strengths" class="mt-3"><p class="text-sm font-medium text-gray-700">จุดเด่น</p><p class="mt-1 rounded-lg bg-gray-50 px-4 py-2 text-sm text-gray-700 whitespace-pre-line">{{ item.strengths }}</p></div>
                    <div v-if="item.improvements" class="mt-3"><p class="text-sm font-medium text-gray-700">สิ่งที่ควรพัฒนา</p><p class="mt-1 rounded-lg bg-amber-50 px-4 py-2 text-sm text-amber-800 whitespace-pre-line">{{ item.improvements }}</p></div>
                    <div v-if="item.evaluator_comment" class="mt-3"><p class="text-sm font-medium text-gray-700">ความเห็นผู้ประเมิน</p><p class="mt-1 rounded-lg bg-gray-50 px-4 py-2 text-sm text-gray-700 whitespace-pre-line">{{ item.evaluator_comment }}</p></div>

                    <div v-if="item.evaluee_note" class="mt-3 rounded-lg bg-emerald-50 px-4 py-3 ring-1 ring-emerald-100">
                        <p class="text-sm font-medium text-emerald-800">ความเห็นผู้รับการประเมิน</p>
                        <p class="mt-1 text-sm text-emerald-700 whitespace-pre-line">{{ item.evaluee_note }}</p>
                        <p v-if="item.acknowledged_thai" class="mt-1 text-xs text-emerald-500">รับทราบเมื่อ {{ item.acknowledged_thai }}</p>
                    </div>

                    <div class="mt-5 flex gap-2">
                        <button v-if="canEdit" class="rounded-md bg-indigo-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500" @click="editing = true">แก้ไข</button>
                        <button v-if="canEdit" class="rounded-md bg-rose-50 px-4 py-1.5 text-sm font-semibold text-rose-600 hover:bg-rose-100" @click="remove">ลบ</button>
                    </div>
                </div>

                <!-- แก้ไข (ผู้ประเมิน) -->
                <div v-else class="space-y-3 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-indigo-100">
                    <p class="text-sm font-semibold text-gray-700">แก้ไขผลการประเมิน</p>
                    <div v-for="c in criteria" :key="c.id" class="flex items-center gap-3 border-b border-gray-50 pb-2">
                        <span class="flex-1 text-sm text-gray-700">{{ c.name }}</span>
                        <input v-model="edit.scores[c.id]" type="number" step="0.01" min="0" :max="c.max_score" class="w-24 rounded-md border-gray-300 text-sm text-right" /><span class="text-sm text-gray-400">/ {{ c.max_score }}</span>
                    </div>
                    <div><label class="mb-1 block text-sm text-gray-600">จุดเด่น</label><textarea v-model="edit.strengths" rows="2" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="mb-1 block text-sm text-gray-600">สิ่งที่ควรพัฒนา</label><textarea v-model="edit.improvements" rows="2" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="mb-1 block text-sm text-gray-600">ความเห็นผู้ประเมิน</label><textarea v-model="edit.evaluator_comment" rows="2" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div class="flex justify-end gap-2">
                        <SecondaryButton type="button" @click="editing = false">ยกเลิก</SecondaryButton>
                        <button :disabled="edit.processing" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60" @click="saveEdit">บันทึก</button>
                    </div>
                </div>

                <!-- ผู้รับการประเมินรับทราบ -->
                <div v-if="canAck" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-emerald-100">
                    <p class="mb-2 text-sm font-semibold text-gray-700">รับทราบผลการประเมิน</p>
                    <label class="mb-1 block text-sm text-gray-600">ความเห็น (ถ้ามี)</label>
                    <textarea v-model="ack.evaluee_note" rows="3" class="w-full rounded-md border-gray-300 text-sm" />
                    <div class="mt-3 flex justify-end">
                        <button :disabled="ack.processing" class="rounded-md bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60" @click="submitAck">รับทราบผลการประเมิน</button>
                    </div>
                </div>

                <SecondaryButton @click="router.visit(route('evaluations.index'))">← กลับ</SecondaryButton>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
