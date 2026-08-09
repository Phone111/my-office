<script setup>
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    person: { type: Object, default: () => ({}) },
    leaves: { type: Array, default: () => [] },
});

const printPage = () => window.print();
</script>

<template>
    <Head :title="`ใบลา · ${person.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Link :href="route('leave.registry.index')" class="no-print rounded-md p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" title="กลับทะเบียนลา">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    </Link>
                    <div>
                        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ person.name }}</h2>
                        <p class="text-xs text-gray-400">{{ person.position ?? '—' }}<span v-if="person.affiliation"> · {{ person.affiliation }}</span></p>
                    </div>
                </div>
                <button type="button" class="no-print inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-500" @click="printPage">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" /></svg>
                    พิมพ์
                </button>
            </div>
        </template>

        <div class="py-10">
            <div id="leave-person-print" class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">
                        ใบลาของ {{ person.name }} ({{ leaves.length }} ใบ)
                    </div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3 text-center">ที่</th>
                                <th class="px-4 py-3">เรื่อง</th>
                                <th class="px-4 py-3">ว/ด/ป ที่ลา</th>
                                <th class="px-4 py-3 text-center">จำนวนวัน</th>
                                <th class="px-4 py-3 text-center">สถานะ</th>
                                <th class="px-4 py-3 text-center">ดูรายละเอียด</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="leaves.length === 0"><td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400">ยังไม่มีใบลา</td></tr>
                            <tr v-for="(l, i) in leaves" :key="l.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 text-center text-gray-400">{{ i + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ l.subject }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ l.date_range }}</td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ l.days }}</td>
                                <td class="px-4 py-3 text-center">
                                    <StatusBadge :status="l.status" />
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Link :href="route('leave.requests.proposal', { leaveRequest: l.id, from: 'registry' })" class="inline-flex text-indigo-600 hover:text-indigo-800" title="ดูแบบฟอร์มใบลา">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@media print {
    body * { visibility: hidden; }
    #leave-person-print, #leave-person-print * { visibility: visible; }
    #leave-person-print { position: absolute; left: 0; top: 0; width: 100%; }
    .no-print { display: none !important; }
}
</style>
