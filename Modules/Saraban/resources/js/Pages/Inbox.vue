<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { thaiDate } from '@/utils/format';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    routes: { type: Array, default: () => [] },
    title: { type: String, default: 'เอกสารรอดำเนินการ' },
    folder: { type: String, default: null },
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

// ชื่อแฟ้มในหน้ารอดำเนินการ (แยกตามชนิดเอกสาร) + ลำดับการแสดง
const FOLDERS = [
    { key: 'memo', label: 'แฟ้มบันทึกเสนอ' },
    { key: 'incoming', label: 'แฟ้มหนังสือราชการ' },
    { key: 'internal_in', label: 'แฟ้มหนังสือภายใน' },
    { key: 'internal_out', label: 'แฟ้มหนังสือภายใน' },
    { key: 'general_in', label: 'แฟ้มเอกสารทั่วไป' },
    { key: 'general_out', label: 'แฟ้มเอกสารทั่วไป' },
    { key: 'order', label: 'แฟ้มคำสั่ง' },
    { key: 'report', label: 'แฟ้มรายงานโครงการ' },
];

// จัดกลุ่ม route ตามชนิด → แสดงเป็นแฟ้ม (เฉพาะแฟ้มที่มีเรื่อง)
const groups = computed(() => {
    const seen = new Map();
    for (const f of FOLDERS) if (!seen.has(f.label)) seen.set(f.label, []);
    for (const r of props.routes) {
        const folder = FOLDERS.find((f) => f.key === r.category) ?? { label: 'แฟ้มอื่นๆ' };
        if (!seen.has(folder.label)) seen.set(folder.label, []);
        seen.get(folder.label).push(r);
    }
    return [...seen.entries()].filter(([, items]) => items.length > 0).map(([label, items]) => ({ label, items }));
});
</script>

<template>
    <Head :title="title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ title }}</h2>
                <Link
                    :href="route('saraban.documents.acted')"
                    class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200"
                >
                    เอกสารที่ดำเนินการแล้ว →
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ flash.success }}
                </div>

                <div v-if="groups.length === 0" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <EmptyState title="ไม่มีเอกสารรอการดำเนินการจากคุณ" />
                </div>

                <!-- แฟ้มแยกตามชนิด -->
                <div v-for="g in groups" :key="g.label" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center gap-2 border-b border-gray-100 bg-indigo-50/60 px-6 py-3">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" /></svg>
                        <span class="font-semibold text-gray-800">{{ g.label }}</span>
                        <span class="rounded-full bg-indigo-600 px-2 py-0.5 text-xs font-bold text-white">{{ g.items.length }} เรื่อง</span>
                    </div>
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3">เรื่อง</th>
                                <th class="px-6 py-3">จาก / ผู้ส่ง</th>
                                <th class="px-6 py-3">ขั้นที่</th>
                                <th class="px-6 py-3">วันที่</th>
                                <th class="px-6 py-3 text-right">การปฏิบัติ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="item in g.items" :key="item.route_id" class="text-sm text-gray-700 hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    <span v-if="item.is_urgent" class="mr-2 inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-700">ด่วน</span>
                                    {{ item.title }}
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ item.source_name ?? item.creator }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ item.step_order }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ thaiDate(item.created_at) }}</td>
                                <td class="px-6 py-4 text-right">
                                    <Link
                                        :href="route('saraban.documents.show', item.document_id)"
                                        class="font-medium text-indigo-600 hover:text-indigo-800"
                                    >
                                        ดำเนินการต่อ →
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
