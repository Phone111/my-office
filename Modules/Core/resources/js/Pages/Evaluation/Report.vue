<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    rounds: { type: Array, default: () => [] },
    selectedRound: { type: [Number, String], default: null },
    byGrade: { type: Array, default: () => [] },
    overall: { type: Object, default: () => ({}) },
    canPickSchool: { type: Boolean, default: false },
    selectedUnit: { type: [Number, String], default: null },
    units: { type: Array, default: () => [] },
});
const switchRound = (e) => router.get(route('evaluations.report'), { round: selectedRound, unit: props.selectedUnit, [e.target.name]: e.target.value }, { preserveState: true, replace: true });
const pct = (c) => (props.overall.count ? Math.round((c / props.overall.count) * 100) : 0);
</script>

<template>
    <Head title="รายงานการประเมิน" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">รายงานผลการประเมิน</h2>
                <Link :href="route('evaluations.index')" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">← กลับ</Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center gap-2">
                    <label class="text-sm text-gray-500">รอบ:</label>
                    <select name="round" :value="selectedRound" class="rounded-md border-gray-300 text-sm" @change="switchRound"><option v-for="r in rounds" :key="r.id" :value="r.id">{{ r.name }}{{ r.is_current ? ' (ปัจจุบัน)' : '' }}</option></select>
                    <template v-if="canPickSchool">
                        <label class="ml-2 text-sm text-gray-500">หน่วยงาน:</label>
                        <select name="unit" :value="selectedUnit ?? ''" class="rounded-md border-gray-300 text-sm" @change="switchRound"><option value="">ทุกหน่วยงาน</option><option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option></select>
                    </template>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-100"><p class="text-2xl font-bold text-gray-800">{{ overall.count ?? 0 }}</p><p class="text-xs text-gray-400">ผู้รับการประเมิน</p></div>
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-indigo-100"><p class="text-2xl font-bold text-indigo-700">{{ overall.avg ?? '—' }}</p><p class="text-xs text-gray-400">ร้อยละเฉลี่ย</p></div>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <p class="mb-3 text-sm font-semibold text-gray-700">จำแนกตามระดับผลการประเมิน</p>
                    <div v-for="(g, i) in byGrade" :key="i" class="mb-2 flex items-center gap-2 text-sm">
                        <span class="w-28 text-gray-700">{{ g.grade }}</span>
                        <div class="h-3 flex-1 overflow-hidden rounded-full bg-gray-100"><div class="h-3 rounded-full bg-indigo-500" :style="{ width: pct(g.count) + '%' }" /></div>
                        <span class="w-20 text-right text-gray-500">{{ g.count }} ({{ pct(g.count) }}%)</span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
