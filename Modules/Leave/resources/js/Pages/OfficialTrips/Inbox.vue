<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    requests: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});
</script>

<template>
    <Head title="ตรวจการไปราชการ" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">ตรวจการไปราชการ</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ flash.success }}</div>

                <div v-if="requests.length === 0" class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <EmptyState title="ไม่มีคำขอไปราชการรอการดำเนินการจากคุณ" />
                </div>

                <!-- แฟ้มตรวจการไปราชการ -->
                <div v-else class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center gap-2 border-b border-gray-100 bg-indigo-50/60 px-6 py-3">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" /></svg>
                        <span class="font-semibold text-gray-800">แฟ้มตรวจการไปราชการ</span>
                        <span class="rounded-full bg-indigo-600 px-2 py-0.5 text-xs font-bold text-white">{{ requests.length }} เรื่อง</span>
                    </div>
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3">เรื่อง</th>
                                <th class="px-6 py-3">จาก / ผู้ขอ</th>
                                <th class="px-6 py-3">สถานที่ไป</th>
                                <th class="px-6 py-3">วันที่</th>
                                <th class="px-6 py-3 text-right">การปฏิบัติ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="r in requests" :key="r.id" class="text-sm text-gray-700 hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ r.title }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ r.sender }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ r.destination }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ r.sent_thai }}</td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="route('official-trips.show', r.id)" class="font-medium text-indigo-600 hover:text-indigo-800">ดำเนินการต่อ →</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
