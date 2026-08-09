<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    isArea: { type: Boolean, default: false },
    stats: { type: Object, default: () => ({}) },
});
const flash = computed(() => usePage().props.flash?.success);
const statusCls = (s) => ({ planned: 'bg-gray-100 text-gray-600', completed: 'bg-amber-100 text-amber-700', acknowledged: 'bg-emerald-100 text-emerald-700' }[s] ?? 'bg-gray-100 text-gray-600');
</script>

<template>
    <Head title="นิเทศการศึกษา" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">ระบบนิเทศการศึกษา</h2>
                    <p class="text-xs text-gray-400">{{ isArea ? 'เขต — นิเทศโรงเรียนในสังกัด' : 'โรงเรียน — ผลการนิเทศที่ได้รับ' }}</p>
                </div>
                <div v-if="isArea" class="flex items-center gap-2">
                    <Link :href="route('supervisions.report')" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">รายงานระดับเขต</Link>
                    <Link :href="route('supervisions.settings')" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">ตั้งค่าการนิเทศ</Link>
                    <Link :href="route('supervisions.create')" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">บันทึก/วางแผน</Link>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-100"><p class="text-2xl font-bold text-gray-800">{{ stats.total ?? 0 }}</p><p class="text-xs text-gray-400">ทั้งหมด</p></div>
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-100"><p class="text-2xl font-bold text-gray-500">{{ stats.planned ?? 0 }}</p><p class="text-xs text-gray-400">รอนิเทศ</p></div>
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-amber-100"><p class="text-2xl font-bold text-amber-600">{{ stats.completed ?? 0 }}</p><p class="text-xs text-gray-400">นิเทศแล้ว/รอรับทราบ</p></div>
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-emerald-100"><p class="text-2xl font-bold text-emerald-600">{{ stats.acknowledged ?? 0 }}</p><p class="text-xs text-gray-400">รับทราบแล้ว</p></div>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">ทะเบียนการนิเทศ ({{ rows.length }})</div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3">วันที่นิเทศ</th><th class="px-4 py-3">โรงเรียน</th><th class="px-4 py-3">รอบ</th><th class="px-4 py-3">ประเด็น</th><th class="px-4 py-3">ผู้นิเทศ</th><th class="px-4 py-3">คุณภาพ</th><th class="px-4 py-3">สถานะ</th><th class="px-4 py-3 text-center">ดู</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="rows.length === 0"><td colspan="8" class="px-6 py-8"><EmptyState title="ยังไม่มีรายการนิเทศ" /></td></tr>
                            <tr v-for="r in rows" :key="r.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-500">{{ r.visit_date_thai }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ r.school }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ r.round ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ r.topic }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ r.supervisor ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ r.quality_avg ? r.quality_avg + '/5' : (r.rating ?? '—') }}</td>
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
