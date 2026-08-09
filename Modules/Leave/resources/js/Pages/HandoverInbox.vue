<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({ rows: { type: Array, default: () => [] } });
const flash = computed(() => usePage().props.flash?.success);

const accept = (id) => {
    if (confirm('ยืนยันรับมอบงานในช่วงที่เพื่อนร่วมงานลา?')) {
        router.post(route('leave.handover.accept', id), {}, { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="รับมอบงาน" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">รับมอบงาน (ปฏิบัติหน้าที่แทน)</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="w-full text-sm">
                        <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs text-gray-500">
                            <tr>
                                <th class="px-5 py-3 font-medium">ผู้ลา</th>
                                <th class="px-5 py-3 font-medium">ประเภท</th>
                                <th class="px-5 py-3 font-medium">ช่วงวันลา</th>
                                <th class="px-5 py-3 font-medium">รวม</th>
                                <th class="px-5 py-3 font-medium">สถานะใบลา</th>
                                <th class="px-5 py-3 font-medium">รับมอบงาน</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="r in rows" :key="r.id" class="hover:bg-gray-50">
                                <td class="px-5 py-3 font-medium text-gray-800">{{ r.requester }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ r.type }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ r.period_thai }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ r.total_days }} วัน</td>
                                <td class="px-5 py-3"><StatusBadge :status="r.status" /></td>
                                <td class="px-5 py-3">
                                    <span v-if="r.accepted" class="text-sm font-medium text-emerald-600">✓ รับแล้ว {{ r.accepted_thai }}</span>
                                    <button v-else class="rounded-md bg-indigo-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500" @click="accept(r.id)">รับมอบงาน</button>
                                </td>
                            </tr>
                            <tr v-if="!rows.length"><td colspan="6" class="px-5 py-8 text-center text-gray-400">ยังไม่มีงานที่ถูกมอบให้ปฏิบัติแทน</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
