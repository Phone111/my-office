<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    stats: { type: Object, required: true },
    today: String,
    pendingList: { type: Array, default: () => [] },
});

const todayLabel = computed(() =>
    new Date(props.today).toLocaleDateString('th-TH', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }),
);

const checkedPct = computed(() =>
    props.stats.totalStaff ? Math.round((props.stats.checkedInToday / props.stats.totalStaff) * 100) : 0,
);

// งานที่รอผู้บริหารดำเนินการ
const actions = computed(() => [
    {
        label: 'เอกสารรออนุมัติ',
        count: props.stats.myPendingDocs,
        href: route('saraban.documents.inbox'),
        tone: 'indigo',
        icon: 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
    },
    {
        label: 'ใบลา / ไปราชการ รออนุมัติ',
        count: props.stats.myPendingLeave,
        href: route('leave.requests.inbox'),
        tone: 'amber',
        icon: 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    },
]);

const tone = (t) =>
    ({
        indigo: { bg: 'bg-indigo-50', text: 'text-indigo-600', badge: 'bg-indigo-600' },
        amber: { bg: 'bg-amber-50', text: 'text-amber-600', badge: 'bg-amber-500' },
    })[t];

// ทางลัด
const shortcuts = [
    { label: 'ตรวจสอบเอกสาร', routeName: 'saraban.documents.inbox', icon: 'M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z' },
    { label: 'ตรวจสอบวันลา', routeName: 'leave.requests.inbox', icon: 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' },
    { label: 'รายงานการลงเวลา', routeName: 'reports.attendance', icon: 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' },
    { label: 'สถิติการลา', routeName: 'reports.leave-statistics', icon: 'M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h12M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5' },
    { label: 'ปฏิทินผู้บริหาร', routeName: 'executive.calendar.index', icon: 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5' },
    { label: 'ทะเบียนหนังสือ', routeName: 'reports.documents', icon: 'M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z' },
];

// สรุปการลงเวลา (mini) — กดเจาะลึกไปหน้ารายงานได้
const attendanceStats = computed(() => [
    { label: 'ลงเวลาแล้ว', value: props.stats.checkedInToday, cls: 'text-emerald-600', href: route('reports.attendance') },
    { label: 'มาสาย', value: props.stats.lateToday, cls: 'text-amber-600', href: route('reports.attendance') },
    { label: 'ลาวันนี้', value: props.stats.onLeaveToday, cls: 'text-sky-600', href: route('reports.leave-statistics') },
    { label: 'ยังไม่ลงเวลา', value: props.stats.notCheckedIn, cls: 'text-rose-600', href: route('reports.attendance') },
]);
</script>

<template>
    <Head title="แดชบอร์ดผู้บริหาร" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">แดชบอร์ดผู้บริหาร</h2>
                <p class="mt-0.5 text-sm text-gray-500">{{ todayLabel }}</p>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                <!-- รอคุณดำเนินการ -->
                <section>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">รอคุณดำเนินการ</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <Link
                            v-for="a in actions"
                            :key="a.label"
                            :href="a.href"
                            class="group flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 transition hover:shadow-md"
                        >
                            <span :class="[tone(a.tone).bg, tone(a.tone).text]" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="a.icon" />
                                </svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-gray-500">{{ a.label }}</p>
                                <p class="text-2xl font-bold text-gray-900">{{ a.count }} <span class="text-sm font-medium text-gray-400">รายการ</span></p>
                            </div>
                            <span class="text-sm font-medium text-indigo-600 group-hover:underline">ดำเนินการ →</span>
                        </Link>
                    </div>

                    <!-- รายชื่อเอกสารที่รออนุมัติ (กดเข้าดูเอกสารได้) -->
                    <div v-if="pendingList.length" class="mt-4 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                        <div class="border-b border-gray-100 px-5 py-2.5 text-xs font-semibold uppercase tracking-wide text-gray-500">เอกสารที่รออนุมัติ</div>
                        <ul class="divide-y divide-gray-50">
                            <Link
                                v-for="item in pendingList"
                                :key="item.id"
                                :href="route('saraban.documents.show', item.id)"
                                class="flex items-center justify-between px-5 py-3 text-sm transition hover:bg-indigo-50/50"
                            >
                                <span class="truncate font-medium text-gray-800">{{ item.title }}</span>
                                <span class="shrink-0 pl-3 text-xs text-gray-400">ขั้นที่ {{ item.step_order }} ›</span>
                            </Link>
                        </ul>
                    </div>
                </section>

                <div class="grid grid-cols-1 gap-6">
                    <!-- การลงเวลาวันนี้ -->
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-gray-800">การลงเวลาวันนี้</h3>
                            <Link :href="route('reports.attendance')" class="text-sm font-medium text-indigo-600 hover:underline">ดูรายงาน →</Link>
                        </div>
                        <div class="mt-4 grid grid-cols-4 gap-2 text-center">
                            <Link
                                v-for="s in attendanceStats"
                                :key="s.label"
                                :href="s.href"
                                class="rounded-xl py-1 transition hover:bg-gray-50"
                            >
                                <p class="text-2xl font-bold" :class="s.cls">{{ s.value }}</p>
                                <p class="text-xs text-gray-400">{{ s.label }}</p>
                            </Link>
                        </div>
                        <div class="mt-5 h-3 w-full overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-emerald-500" :style="{ width: checkedPct + '%' }" />
                        </div>
                        <p class="mt-2 text-xs text-gray-400">ลงเวลาแล้ว {{ stats.checkedInToday }} / {{ stats.totalStaff }} คน ({{ checkedPct }}%)</p>
                    </div>
                </div>

                <!-- ทางลัด -->
                <section>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">ทางลัด</h3>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                        <Link
                            v-for="s in shortcuts"
                            :key="s.routeName"
                            :href="route(s.routeName)"
                            class="group flex flex-col items-center gap-2 rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-100 transition hover:shadow-md hover:ring-indigo-200"
                        >
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gray-50 text-gray-600 transition group-hover:bg-indigo-50 group-hover:text-indigo-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="s.icon" />
                                </svg>
                            </span>
                            <span class="text-xs font-medium text-gray-700">{{ s.label }}</span>
                        </Link>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
