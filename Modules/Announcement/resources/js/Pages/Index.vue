<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    news: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

// การลบข่าว
const confirmingDeletion = ref(null);
const closeModal = () => (confirmingDeletion.value = null);
const deleteNews = () => {
    router.delete(route('announcements.destroy', confirmingDeletion.value.id), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
};

const fileUrl = (path) => `/storage/${path}`;
</script>

<template>
    <Head title="จัดการข่าวสาร" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    จัดการข่าวสาร
                </h2>
                <Link :href="route('announcements.create')">
                    <PrimaryButton>+ เพิ่มข่าวสาร</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div
                    v-if="flash.success"
                    class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
                >
                    {{ flash.success }}
                </div>

                <div
                    class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100"
                >
                    <EmptyState
                        v-if="news.data.length === 0"
                        title="ยังไม่มีข่าวสาร"
                        description="เริ่มเพิ่มข่าวแรกของคุณ"
                    />

                    <table v-else class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr
                                class="text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                            >
                                <th class="px-6 py-3">หัวข้อ</th>
                                <th class="px-6 py-3">ผู้สร้าง</th>
                                <th class="px-6 py-3">ไฟล์แนบ</th>
                                <th class="px-6 py-3">วันที่</th>
                                <th class="px-6 py-3 text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="item in news.data"
                                :key="item.id"
                                class="text-sm text-gray-700 hover:bg-gray-50"
                            >
                                <td class="px-6 py-4">
                                    <p class="font-medium text-gray-900">{{ item.title }}</p>
                                    <p class="mt-0.5 line-clamp-1 text-xs text-gray-400">
                                        {{ item.content }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">{{ item.creator ?? '-' }}</td>
                                <td class="px-6 py-4">
                                    <a
                                        v-if="item.file_path"
                                        :href="fileUrl(item.file_path)"
                                        target="_blank"
                                        class="text-indigo-600 hover:underline"
                                    >
                                        ดาวน์โหลด
                                    </a>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ item.created_at }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <Link
                                            :href="route('announcements.edit', item.id)"
                                            class="font-medium text-indigo-600 hover:text-indigo-800"
                                        >
                                            แก้ไข
                                        </Link>
                                        <button
                                            type="button"
                                            class="font-medium text-red-600 hover:text-red-800"
                                            @click="confirmingDeletion = item"
                                        >
                                            ลบ
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="news.links.length > 3" class="flex flex-wrap justify-center gap-1">
                    <component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, i) in news.links"
                        :key="i"
                        :href="link.url"
                        class="rounded-md px-3 py-2 text-sm"
                        :class="[
                            link.active
                                ? 'bg-indigo-600 text-white'
                                : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50',
                            !link.url && 'cursor-default opacity-50',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Modal ยืนยันการลบ -->
        <Modal :show="confirmingDeletion !== null" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    ยืนยันการลบข่าวสาร
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    คุณต้องการลบ "{{ confirmingDeletion?.title }}" ใช่หรือไม่?
                    การกระทำนี้ไม่สามารถย้อนกลับได้
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal">ยกเลิก</SecondaryButton>
                    <DangerButton @click="deleteNews">ลบข่าวสาร</DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
