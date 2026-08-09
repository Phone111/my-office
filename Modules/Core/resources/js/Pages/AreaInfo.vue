<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    summary: { type: Object, default: () => ({}) },
    byGrade: { type: Array, default: () => [] },
    byGender: { type: Object, default: () => ({ M: 0, F: 0 }) },
    bySize: { type: Array, default: () => [] },
    schools: { type: Array, default: () => [] },
});

const fmt = (n) => (n ?? 0).toLocaleString('th-TH');
const maxGrade = computed(() => Math.max(1, ...props.byGrade.map((g) => g.count)));
const genderTotal = computed(() => (props.byGender.M ?? 0) + (props.byGender.F ?? 0));
const pct = (n, t) => (t ? Math.round((n / t) * 100) : 0);

const search = ref('');
const sortKey = ref('students');
const filteredSchools = computed(() => {
    const q = search.value.trim().toLowerCase();
    const list = q ? props.schools.filter((s) => s.name.toLowerCase().includes(q) || (s.code ?? '').includes(q)) : props.schools;
    return [...list].sort((a, b) => (sortKey.value === 'name' ? a.name.localeCompare(b.name, 'th') : b[sortKey.value] - a[sortKey.value]));
});

const sizeColor = { 'เล็ก': 'bg-sky-500', 'กลาง': 'bg-emerald-500', 'ใหญ่': 'bg-amber-500', 'ใหญ่พิเศษ': 'bg-rose-500' };
const sizeChip = {
    'เล็ก': 'bg-sky-100 text-sky-700', 'กลาง': 'bg-emerald-100 text-emerald-700',
    'ใหญ่': 'bg-amber-100 text-amber-700', 'ใหญ่พิเศษ': 'bg-rose-100 text-rose-700',
};
</script>

<template>
    <Head title="สารสนเทศเขต (EMIS)" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">รายงานสารสนเทศเขต (EMIS)</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- การ์ดสรุป -->
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                        <p class="text-sm text-gray-500">โรงเรียนในสังกัด</p>
                        <p class="mt-1 text-3xl font-bold text-indigo-600">{{ fmt(summary.schools) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                        <p class="text-sm text-gray-500">นักเรียนทั้งหมด</p>
                        <p class="mt-1 text-3xl font-bold text-emerald-600">{{ fmt(summary.students) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                        <p class="text-sm text-gray-500">บุคลากร (ในโรงเรียน)</p>
                        <p class="mt-1 text-3xl font-bold text-amber-600">{{ fmt(summary.staff) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                        <p class="text-sm text-gray-500">เฉลี่ย นร./โรงเรียน</p>
                        <p class="mt-1 text-3xl font-bold text-violet-600">{{ fmt(summary.avgPerSchool) }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- นักเรียนแยกชั้น -->
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 lg:col-span-2">
                        <h3 class="mb-3 text-base font-bold text-gray-800">นักเรียนแยกตามชั้น</h3>
                        <p v-if="!byGrade.length" class="py-6 text-center text-sm text-gray-400">ยังไม่มีข้อมูลนักเรียน</p>
                        <div v-else class="space-y-1.5">
                            <div v-for="g in byGrade" :key="g.grade" class="flex items-center gap-2 text-sm">
                                <span class="w-10 shrink-0 text-right text-gray-500">{{ g.grade }}</span>
                                <div class="h-5 flex-1 overflow-hidden rounded bg-gray-100">
                                    <div class="flex h-full items-center justify-end rounded bg-indigo-500 px-2 text-xs font-medium text-white" :style="{ width: Math.max(8, (g.count / maxGrade) * 100) + '%' }">{{ fmt(g.count) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- เพศ + ขนาดโรงเรียน -->
                    <div class="space-y-6">
                        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                            <h3 class="mb-3 text-base font-bold text-gray-800">แยกตามเพศ</h3>
                            <div class="flex items-center gap-4">
                                <div class="flex-1 text-center">
                                    <p class="text-2xl font-bold text-sky-600">{{ fmt(byGender.M) }}</p>
                                    <p class="text-xs text-gray-500">ชาย ({{ pct(byGender.M, genderTotal) }}%)</p>
                                </div>
                                <div class="h-10 w-px bg-gray-200" />
                                <div class="flex-1 text-center">
                                    <p class="text-2xl font-bold text-pink-600">{{ fmt(byGender.F) }}</p>
                                    <p class="text-xs text-gray-500">หญิง ({{ pct(byGender.F, genderTotal) }}%)</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                            <h3 class="mb-3 text-base font-bold text-gray-800">ขนาดโรงเรียน</h3>
                            <div class="space-y-2">
                                <div v-for="s in bySize" :key="s.size" class="flex items-center gap-2 text-sm">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full" :class="sizeColor[s.size]" />
                                    <span class="flex-1 text-gray-600">{{ s.size }}</span>
                                    <span class="font-semibold text-gray-800">{{ fmt(s.count) }} ร.ร.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ตารางรายโรงเรียน -->
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-base font-bold text-gray-800">รายโรงเรียน ({{ fmt(filteredSchools.length) }})</h3>
                        <div class="flex items-center gap-2">
                            <select v-model="sortKey" class="rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="students">เรียงตามนักเรียน (มาก→น้อย)</option>
                                <option value="staff">เรียงตามบุคลากร</option>
                                <option value="name">เรียงตามชื่อ</option>
                            </select>
                            <input v-model="search" type="text" placeholder="ค้นหาโรงเรียน..." class="rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500">
                                    <th class="px-3 py-2">#</th>
                                    <th class="px-3 py-2">โรงเรียน</th>
                                    <th class="px-3 py-2 text-right">นักเรียน</th>
                                    <th class="px-3 py-2 text-right">บุคลากร</th>
                                    <th class="px-3 py-2 text-center">ขนาด</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(s, i) in filteredSchools" :key="s.id" class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-3 py-2 text-gray-400">{{ i + 1 }}</td>
                                    <td class="px-3 py-2">
                                        <span class="font-medium text-gray-800">{{ s.name }}</span>
                                        <span v-if="s.code" class="ml-1 text-xs text-gray-400">({{ s.code }})</span>
                                    </td>
                                    <td class="px-3 py-2 text-right font-semibold text-gray-700">{{ fmt(s.students) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">{{ fmt(s.staff) }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="sizeChip[s.size]">{{ s.size }}</span>
                                    </td>
                                </tr>
                                <tr v-if="!filteredSchools.length">
                                    <td colspan="5" class="px-3 py-6 text-center text-gray-400">ไม่พบโรงเรียน</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
