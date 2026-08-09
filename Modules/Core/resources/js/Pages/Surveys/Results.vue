<script setup>
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    survey: { type: Object, required: true },
    questions: { type: Array, default: () => [] },
    typeLabels: { type: Object, default: () => ({}) },
});
const flash = computed(() => usePage().props.flash?.success);
const statusCls = (s) => ({ draft: 'bg-gray-100 text-gray-600', open: 'bg-emerald-100 text-emerald-700', closed: 'bg-rose-100 text-rose-600' }[s] ?? 'bg-gray-100 text-gray-600');

const toggleLabel = computed(() => (props.survey.status === 'open' ? 'ปิดรับคำตอบ' : 'เปิดให้ตอบ'));
const toggle = () => router.post(route('surveys.toggle', props.survey.id), {}, { preserveScroll: true });
const remove = () => {
    if (confirm('ลบแบบสอบถามนี้และคำตอบทั้งหมด?')) router.delete(route('surveys.destroy', props.survey.id));
};
const pct = (count, total) => (total ? Math.round((count / total) * 100) : 0);
</script>

<template>
    <Head :title="`ผล: ${survey.title}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ survey.title }}</h2>
                    <p class="text-xs text-gray-400">สรุปผลแบบสอบถาม · ผู้ตอบ {{ survey.responses }} คน</p>
                </div>
                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="statusCls(survey.status)">{{ survey.status_label }}</span>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="flex gap-2">
                    <button class="rounded-md bg-indigo-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500" @click="toggle">{{ toggleLabel }}</button>
                    <button class="rounded-md bg-rose-50 px-4 py-1.5 text-sm font-semibold text-rose-600 hover:bg-rose-100" @click="remove">ลบ</button>
                </div>

                <div v-if="survey.description" class="rounded-2xl bg-white p-5 text-sm text-gray-600 shadow-sm ring-1 ring-gray-100">{{ survey.description }}</div>

                <div v-for="(q, i) in questions" :key="q.id" class="space-y-3 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-start justify-between">
                        <p class="text-sm font-medium text-gray-800">{{ i + 1 }}. {{ q.text }}</p>
                        <span class="shrink-0 text-xs text-gray-400">{{ typeLabels[q.type] }} · ตอบ {{ q.count }}</span>
                    </div>

                    <!-- rating -->
                    <div v-if="q.type === 'rating'">
                        <p class="mb-2 text-sm text-gray-600">ค่าเฉลี่ย: <span class="text-lg font-bold text-indigo-700">{{ q.average ?? '—' }}</span> / 5</p>
                        <div v-for="r in [5, 4, 3, 2, 1]" :key="r" class="mb-1 flex items-center gap-2 text-xs">
                            <span class="w-4 text-gray-500">{{ r }}</span>
                            <div class="h-3 flex-1 overflow-hidden rounded-full bg-gray-100"><div class="h-3 rounded-full bg-indigo-500" :style="{ width: pct(q.dist[r], q.count) + '%' }" /></div>
                            <span class="w-16 text-right text-gray-400">{{ q.dist[r] }} ({{ pct(q.dist[r], q.count) }}%)</span>
                        </div>
                    </div>

                    <!-- choice -->
                    <div v-else-if="q.type === 'choice'">
                        <div v-for="(o, oi) in q.options" :key="oi" class="mb-1 flex items-center gap-2 text-xs">
                            <span class="w-32 truncate text-gray-600">{{ o.label }}</span>
                            <div class="h-3 flex-1 overflow-hidden rounded-full bg-gray-100"><div class="h-3 rounded-full bg-emerald-500" :style="{ width: pct(o.count, q.count) + '%' }" /></div>
                            <span class="w-16 text-right text-gray-400">{{ o.count }} ({{ pct(o.count, q.count) }}%)</span>
                        </div>
                    </div>

                    <!-- text -->
                    <div v-else>
                        <p v-if="q.texts.length === 0" class="text-sm text-gray-400">ยังไม่มีคำตอบ</p>
                        <ul v-else class="space-y-1.5">
                            <li v-for="(t, ti) in q.texts" :key="ti" class="rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700">{{ t }}</li>
                        </ul>
                    </div>
                </div>

                <SecondaryButton @click="router.visit(route('surveys.index'))">← กลับ</SecondaryButton>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
