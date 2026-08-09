<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    mine: { type: Array, default: () => [] },
    given: { type: Array, default: () => [] },
    canEvaluate: { type: Boolean, default: false },
    canManage: { type: Boolean, default: false },
});
const flash = computed(() => usePage().props.flash?.success);
const gradeCls = (g) => ({ ดีเด่น: 'text-emerald-700', ดีมาก: 'text-emerald-600', ดี: 'text-indigo-600', พอใช้: 'text-amber-600', ต้องปรับปรุง: 'text-rose-600' }[g] ?? 'text-gray-600');
</script>

<template>
    <Head title="ประเมินผลปฏิบัติงาน" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ประเมินผลการปฏิบัติงาน</h2>
                <div class="flex gap-2">
                    <Link v-if="canManage" :href="route('evaluations.report')" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">รายงาน</Link>
                    <Link v-if="canManage" :href="route('evaluations.settings')" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">ตั้งค่า</Link>
                    <Link v-if="canEvaluate" :href="route('evaluations.create')" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">ประเมินบุคลากร</Link>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">ผลการประเมินของฉัน ({{ mine.length }})</div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50"><tr class="text-left text-xs font-semibold uppercase text-gray-500"><th class="px-4 py-3">รอบ</th><th class="px-4 py-3">ผู้ประเมิน</th><th class="px-4 py-3 text-center">ร้อยละ</th><th class="px-4 py-3">ระดับ</th><th class="px-4 py-3">สถานะ</th><th class="px-4 py-3 text-center">ดู</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="mine.length === 0"><td colspan="6" class="px-6 py-8"><EmptyState title="ยังไม่มีผลการประเมิน" /></td></tr>
                            <tr v-for="r in mine" :key="r.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-600">{{ r.round ?? '—' }}</td><td class="px-4 py-3 text-gray-600">{{ r.evaluator ?? '—' }}</td>
                                <td class="px-4 py-3 text-center font-semibold">{{ r.percent ?? '—' }}</td><td class="px-4 py-3 font-semibold" :class="gradeCls(r.grade)">{{ r.grade ?? '—' }}</td>
                                <td class="px-4 py-3"><StatusBadge :status="r.status" :label="r.status_label" /></td>
                                <td class="px-4 py-3 text-center"><Link :href="route('evaluations.show', r.id)" class="text-indigo-600 hover:underline">ดู</Link></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="canEvaluate" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">การประเมินที่ฉันทำ ({{ given.length }})</div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50"><tr class="text-left text-xs font-semibold uppercase text-gray-500"><th class="px-4 py-3">ผู้รับการประเมิน</th><th class="px-4 py-3">รอบ</th><th class="px-4 py-3 text-center">ร้อยละ</th><th class="px-4 py-3">ระดับ</th><th class="px-4 py-3">สถานะ</th><th class="px-4 py-3 text-center">ดู</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="given.length === 0"><td colspan="6" class="px-6 py-8"><EmptyState title="ยังไม่ได้ประเมินใคร" /></td></tr>
                            <tr v-for="r in given" :key="r.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ r.evaluee }}</td><td class="px-4 py-3 text-gray-600">{{ r.round ?? '—' }}</td>
                                <td class="px-4 py-3 text-center font-semibold">{{ r.percent ?? '—' }}</td><td class="px-4 py-3 font-semibold" :class="gradeCls(r.grade)">{{ r.grade ?? '—' }}</td>
                                <td class="px-4 py-3"><StatusBadge :status="r.status" :label="r.status_label" /></td>
                                <td class="px-4 py-3 text-center"><Link :href="route('evaluations.show', r.id)" class="text-indigo-600 hover:underline">ดู</Link></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
