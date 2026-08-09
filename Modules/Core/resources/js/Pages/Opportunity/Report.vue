<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    byAge: { type: Array, default: () => [] },
    byReason: { type: Array, default: () => [] },
    overall: { type: Object, default: () => ({}) },
});
</script>

<template>
    <Head title="รายงานสิทธิและโอกาส" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">รายงานสิทธิและโอกาสทางการศึกษา</h2>
                <Link :href="route('opportunity.index')" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">← กลับ</Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-100"><p class="text-2xl font-bold text-gray-800">{{ overall.total }}</p><p class="text-xs text-gray-400">ทั้งหมด</p></div>
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-emerald-100"><p class="text-2xl font-bold text-emerald-600">{{ overall.enrolled }}</p><p class="text-xs text-gray-400">เข้าเรียน</p></div>
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-rose-100"><p class="text-2xl font-bold text-rose-600">{{ overall.not }}</p><p class="text-xs text-gray-400">ยังไม่เข้าเรียน</p></div>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">แยกตามช่วงอายุ</div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50"><tr class="text-left text-xs font-semibold uppercase text-gray-500"><th class="px-4 py-3">ช่วงอายุ</th><th class="px-4 py-3 text-center">ทั้งหมด</th><th class="px-4 py-3 text-center">เข้าเรียน</th><th class="px-4 py-3 text-center">ยังไม่เข้าเรียน</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(a, i) in byAge" :key="i" class="text-gray-700">
                                <td class="px-4 py-3">{{ a.group }}</td><td class="px-4 py-3 text-center">{{ a.total }}</td><td class="px-4 py-3 text-center text-emerald-600">{{ a.enrolled }}</td><td class="px-4 py-3 text-center text-rose-600">{{ a.not }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">สาเหตุที่ยังไม่เข้าเรียน</div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="byReason.length === 0"><td colspan="2"><EmptyState title="ไม่มีข้อมูล" /></td></tr>
                            <tr v-for="(r, i) in byReason" :key="i" class="text-gray-700"><td class="px-4 py-3">{{ r.reason }}</td><td class="px-4 py-3 text-right font-semibold text-rose-600">{{ r.count }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
