<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { thaiDate } from '@/utils/format';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    rows: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});
</script>

<template>
    <Head title="เอกสารถูกตีกลับ" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">เอกสารถูกตีกลับ (รอแก้ไข & เสนอใหม่)</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ flash.success }}</div>

                <div v-if="rows.length === 0" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <EmptyState title="ไม่มีเอกสารที่ถูกตีกลับ" />
                </div>

                <div v-else class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center gap-2 border-b border-gray-100 bg-rose-50/70 px-6 py-3">
                        <svg class="h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" /></svg>
                        <span class="font-semibold text-gray-800">เอกสารถูกตีกลับ</span>
                        <span class="rounded-full bg-rose-600 px-2 py-0.5 text-xs font-bold text-white">{{ rows.length }} เรื่อง</span>
                    </div>
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3">ทะเบียน</th>
                                <th class="px-6 py-3">เรื่อง</th>
                                <th class="px-6 py-3">เหตุผลที่ตีกลับ</th>
                                <th class="px-6 py-3">วันที่</th>
                                <th class="px-6 py-3 text-right">การปฏิบัติ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="d in rows" :key="d.id" class="text-sm text-gray-700 hover:bg-rose-50/30">
                                <td class="px-6 py-4 font-mono text-indigo-700">{{ d.document_number ?? '—' }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900">{{ d.title }}<div class="text-xs font-normal text-gray-400">{{ d.category_label }}</div></td>
                                <td class="px-6 py-4 text-rose-600">{{ d.reject_comment ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ thaiDate(d.created_at) }}</td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="route('saraban.documents.edit', d.id)" class="inline-flex items-center gap-1 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">แก้ไข &amp; เสนอใหม่</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
