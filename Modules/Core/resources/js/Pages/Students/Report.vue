<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    byGrade: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
    unitName: { type: String, default: null },
    units: { type: Array, default: () => [] },
    selectedUnit: { type: [Number, String], default: null },
    canPickSchool: { type: Boolean, default: false },
});
const unitParam = computed(() => (props.canPickSchool ? { unit: props.selectedUnit } : {}));
const switchUnit = (e) => router.get(route('students.report'), { unit: e.target.value }, { preserveState: true, replace: true });
const sumMale = computed(() => props.byGrade.reduce((a, b) => a + b.male, 0));
const sumFemale = computed(() => props.byGrade.reduce((a, b) => a + b.female, 0));
</script>

<template>
    <Head title="สรุปจำนวนนักเรียน" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">สรุปจำนวนนักเรียนรายชั้น</h2>
                    <p class="text-xs text-gray-400">{{ unitName }}</p>
                </div>
                <Link :href="route('students.index', unitParam)" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">← กลับ</Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="canPickSchool" class="flex items-center gap-2">
                    <label class="text-sm text-gray-500">โรงเรียน:</label>
                    <select :value="selectedUnit" class="rounded-md border-gray-300 text-sm" @change="switchUnit"><option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option></select>
                </div>

                <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-indigo-100"><p class="text-3xl font-bold text-indigo-700">{{ total }}</p><p class="text-xs text-gray-400">นักเรียนทั้งหมด (กำลังเรียน)</p></div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50"><tr class="text-left text-xs font-semibold uppercase text-gray-500"><th class="px-4 py-3">ระดับชั้น</th><th class="px-4 py-3 text-center">ชาย</th><th class="px-4 py-3 text-center">หญิง</th><th class="px-4 py-3 text-center">รวม</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="byGrade.length === 0"><td colspan="4" class="px-6 py-8"><EmptyState title="ยังไม่มีข้อมูล" /></td></tr>
                            <tr v-for="(g, i) in byGrade" :key="i" class="text-gray-700">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ g.grade }}</td>
                                <td class="px-4 py-3 text-center text-blue-600">{{ g.male }}</td>
                                <td class="px-4 py-3 text-center text-pink-600">{{ g.female }}</td>
                                <td class="px-4 py-3 text-center font-semibold text-indigo-700">{{ g.total }}</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="byGrade.length" class="bg-gray-50 font-semibold text-gray-800">
                            <tr><td class="px-4 py-3">รวมทั้งหมด</td><td class="px-4 py-3 text-center text-blue-600">{{ sumMale }}</td><td class="px-4 py-3 text-center text-pink-600">{{ sumFemale }}</td><td class="px-4 py-3 text-center text-indigo-700">{{ total }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
