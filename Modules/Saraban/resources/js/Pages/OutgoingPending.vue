<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});
</script>

<template>
    <Head title="แฟ้มรอแนบไฟล์ส่ง" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">แฟ้มรอแนบไฟล์ส่ง</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ flash.success }}</div>

                <EmptyState v-if="rows.length === 0" title="ไม่มีหนังสือส่งที่รอแนบไฟล์" />

                <div v-else class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center gap-2 border-b border-gray-100 bg-indigo-50/60 px-6 py-3">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" /></svg>
                        <span class="font-semibold text-gray-800">แฟ้มรอแนบไฟล์ส่ง</span>
                        <span class="rounded-full bg-indigo-600 px-2 py-0.5 text-xs font-bold text-white">{{ rows.length }} เรื่อง</span>
                    </div>
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3 text-center">เลขทะเบียน</th>
                                <th class="px-6 py-3">เรื่อง</th>
                                <th class="px-6 py-3">ลงวันที่</th>
                                <th class="px-6 py-3 text-right">การปฏิบัติ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="r in rows" :key="r.id" class="text-sm text-gray-700 hover:bg-gray-50">
                                <td class="px-6 py-4 text-center font-mono font-semibold text-indigo-700">{{ r.document_number }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900">{{ r.title }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ r.date_thai }}</td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="route('saraban.outgoing.attach-form', r.id)" class="inline-flex items-center gap-1.5 font-medium text-indigo-600 hover:text-indigo-800">
                                        แนบไฟล์ →
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
