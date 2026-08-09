<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    fiscalYear: { type: Number, default: 0 },
    stats: { type: Array, default: () => [] },
    leaveTypes: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});

// สีการ์ดต่อประเภท
const cardColor = (code) =>
    ({
        sick: 'from-rose-50 to-rose-100 ring-rose-200 text-rose-700',
        personal: 'from-sky-50 to-sky-100 ring-sky-200 text-sky-700',
        maternity: 'from-amber-50 to-amber-100 ring-amber-200 text-amber-700',
        vacation: 'from-emerald-50 to-emerald-100 ring-emerald-200 text-emerald-700',
        official: 'from-indigo-50 to-indigo-100 ring-indigo-200 text-indigo-700',
    })[code] ?? 'from-gray-50 to-gray-100 ring-gray-200 text-gray-700';
</script>

<template>
    <Head title="ระบบการลา" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">ระบบการลา</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-8 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ flash.success }}
                </div>

                <!-- แบบฟอร์มคำขอ -->
                <div>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-500">แบบฟอร์มคำขอ</h3>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="t in leaveTypes"
                            :key="t.id"
                            :href="route('leave.requests.create', { type: t.code })"
                            class="group flex items-center gap-3 rounded-2xl bg-gradient-to-br p-5 shadow-sm ring-1 transition hover:shadow-md"
                            :class="cardColor(t.code)"
                        >
                            <svg class="h-9 w-9 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                            <span class="font-semibold text-gray-800">เขียนขออนุญาต{{ t.name }}</span>
                        </Link>

                        <!-- ขอยกเลิกวันลา -->
                        <Link
                            :href="route('leave.requests.folder')"
                            class="group flex items-center gap-3 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 transition hover:shadow-md"
                        >
                            <svg class="h-9 w-9 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <span class="font-semibold text-gray-700">ขอยกเลิกวันลา</span>
                        </Link>
                    </div>
                </div>

                <!-- สถิติการลา -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-4 text-center">
                        <h3 class="font-semibold text-gray-800">สถิติการลาในปีงบประมาณ {{ fiscalYear }}</h3>
                    </div>
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3 text-left">ประเภทการลา</th>
                                <th class="px-6 py-3 text-center">ครั้ง</th>
                                <th class="px-6 py-3 text-center">วัน</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="s in stats" :key="s.code" class="text-sm text-gray-700">
                                <td class="px-6 py-3 font-medium text-gray-900">{{ s.name }}</td>
                                <td class="px-6 py-3 text-center">{{ s.times }}</td>
                                <td class="px-6 py-3 text-center">{{ s.days }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
