<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

// จัดกลุ่มตามคู่มือ AMSS การเงินฯ (ส่วนที่ 1–7)
const groups = [
    {
        title: 'ตั้งค่าระบบ',
        accent: 'slate',
        items: [{ label: 'ตั้งค่าระบบการเงิน', name: 'finance.settings.index' }],
    },
    {
        title: 'ทะเบียนรับ',
        accent: 'emerald',
        items: [
            { label: 'ทะเบียนเงินงวด (จัดสรรงบประมาณ)', name: 'finance.allocations.index' },
            { label: 'รับเงินงบประมาณ', name: 'finance.receipts.index', params: { class: 'budget' } },
            { label: 'รับเงินนอกงบประมาณ', name: 'finance.receipts.index', params: { class: 'nonbudget' } },
            { label: 'รับเงินรายได้แผ่นดิน', name: 'finance.receipts.index', params: { class: 'state_revenue' } },
        ],
    },
    {
        title: 'ทะเบียนขอเบิก',
        accent: 'indigo',
        items: [
            { label: 'ขอเบิก/ขอยืมโครงการ', name: 'finance.withdrawals.index' },
            { label: 'คืนเงินโครงการ', name: 'finance.project-returns.index' },
            { label: 'ขอเบิกเงินคงคลัง (ฎีกา)', name: 'finance.petitions.index', params: { type: 'treasury' } },
            { label: 'คืนเงินคงคลัง', name: 'finance.treasury-returns.index' },
            { label: 'ยกเลิกฎีกา', name: 'finance.petition-cancels.index' },
            { label: 'เงินกันเหลื่อมปี', name: 'finance.petitions.index', params: { type: 'carryover' } },
        ],
    },
    {
        title: 'ทะเบียนจ่าย',
        accent: 'rose',
        items: [
            { label: 'สั่งจ่ายเงินงบประมาณ', name: 'finance.payments.index', params: { class: 'budget' } },
            { label: 'สั่งจ่ายเงินนอกงบประมาณ', name: 'finance.payments.index', params: { class: 'nonbudget' } },
            { label: 'สั่งจ่ายเงินรายได้แผ่นดิน', name: 'finance.payments.index', params: { class: 'state_revenue' } },
            { label: 'เงินทดรองราชการ', name: 'finance.payments.index', params: { class: 'advance' } },
            { label: 'อนุมัติจ่ายเงิน', name: 'finance.payments.approvals' },
            { label: 'จ่ายเงิน', name: 'finance.payments.payouts' },
        ],
    },
    {
        title: 'เปลี่ยนแปลงสถานะเงิน',
        accent: 'amber',
        items: [
            { label: 'เงินงบประมาณ', name: 'finance.status-changes.index', params: { class: 'budget' } },
            { label: 'เงินนอกงบประมาณ', name: 'finance.status-changes.index', params: { class: 'nonbudget' } },
            { label: 'เงินรายได้แผ่นดิน', name: 'finance.status-changes.index', params: { class: 'state_revenue' } },
        ],
    },
    {
        title: 'ตรวจสอบ & รายงาน',
        accent: 'sky',
        items: [
            { label: 'ตรวจสอบ (9 รายการ)', name: 'finance.audit.index' },
            { label: 'รายงานการเงิน (11 รายงาน)', name: 'finance.reports.index' },
        ],
    },
];

const accentClass = {
    slate: 'ring-slate-200 [&_h3]:text-slate-700 [&_.dot]:bg-slate-400',
    emerald: 'ring-emerald-100 [&_h3]:text-emerald-700 [&_.dot]:bg-emerald-400',
    indigo: 'ring-indigo-100 [&_h3]:text-indigo-700 [&_.dot]:bg-indigo-400',
    rose: 'ring-rose-100 [&_h3]:text-rose-700 [&_.dot]:bg-rose-400',
    amber: 'ring-amber-100 [&_h3]:text-amber-700 [&_.dot]:bg-amber-400',
    sky: 'ring-sky-100 [&_h3]:text-sky-700 [&_.dot]:bg-sky-400',
};
</script>

<template>
    <Head title="การเงินและบัญชี" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">การเงินและบัญชี</h2>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <section v-for="g in groups" :key="g.title" :class="['rounded-2xl bg-white p-5 shadow-sm ring-1', accentClass[g.accent]]">
                        <h3 class="mb-3 text-sm font-semibold">{{ g.title }}</h3>
                        <ul class="space-y-1">
                            <li v-for="it in g.items" :key="it.label">
                                <Link :href="route(it.name, it.params)" class="group flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-gray-600 transition hover:bg-gray-50 hover:text-gray-900">
                                    <span class="dot h-1.5 w-1.5 rounded-full"></span>
                                    <span class="flex-1">{{ it.label }}</span>
                                    <span class="text-gray-300 transition group-hover:text-gray-400">→</span>
                                </Link>
                            </li>
                        </ul>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
