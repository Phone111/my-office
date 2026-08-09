<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { hubCategories, itemHasAccess } from '@/hubCatalog';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    pendingLeaves: { type: Number, default: 0 },
    pendingBookings: { type: Number, default: 0 },
    unreadNotifications: { type: Number, default: 0 },
    today: String,
    attendanceToday: { type: Object, default: null },
    pendingApprovals: { type: Number, default: 0 },
    myPendingDocs: { type: Number, default: 0 },
    recentNews: { type: Array, default: () => [] },
    dutyExecutives: { type: Array, default: () => [] },
    recentActivities: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const roles = computed(() => page.props.auth.roles ?? []);

const userName = computed(() => user.value?.name ?? '-');
const totalPending = computed(() =>
    props.pendingApprovals +
    props.myPendingDocs +
    props.pendingLeaves +
    props.pendingBookings +
    props.unreadNotifications,
);

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return 'สวัสดีตอนเช้า';
    if (h < 17) return 'สวัสดีตอนบ่าย';
    return 'สวัสดีตอนเย็น';
});

const todayLabel = computed(() =>
    new Date(props.today).toLocaleDateString('th-TH', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }),
);

const hasCheckedIn = computed(() => props.attendanceToday !== null);
const checkInForm = useForm({});
const checkIn = () => checkInForm.post(route('attendance.store'), { preserveScroll: true });

const badgeVal = (item) => {
    if (!item.badgeKey) return 0;
    return item.badgeKey.split('.').reduce((o, k) => (o == null ? undefined : o[k]), page.props) ?? 0;
};

const accessibleCategories = computed(() =>
    hubCategories
        .map((cat) => ({
            ...cat,
            items: cat.items.filter((item) => itemHasAccess(item, roles.value)),
        }))
        .filter((cat) => cat.items.length > 0),
);

const quickLaunch = computed(() =>
    accessibleCategories.value
        .flatMap((cat) => cat.items.map((item) => ({ ...item, category: cat.heading, accent: cat.accent })))
        .slice(0, 8),
);

const statCards = computed(() => [
    {
        label: 'รออนุมัติเอกสาร',
        value: props.pendingApprovals,
        href: route('saraban.documents.inbox'),
        tone: 'amber',
    },
    {
        label: 'เอกสารของฉัน',
        value: props.myPendingDocs,
        href: route('saraban.documents.index'),
        tone: 'violet',
    },
    {
        label: 'คำขอลา',
        value: props.pendingLeaves,
        href: route('leave.requests.folder'),
        tone: 'orange',
    },
    {
        label: 'จองทรัพยากร',
        value: props.pendingBookings,
        href: route('booking.index'),
        tone: 'sky',
    },
]);

const tone = (name) =>
    ({
        amber: { bg: 'bg-amber-50', text: 'text-amber-600', bar: 'bg-amber-500' },
        violet: { bg: 'bg-violet-50', text: 'text-violet-600', bar: 'bg-violet-500' },
        orange: { bg: 'bg-orange-50', text: 'text-orange-600', bar: 'bg-orange-500' },
        sky: { bg: 'bg-sky-50', text: 'text-sky-600', bar: 'bg-sky-500' },
        rose: { bg: 'bg-rose-50', text: 'text-rose-600', bar: 'bg-rose-500' },
        emerald: { bg: 'bg-emerald-50', text: 'text-emerald-600', bar: 'bg-emerald-500' },
    })[name];

const accentClass = (name) =>
    ({
        indigo: 'bg-indigo-500',
        emerald: 'bg-emerald-500',
        amber: 'bg-amber-500',
        sky: 'bg-sky-500',
        rose: 'bg-rose-500',
        violet: 'bg-violet-500',
        teal: 'bg-teal-500',
        slate: 'bg-slate-500',
    })[name] ?? 'bg-indigo-500';

const activityRows = computed(() => props.recentActivities);
const latestNews = computed(() => props.recentNews.slice(0, 4));
</script>

<template>
    <Head title="แดชบอร์ด" />

    <AuthenticatedLayout>
        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                <section class="relative overflow-hidden rounded-[2rem] border border-indigo-500/10 bg-gradient-to-br from-slate-950 via-indigo-700 to-cyan-600 px-8 py-8 text-white shadow-xl">
                    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.18),transparent_34%),radial-gradient(circle_at_bottom_left,rgba(255,255,255,0.10),transparent_30%)]" />
                    <div class="relative grid gap-6 lg:grid-cols-[1.5fr_1fr] lg:items-end">
                        <div>
                            <p class="text-sm font-medium text-indigo-100">{{ todayLabel }}</p>
                            <h1 class="mt-1 text-2xl font-bold sm:text-3xl">
                                {{ greeting }}, {{ userName }}
                            </h1>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-indigo-100/95">
                                ภาพรวมงานวันนี้รวมทุกอย่างที่ต้องดูแลไว้ในที่เดียว ทั้งงานค้าง เอกสาร และทางลัดไปยังระบบหลัก
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-2">
                            <div class="rounded-2xl bg-white/10 p-4 backdrop-blur">
                                <p class="text-xs text-indigo-100">งานค้างทั้งหมด</p>
                                <p class="mt-2 text-3xl font-bold">{{ totalPending }}</p>
                            </div>
                            <div class="rounded-2xl bg-white/10 p-4 backdrop-blur">
                                <p class="text-xs text-indigo-100">สถานะวันนี้</p>
                                <p class="mt-2 text-lg font-bold">
                                    {{ hasCheckedIn ? 'ลงเวลาแล้ว' : 'ยังไม่ลงเวลา' }}
                                </p>
                            </div>
                            <div class="rounded-2xl bg-white/10 p-4 backdrop-blur">
                                <p class="text-xs text-indigo-100">ข่าวสารล่าสุด</p>
                                <p class="mt-2 text-3xl font-bold">{{ latestNews.length }}</p>
                            </div>
                            <div class="rounded-2xl bg-white/10 p-4 backdrop-blur">
                                <p class="text-xs text-indigo-100">ผู้บริหารปฏิบัติการ</p>
                                <p class="mt-2 text-3xl font-bold">{{ dutyExecutives.length }}</p>
                            </div>
                        </div>
                    </div>
                </section>
<section class="rounded-2xl border border-red-100 bg-white p-6 shadow-sm">
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold text-gray-800">
                🚨 งานที่ต้องดำเนินการ
            </h2>
            <p class="text-sm text-gray-500">
                งานที่ควรดำเนินการก่อนเป็นลำดับแรก
            </p>
        </div>

        <span
            class="rounded-full bg-red-100 px-3 py-1 text-sm font-semibold text-red-700"
        >
            {{ totalPending }} งาน
        </span>
    </div>

    <div class="space-y-3">

        <Link
            v-if="pendingApprovals > 0"
            :href="route('saraban.documents.inbox')"
            class="flex items-center justify-between rounded-xl border p-4 hover:bg-gray-50"
        >
            <div>
                <p class="font-semibold">📄 เอกสารรออนุมัติ</p>
                <p class="text-sm text-gray-500">
                    {{ pendingApprovals }} รายการ
                </p>
            </div>

            <span class="text-indigo-600 font-semibold">
                เปิด →
            </span>
        </Link>

        <Link
            v-if="pendingLeaves > 0"
            :href="route('leave.requests.folder')"
            class="flex items-center justify-between rounded-xl border p-4 hover:bg-gray-50"
        >
            <div>
                <p class="font-semibold">📝 คำขอลา</p>
                <p class="text-sm text-gray-500">
                    {{ pendingLeaves }} รายการ
                </p>
            </div>

            <span class="text-indigo-600 font-semibold">
                เปิด →
            </span>
        </Link>

        <Link
            v-if="pendingBookings > 0"
            :href="route('booking.index')"
            class="flex items-center justify-between rounded-xl border p-4 hover:bg-gray-50"
        >
            <div>
                <p class="font-semibold">🚗 การจองทรัพยากร</p>
                <p class="text-sm text-gray-500">
                    {{ pendingBookings }} รายการ
                </p>
            </div>

            <span class="text-indigo-600 font-semibold">
                เปิด →
            </span>
        </Link>

        <div
            v-if="totalPending === 0"
            class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-center"
        >
            ✅ วันนี้ไม่มีงานเร่งด่วน
        </div>

    </div>
</section>
                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Link
                        v-for="card in statCards"
                        :key="card.label"
                        :href="card.href"
                        class="group rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">{{ card.label }}</p>
                                <p class="mt-2 text-3xl font-bold text-gray-900">{{ card.value }}</p>
                            </div>
                            <span class="flex h-12 w-12 items-center justify-center rounded-xl" :class="[tone(card.tone).bg, tone(card.tone).text]">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        :d="card.tone === 'amber'
                                            ? 'M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859'
                                            : card.tone === 'violet'
                                                ? 'M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z'
                                                : card.tone === 'orange'
                                                    ? 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5'
                                                    : 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5'"
                                    />
                                </svg>
                            </span>
                        </div>
                    </Link>
                </section>

                <section class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        <div class="mb-5 flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-bold text-gray-800">ทางลัด</h2>
                                <p class="mt-1 text-sm text-gray-500">เมนูที่ใช้งานบ่อยที่สุด</p>
                            </div>
                            <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                                {{ quickLaunch.length }} รายการ
                            </span>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <Link
                                v-for="item in quickLaunch"
                                :key="item.routeName"
                                :href="route(item.routeName, item.routeParams ?? {})"
                                class="group rounded-2xl border border-gray-100 bg-gray-50 p-4 transition hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-white hover:shadow-md"
                            >
                                <div class="flex items-start gap-4">
                                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-white" :class="accentClass(item.accent)">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                                        </svg>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm text-gray-500">{{ item.category }}</p>
                                        <p class="mt-1 font-semibold text-gray-800">{{ item.label }}</p>
                                        <p v-if="badgeVal(item) > 0" class="mt-2 text-xs font-semibold text-rose-600">
                                            มี {{ badgeVal(item) }} รายการ
                                        </p>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        <div class="mb-5 flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-bold text-gray-800">สถานะวันนี้</h2>
                                <p class="mt-1 text-sm text-gray-500">เช็กอินและงานที่รออยู่ตอนนี้</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-2xl bg-gray-50 p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-500">การลงเวลา</p>
                                        <p class="mt-1 text-lg font-bold text-gray-900">
                                            {{ hasCheckedIn ? 'ลงเวลาแล้ว' : 'ยังไม่ลงเวลา' }}
                                        </p>
                                    </div>
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="hasCheckedIn ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                    >
                                        {{ hasCheckedIn ? 'สำเร็จ' : 'รอดำเนินการ' }}
                                    </span>
                                </div>
                                <div v-if="hasCheckedIn" class="mt-3 text-sm text-gray-600">
                                    เวลาเข้า {{ attendanceToday.check_in_time?.slice(0, 5) ?? '-' }} น.
                                </div>
                                <button
                                    v-else
                                    type="button"
                                    :disabled="checkInForm.processing"
                                    class="mt-3 w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                                    @click="checkIn"
                                >
                                    {{ checkInForm.processing ? 'กำลังบันทึก...' : 'ลงชื่อเข้างาน' }}
                                </button>
                            </div>

                            <div class="rounded-2xl bg-gray-50 p-4">
                                <p class="text-sm text-gray-500">งานค้างทั้งหมด</p>
                                <p class="mt-1 text-3xl font-bold text-gray-900">{{ totalPending }}</p>
                                <p class="mt-2 text-sm text-gray-600">
                                    รวมเอกสาร คำขอ และการแจ้งเตือนที่ยังต้องจัดการ
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section v-if="dutyExecutives.length" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"
                                />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-lg font-bold text-gray-800">ผู้บริหารปฏิบัติราชการ</h2>
                            <p class="text-sm text-gray-500">รายชื่อผู้บริหารที่กำลังปฏิบัติงานอยู่</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="e in dutyExecutives"
                            :key="e.id"
                            class="flex items-center gap-3 rounded-xl border p-3"
                            :class="e.is_director ? 'border-indigo-100 bg-indigo-50/40' : 'border-gray-100 bg-gray-50/60'"
                        >
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                                :class="e.is_director ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-200 text-gray-600'"
                            >
                                {{ (e.name ?? '?').charAt(0) }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-base font-bold text-gray-800">{{ e.name }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ e.position ?? '—' }}</p>
                            </div>
                            <span
                                class="ml-auto shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="e.is_director ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-700'"
                            >
                                {{ e.duty_label }}
                            </span>
                        </div>
                    </div>
                </section>

                <section>
                    <div class="mb-6 flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">ศูนย์รวมงานของฉัน</h2>
                            <p class="mt-1 text-sm text-gray-500">เมนูที่คุณสามารถใช้งานได้ตามสิทธิ์ของคุณ</p>
                        </div>
                        <span class="rounded-full bg-indigo-100 px-4 py-2 text-sm font-semibold text-indigo-700">
                            {{ accessibleCategories.length }} ระบบ
                        </span>
                    </div>

                    <div class="space-y-6">
                        <div v-for="cat in accessibleCategories" :key="cat.slug">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="h-8 w-1 rounded-full bg-indigo-600" />
                                <h3 class="text-xl font-bold text-gray-800">{{ cat.heading }}</h3>
                                <span class="rounded-full bg-indigo-100 px-2 py-1 text-xs font-semibold text-indigo-700">
                                    {{ cat.items.length }} เมนู
                                </span>
                            </div>

                            <div
                                class="grid gap-5"
                                :class="
                                    cat.items.length === 1
                                        ? 'grid-cols-1'
                                        : cat.items.length === 2
                                            ? 'grid-cols-1 md:grid-cols-2'
                                            : 'grid-cols-1 md:grid-cols-2 xl:grid-cols-3'
                                "
                            >
                                <Link
                                    v-for="item in cat.items"
                                    :key="item.label"
                                    :href="route(item.routeName, item.routeParams ?? {})"
                                    class="group flex h-full flex-col justify-between overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-indigo-300 hover:shadow-lg"
                                >
                                    <div class="flex min-h-[126px] items-center justify-between border-b border-gray-100 p-5">
                                        <div class="flex flex-1 items-center gap-4">
                                            <span
                                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-white"
                                                :class="accentClass(cat.accent)"
                                            >
                                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                                                </svg>
                                            </span>

                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center justify-between gap-2">
                                                    <p class="font-semibold text-gray-800">{{ item.label }}</p>
                                                    <span
                                                        v-if="badgeVal(item) > 0"
                                                        class="inline-flex min-w-6 items-center justify-center rounded-full bg-rose-500 px-2 py-0.5 text-xs font-bold text-white"
                                                    >
                                                        {{ badgeVal(item) > 99 ? '99+' : badgeVal(item) }}
                                                    </span>
                                                </div>

                                                <p v-if="badgeVal(item) > 0" class="mt-2 text-xs font-semibold text-rose-600">
                                                    มี {{ badgeVal(item) }} รายการ
                                                </p>
                                            </div>
                                        </div>

                                        <svg
                                            class="ml-3 h-5 w-5 shrink-0 text-gray-300 transition group-hover:translate-x-1 group-hover:text-indigo-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>

                                    <div class="flex items-center justify-between border-t bg-gray-50 px-5 py-3">
                                        <span class="text-xs text-gray-500">ไปยังเมนู →</span>
                                        <span class="rounded-full bg-indigo-100 px-2 py-1 text-xs font-semibold text-indigo-700">เปิด</span>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="grid gap-6 lg:grid-cols-[1fr_1fr]">
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="flex items-center gap-2 text-lg font-bold text-gray-800">
                                <span>📢</span>
                                ข่าวสารล่าสุด
                            </h2>
                            <Link :href="route('news.feed')" class="rounded-lg bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-100">
                                ดูทั้งหมด →
                            </Link>
                        </div>

                        <EmptyState v-if="latestNews.length === 0" title="ยังไม่มีข่าวสาร" />

                        <ul v-else class="space-y-3">
                            <li
                                v-for="n in latestNews"
                                :key="n.id"
                                class="rounded-xl border border-gray-100 p-4 transition hover:border-indigo-200 hover:bg-indigo-50"
                            >
                                <p class="font-semibold text-gray-800">{{ n.title }}</p>
                                <p class="mt-1 text-sm text-gray-500">โดย {{ n.creator ?? '—' }}</p>
                                <p class="mt-2 text-xs text-gray-400">{{ n.created_at }}</p>
                            </li>
                        </ul>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="flex items-center gap-2 text-lg font-bold text-gray-800">
                                <span>🕒</span>
                                กิจกรรมล่าสุด
                            </h2>
                            <span class="text-sm text-gray-500">วันนี้</span>
                        </div>

                        <div v-if="activityRows.length" class="space-y-4">
                            <div v-for="activity in activityRows" :key="activity.time + activity.title" class="flex items-center gap-4">
                                <div class="h-3 w-3 rounded-full" :class="activity.color" />
                                <span class="w-16 text-sm font-semibold text-gray-500">{{ activity.time }}</span>
                                <span class="text-sm text-gray-800">{{ activity.title }}</span>
                            </div>
                        </div>

                        <div v-else class="rounded-xl border border-dashed border-gray-200 py-8 text-center text-gray-400">
                            ยังไม่มีกิจกรรมวันนี้
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
