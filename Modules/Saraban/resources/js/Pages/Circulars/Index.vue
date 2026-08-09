<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({ circulars: { type: Array, default: () => [] } });

const flash = computed(() => usePage().props.flash ?? {});
</script>

<template>
    <Head title="ส่งหนังสือราชการภายใน" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ส่งหนังสือราชการภายใน</h2>
                <Link :href="route('saraban.circulars.create')">
                    <PrimaryButton>+ ส่งหนังสือราชการภายใน</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ flash.success }}
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3">เรื่อง</th>
                                <th class="px-6 py-3">กลุ่มที่ส่ง</th>
                                <th class="px-6 py-3 text-center">ผู้รับ</th>
                                <th class="px-6 py-3 text-center">ไฟล์แนบ</th>
                                <th class="px-6 py-3">วันที่ส่ง</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="circulars.length === 0"><td colspan="5" class="px-6 py-12 text-center text-sm text-gray-400">ยังไม่มีหนังสือเวียน</td></tr>
                            <tr v-for="c in circulars" :key="c.id" class="text-sm text-gray-700 hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium text-gray-900">{{ c.title }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ c.sender_group ?? '—' }}</td>
                                <td class="px-6 py-3 text-center text-gray-500">{{ c.recipients }} คน</td>
                                <td class="px-6 py-3 text-center text-gray-500">{{ c.attachments }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ c.created_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
