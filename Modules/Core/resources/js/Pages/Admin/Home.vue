<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
});

const statOf = (key) => props.stats?.[key] ?? 0;

// แถบสรุปตัวเลขหลัก (KPI)
const kpis = [
    { key: 'personnel', label: 'บุคลากรทั้งหมด', unit: 'คน', color: 'indigo', routeName: 'admin.personnel.index', icon: 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z' },
    { key: 'admins', label: 'ผู้ดูแลระบบ', unit: 'คน', color: 'sky', routeName: 'admin.admins.index', icon: 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z' },
    { key: 'news', label: 'ข่าวประชาสัมพันธ์', unit: 'ข่าว', color: 'amber', routeName: 'admin.news.index', icon: 'M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z' },
    { key: 'signatures', label: 'ลายเซ็นบุคลากร', unit: 'รายการ', color: 'emerald', routeName: 'admin.signatures.index', icon: 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10' },
];

// หมวดระบบจัดการ (ทางลัด)
const sections = [
    {
        heading: 'ผู้ใช้ & สิทธิ์',
        color: 'indigo',
        items: [
            { label: 'จัดการผู้ดูแลระบบ', desc: 'เพิ่ม/ลบผู้ดูแลระบบ', routeName: 'admin.admins.index', statKey: 'admins', unit: 'คน', icon: 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z' },
            { label: 'จัดการบุคลากร', desc: 'ข้อมูลครู/เจ้าหน้าที่ทั้งหมด', routeName: 'admin.personnel.index', statKey: 'personnel', unit: 'คน', icon: 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z' },
            { label: 'ลายเซ็นบุคลากร', desc: 'อัปโหลด/แก้ไขลายเซ็น', routeName: 'admin.signatures.index', statKey: 'signatures', unit: 'รายการ', icon: 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10' },
        ],
    },
    {
        heading: 'โครงสร้างองค์กร',
        color: 'emerald',
        items: [
            { label: 'จัดการกลุ่ม', desc: 'กลุ่มบริหาร/กลุ่มงาน', routeName: 'admin.groups.index', statKey: 'groups', unit: 'กลุ่ม', icon: 'M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z' },
            { label: 'จัดการกลุ่มสาระ', desc: 'กลุ่มสาระการเรียนรู้', routeName: 'admin.departments.index', statKey: 'departments', unit: 'กลุ่ม', icon: 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25' },
            { label: 'จัดการตำแหน่ง', desc: 'ประเภทตำแหน่งบุคลากร', routeName: 'admin.positions.index', statKey: 'positions', unit: 'ตำแหน่ง', icon: 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z' },
        ],
    },
    {
        heading: 'เนื้อหา & ทรัพยากร',
        color: 'amber',
        items: [
            { label: 'จัดการข่าว', desc: 'ข่าวประชาสัมพันธ์', routeName: 'admin.news.index', statKey: 'news', unit: 'ข่าว', icon: 'M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z' },
            { label: 'จัดการรถยนต์', desc: 'ทะเบียนรถของหน่วยงาน', routeName: 'booking.vehicles.index', statKey: 'vehicles', unit: 'คัน', icon: 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-6m0 0v-6.225M16.5 18.75V6.75a1.5 1.5 0 0 0-1.5-1.5h-7.5a1.5 1.5 0 0 0-1.5 1.5v7.5m0 0h6.225' },
            { label: 'จัดการห้องประชุม', desc: 'ห้องประชุม/สถานที่จอง', routeName: 'booking.meeting-rooms.index', statKey: 'meeting_rooms', unit: 'ห้อง', icon: 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z' },
        ],
    },
];

// รวมการ์ดทุกหมวดเป็นลิสต์เดียว (ไม่แสดงหัวข้อประเภท)
const cards = sections.flatMap((s) => s.items.map((i) => ({ ...i, color: s.color })));

const tone = (color) =>
    ({
        indigo: { icon: 'bg-indigo-50 text-indigo-600', ring: 'group-hover:ring-indigo-200', dot: 'bg-indigo-500' },
        emerald: { icon: 'bg-emerald-50 text-emerald-600', ring: 'group-hover:ring-emerald-200', dot: 'bg-emerald-500' },
        amber: { icon: 'bg-amber-50 text-amber-600', ring: 'group-hover:ring-amber-200', dot: 'bg-amber-500' },
        sky: { icon: 'bg-sky-50 text-sky-600', ring: 'group-hover:ring-sky-200', dot: 'bg-sky-500' },
    })[color] ?? { icon: 'bg-gray-50 text-gray-600', ring: '', dot: 'bg-gray-400' };
</script>

<template>
    <Head title="หน้าหลักผู้ดูแลระบบ" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">หน้าหลักผู้ดูแลระบบ</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                <!-- แถบสรุปตัวเลข (KPI) -->
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <Link
                        v-for="k in kpis"
                        :key="k.key"
                        :href="route(k.routeName)"
                        class="group rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 transition hover:shadow-md"
                        :class="tone(k.color).ring"
                    >
                        <div class="flex items-start justify-between">
                            <span class="text-sm text-gray-500">{{ k.label }}</span>
                            <span :class="tone(k.color).icon" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="k.icon" />
                                </svg>
                            </span>
                        </div>
                        <p class="mt-3 text-3xl font-bold text-gray-900">{{ statOf(k.key) }}</p>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-400">{{ k.unit }}</span>
                            <span class="flex items-center gap-0.5 text-xs font-medium text-gray-300 transition group-hover:text-indigo-500">
                                จัดการ
                                <svg class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                            </span>
                        </div>
                    </Link>
                </div>

                <!-- ทางลัดสู่ระบบจัดการ (กริดเดียว ไม่แบ่งประเภท) -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="item in cards"
                        :key="item.routeName"
                        :href="route(item.routeName)"
                        class="group flex flex-col rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 transition hover:shadow-md"
                        :class="tone(item.color).ring"
                    >
                        <div class="flex items-start gap-4">
                            <span :class="tone(item.color).icon" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                                </svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900">{{ item.label }}</p>
                                <p class="truncate text-xs text-gray-400">{{ item.desc }}</p>
                            </div>
                            <svg class="h-5 w-5 shrink-0 text-gray-300 transition group-hover:translate-x-0.5 group-hover:text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                        </div>
                        <div class="mt-4 border-t border-gray-50 pt-3 text-xs text-gray-400">
                            <span class="font-semibold text-gray-700">{{ statOf(item.statKey) }}</span> {{ item.unit }}
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
