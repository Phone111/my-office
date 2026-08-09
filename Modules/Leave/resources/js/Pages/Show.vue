<script setup>
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    leave: { type: Object, required: true },
});

const stepDot = (status) =>
    ({
        waiting: 'bg-gray-300',
        pending: 'bg-amber-400 ring-4 ring-amber-100',
        approved: 'bg-green-500',
        rejected: 'bg-red-500',
    })[status] ?? 'bg-gray-300';

const stepLabel = (status) =>
    ({ waiting: 'รอคิว', pending: 'กำลังพิจารณา', approved: 'อนุมัติแล้ว', rejected: 'ตีกลับ' })[status] ?? status;

const fileUrl = (path) => `/storage/${path}`;
</script>

<template>
    <Head title="รายละเอียดใบลา" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">รายละเอียดใบลา</h2>
                <Link :href="route('leave.requests.index')" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    &larr; กลับหน้าใบลา
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">{{ leave.type }}</h1>
                            <p class="mt-1 text-sm text-gray-400">โดย {{ leave.requester }}</p>
                        </div>
                        <StatusBadge class="shrink-0" :status="leave.status" />
                    </div>

                    <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-400">ช่วงวันที่</dt>
                            <dd class="font-medium text-gray-800">{{ leave.start_date }} – {{ leave.end_date }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-400">จำนวนวัน</dt>
                            <dd class="font-medium text-gray-800">{{ leave.total_days }} วัน</dd>
                        </div>
                    </dl>

                    <div class="mt-4">
                        <p class="text-sm text-gray-400">เหตุผล</p>
                        <p class="mt-1 whitespace-pre-line text-gray-700">{{ leave.reason }}</p>
                    </div>

                    <div v-if="leave.file_path" class="mt-4 border-t border-gray-100 pt-4">
                        <a :href="fileUrl(leave.file_path)" target="_blank" class="text-sm font-medium text-indigo-600 hover:underline">
                            ดาวน์โหลดเอกสารแนบ
                        </a>
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
                    <h3 class="mb-6 font-semibold text-gray-800">เส้นทางการอนุมัติ</h3>
                    <ol class="relative space-y-8 border-l border-gray-200 pl-6">
                        <li v-for="step in leave.routes" :key="step.step_order" class="relative">
                            <span class="absolute -left-[1.85rem] top-1 h-3.5 w-3.5 rounded-full" :class="stepDot(step.status)" />
                            <div class="flex items-center justify-between">
                                <p class="font-medium text-gray-900">ขั้นที่ {{ step.step_order }} · {{ step.approver }}</p>
                                <span class="text-xs text-gray-400">{{ step.acted_at ?? '' }}</span>
                            </div>
                            <p class="text-sm text-gray-500">{{ stepLabel(step.status) }}</p>
                            <p v-if="step.comment" class="mt-2 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-600">“{{ step.comment }}”</p>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
