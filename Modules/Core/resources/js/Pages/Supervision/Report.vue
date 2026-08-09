<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    rounds: { type: Array, default: () => [] },
    selectedRound: { type: [Number, String], default: null },
    bySchool: { type: Array, default: () => [] },
    byStandard: { type: Array, default: () => [] },
    overall: { type: Object, default: () => ({}) },
    qualityLabels: { type: Object, default: () => ({}) },
});

const switchRound = (e) => router.get(route('supervisions.report'), { round: e.target.value }, { preserveState: true, replace: true });
const statusCls = (s) => ({ planned: 'bg-gray-100 text-gray-600', completed: 'bg-amber-100 text-amber-700', acknowledged: 'bg-emerald-100 text-emerald-700' }[s] ?? 'bg-gray-100 text-gray-600');
const pct = (v) => (v ? Math.round((v / 5) * 100) : 0);
</script>

<template>
    <Head title="รายงานการนิเทศระดับเขต" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">รายงานการนิเทศ ระดับเขต</h2>
                <Link :href="route('supervisions.index')" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">← กลับ</Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-500">รอบการนิเทศ:</label>
                    <select :value="selectedRound" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="switchRound">
                        <option value="all">— ทุกรอบ —</option>
                        <option v-for="r in rounds" :key="r.id" :value="r.id">{{ r.name }}{{ r.is_current ? ' (ปัจจุบัน)' : '' }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-100"><p class="text-2xl font-bold text-gray-800">{{ overall.schools ?? 0 }}</p><p class="text-xs text-gray-400">โรงเรียนที่นิเทศในรอบนี้</p></div>
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-indigo-100"><p class="text-2xl font-bold text-indigo-700">{{ overall.avg ?? '—' }}<span v-if="overall.avg" class="text-base"> / 5</span></p><p class="text-xs text-gray-400">คุณภาพเฉลี่ยรวม</p></div>
                </div>

                <!-- สรุปตามมาตรฐาน -->
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <p class="mb-3 text-sm font-semibold text-gray-700">คุณภาพเฉลี่ยตามมาตรฐาน (ทุกโรงเรียนในรอบ)</p>
                    <div v-for="(s, i) in byStandard" :key="i" class="mb-2 flex items-center gap-2 text-sm">
                        <span class="w-64 truncate text-gray-700">{{ s.standard }}</span>
                        <div class="h-3 flex-1 overflow-hidden rounded-full bg-gray-100"><div class="h-3 rounded-full bg-indigo-500" :style="{ width: pct(s.avg) + '%' }" /></div>
                        <span class="w-20 text-right text-gray-500">{{ s.avg ? s.avg + '/5' : '—' }}</span>
                    </div>
                    <p v-if="byStandard.length === 0" class="text-center text-sm text-gray-400">ไม่มีข้อมูล</p>
                </div>

                <!-- สรุปต่อโรงเรียน -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">ผลการนิเทศรายโรงเรียน ({{ bySchool.length }})</div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3">โรงเรียน</th><th class="px-4 py-3 text-center">คุณภาพเฉลี่ย</th><th class="px-4 py-3 text-center">ตัวชี้วัดที่ประเมิน</th><th class="px-4 py-3">สถานะ</th><th class="px-4 py-3 text-center">ดู</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="bySchool.length === 0"><td colspan="5" class="px-6 py-8"><EmptyState title="ยังไม่มีการนิเทศในรอบนี้" /></td></tr>
                            <tr v-for="r in bySchool" :key="r.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ r.school }}</td>
                                <td class="px-4 py-3 text-center font-semibold text-indigo-700">{{ r.avg ? r.avg + '/5' : '—' }}</td>
                                <td class="px-4 py-3 text-center text-gray-500">{{ r.scored }}</td>
                                <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="statusCls(r.status)">{{ r.status_label }}</span></td>
                                <td class="px-4 py-3 text-center"><Link :href="route('supervisions.show', r.id)" class="text-indigo-600 hover:underline">ดู</Link></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
