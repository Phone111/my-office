<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    aspects: { type: Object, default: () => ({}) },
    agreement: { type: Object, default: () => ({}) },
    tasks: { type: Array, default: () => [] },
    canReview: { type: Boolean, default: false },
    isOwner: { type: Boolean, default: false },
});

const tasksOf = (aspect) => props.tasks.filter((t) => t.aspect === Number(aspect));

const approveForm = useForm({ approver_note: '' });
const approve = () => approveForm.post(route('pa.approve', props.agreement.id), { preserveScroll: true });

const evalForm = useForm({ score: null });
const evaluate = () => evalForm.post(route('pa.evaluate', props.agreement.id), { preserveScroll: true });

const a = computed(() => props.agreement);
</script>

<template>
    <Head title="ข้อตกลง ว.PA" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">ข้อตกลง ว.PA · ปีงบ {{ a.fiscal_year }}</h2>
                <Link :href="route('pa.index')" class="text-sm text-gray-500 hover:text-gray-700">← กลับ</Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-4xl space-y-5 px-4 sm:px-6 lg:px-8">
                <!-- หัว -->
                <div class="flex flex-wrap items-center justify-between gap-2 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div>
                        <p class="text-lg font-bold text-gray-800">{{ a.owner }}</p>
                        <p class="text-xs text-gray-400">{{ a.position ?? '—' }}</p>
                    </div>
                    <div class="text-right">
                        <StatusBadge :status="a.status" :label="a.status_label" />
                        <p v-if="a.score != null" class="mt-1 text-sm font-bold" :class="a.score >= 70 ? 'text-emerald-600' : 'text-rose-500'">{{ a.score }} คะแนน ({{ a.score >= 70 ? 'ผ่าน' : 'ไม่ผ่าน' }})</p>
                    </div>
                </div>

                <!-- ส่วนที่ 1 -->
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="mb-3 text-base font-bold text-gray-800">ส่วนที่ 1 · ข้อตกลงการพัฒนางานตามมาตรฐานตำแหน่ง</h3>
                    <div v-for="(name, asp) in aspects" :key="asp" class="mb-4">
                        <p class="mb-1 text-sm font-semibold text-indigo-700">{{ asp }}. {{ name }}</p>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-400">
                                    <th class="w-1/2 px-2 py-1">งานที่ดำเนินการ</th>
                                    <th class="w-1/2 px-2 py-1">ผลลัพธ์ที่คาดหวัง</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(t, i) in tasksOf(asp)" :key="i" class="border-t border-gray-50 align-top">
                                    <td class="whitespace-pre-line px-2 py-1.5 text-gray-700">{{ t.task }}</td>
                                    <td class="whitespace-pre-line px-2 py-1.5 text-gray-600">{{ t.expected_outcome || '—' }}</td>
                                </tr>
                                <tr v-if="!tasksOf(asp).length"><td colspan="2" class="px-2 py-1 text-xs text-gray-300">—</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ส่วนที่ 2 -->
                <div class="space-y-2 rounded-2xl bg-white p-6 text-sm shadow-sm ring-1 ring-gray-100">
                    <h3 class="mb-2 text-base font-bold text-gray-800">ส่วนที่ 2 · ประเด็นท้าทาย</h3>
                    <p><span class="font-semibold text-gray-700">ประเด็น:</span> {{ a.challenge_issue || '—' }}</p>
                    <p><span class="font-semibold text-gray-700">สภาพปัญหา:</span> <span class="whitespace-pre-line">{{ a.challenge_problem || '—' }}</span></p>
                    <p><span class="font-semibold text-gray-700">วิธีดำเนินการ:</span> <span class="whitespace-pre-line">{{ a.challenge_method || '—' }}</span></p>
                    <p><span class="font-semibold text-gray-700">ผลเชิงปริมาณ:</span> {{ a.challenge_outcome_quant || '—' }}</p>
                    <p><span class="font-semibold text-gray-700">ผลเชิงคุณภาพ:</span> {{ a.challenge_outcome_qual || '—' }}</p>
                </div>

                <!-- ความเห็น ผอ. -->
                <div v-if="a.approver_note" class="rounded-2xl bg-sky-50 p-4 text-sm text-sky-800 ring-1 ring-sky-100">
                    <span class="font-semibold">ความเห็น ผอ. ({{ a.approver }}):</span> {{ a.approver_note }}
                </div>

                <!-- การกระทำของผู้บริหาร -->
                <div v-if="canReview" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-indigo-100">
                    <h3 class="mb-3 text-base font-bold text-gray-800">การดำเนินการของผู้บริหาร</h3>
                    <!-- เห็นชอบ (เมื่อเสนอ) -->
                    <div v-if="a.status === 'submitted'" class="space-y-2">
                        <textarea v-model="approveForm.approver_note" rows="2" placeholder="ความเห็น/ข้อเสนอแนะ (ถ้ามี)" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <button type="button" :disabled="approveForm.processing" class="rounded-md bg-sky-600 px-5 py-2 text-sm font-semibold text-white hover:bg-sky-500 disabled:opacity-60" @click="approve">เห็นชอบข้อตกลง</button>
                    </div>
                    <!-- ประเมินปลายปี (เมื่อเห็นชอบแล้ว) -->
                    <div v-else-if="a.status === 'approved' || a.status === 'evaluated'" class="flex items-end gap-2">
                        <div>
                            <label class="block text-xs text-gray-500">คะแนนประเมิน (0–100) · ผ่าน ≥ 70</label>
                            <input v-model="evalForm.score" type="number" min="0" max="100" step="0.01" class="mt-1 w-32 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <button type="button" :disabled="evalForm.processing" class="rounded-md bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-60" @click="evaluate">{{ a.status === 'evaluated' ? 'แก้ไขคะแนน' : 'บันทึกผลประเมิน' }}</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
