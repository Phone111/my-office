<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    notificationPage: { type: Object, default: null },
    notifications: { type: Object, default: null },
});

const notificationPage = computed(() => props.notificationPage ?? props.notifications ?? { data: [] });

const markAllRead = () => router.post(route('notifications.read-all'), {}, { preserveScroll: true });
const purgeBroken = () => router.post(route('notifications.purge-broken'), {}, { preserveScroll: true });

const typeMeta = (type) =>
    ({
        success: 'bg-green-100 text-green-600',
        danger: 'bg-red-100 text-red-600',
        info: 'bg-indigo-100 text-indigo-600',
    })[type] ?? 'bg-gray-100 text-gray-500';
</script>

<template>
    <Head title="การแจ้งเตือน" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">การแจ้งเตือนทั้งหมด</h2>
                <button
                    type="button"
                    class="text-sm font-medium text-indigo-600 hover:underline"
                    @click="markAllRead"
                >
                    ทำเครื่องหมายว่าอ่านทั้งหมด
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <p class="text-sm text-gray-500">จัดการการแจ้งเตือนของคุณ</p>
                        <button
                            type="button"
                            class="text-sm font-medium text-rose-600 hover:underline"
                            @click="purgeBroken"
                        >
                            ล้างแจ้งเตือนลิงก์เสีย
                        </button>
                    </div>
                    <div v-if="notificationPage.data.length === 0" class="px-6 py-12 text-center text-sm text-gray-400">
                        ยังไม่มีการแจ้งเตือน
                    </div>
                    <ul v-else class="divide-y divide-gray-50">
                        <Link
                            v-for="n in notificationPage.data"
                            :key="n.id"
                            :href="route('notifications.open', n.id)"
                            class="flex items-start gap-4 px-6 py-4 transition hover:bg-gray-50"
                            :class="{ 'bg-indigo-50/40': !n.is_read }"
                        >
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full" :class="typeMeta(n.type)">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                </svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-gray-800">{{ n.title }}</p>
                                <p class="text-sm text-gray-500">{{ n.message }}</p>
                                <p class="mt-0.5 text-xs text-gray-400">{{ n.created_at }}</p>
                            </div>
                            <span v-if="!n.is_read" class="mt-1 h-2 w-2 shrink-0 rounded-full bg-indigo-500" />
                        </Link>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
