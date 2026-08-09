<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Modal from '@/Components/Modal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { thaiDate } from '@/utils/format';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    received: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});

const detail = ref(null);
const openDetail = (d) => {
    detail.value = d;
    if (!d.is_read) {
        router.post(route('saraban.personal-documents.read', d.id), {}, { preserveScroll: true, preserveState: false });
    }
};

const fileIt = (d) => router.post(route('saraban.personal-documents.file', d.id), {}, { preserveScroll: true });
</script>

<template>
    <Head title="แฟ้มรับเอกสารทั่วไป" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">แฟ้มรับเอกสารทั่วไป</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ flash.success }}
                </div>

                <EmptyState v-if="received.length === 0" title="ยังไม่มีเอกสารที่ได้รับ" description="เอกสารที่ส่งถึงคุณจะแสดงที่นี่" />

                <div v-else class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center gap-2 border-b border-gray-100 bg-indigo-50/60 px-6 py-3">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                        <span class="font-semibold text-gray-800">แฟ้มรับเอกสารทั่วไป</span>
                        <span class="rounded-full bg-indigo-600 px-2 py-0.5 text-xs font-bold text-white">{{ received.length }} เรื่อง</span>
                    </div>
                    <table class="min-w-full divide-y divide-gray-100 text-[15px]">
                        <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-3 text-left">เรื่อง</th>
                                <th class="px-6 py-3 text-center">วันที่ส่ง</th>
                                <th class="px-6 py-3 text-left">เจ้าของเรื่อง</th>
                                <th class="px-6 py-3 text-center">ดูเอกสาร</th>
                                <th class="px-6 py-3 text-center">ลงทะเบียน</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="d in received" :key="d.id" class="text-gray-700 hover:bg-gray-50" :class="!d.is_read ? 'bg-amber-50/40' : ''">
                                <td class="px-6 py-3 font-medium text-gray-900">
                                    {{ d.title }}
                                    <span v-if="!d.is_read" class="ml-2 inline-flex rounded bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold text-white">NEW</span>
                                    <span v-if="d.attachments.length" class="ml-1 text-gray-400">{{ d.attachments.length }}</span>
                                </td>
                                <td class="px-6 py-3 text-center text-gray-500">{{ thaiDate(d.created_at) }}</td>
                                <td class="px-6 py-3 text-gray-600">{{ d.sender }}</td>
                                <td class="px-6 py-3 text-center">
                                    <button class="inline-flex text-indigo-600 hover:text-indigo-800" title="ดู" @click="openDetail(d)">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                    </button>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <button class="text-sm font-medium text-indigo-600 hover:text-indigo-800" @click="fileIt(d)">จัดเก็บ</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- รายละเอียดเอกสาร -->
        <Modal :show="detail !== null" @close="detail = null">
            <div v-if="detail" class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">{{ detail.title }}</h2>
                <p class="mt-1 text-sm text-gray-400">จาก {{ detail.sender }} · {{ thaiDate(detail.created_at) }}</p>
                <div v-if="detail.content" class="rich-content mt-4 text-sm leading-relaxed text-gray-700" v-html="detail.content" />
                <div v-if="detail.attachments.length" class="mt-4 space-y-1">
                    <a v-for="(a, i) in detail.attachments" :key="a.url ?? i" :href="a.url" target="_blank" class="flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ a.name }}</a>
                </div>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="detail = null">ปิด</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
