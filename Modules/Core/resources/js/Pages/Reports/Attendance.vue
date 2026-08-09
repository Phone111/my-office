<script setup>
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    date: { type: String, default: '' },
    present: { type: Array, default: () => [] },
    absent: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
});

const selectedDate = ref(props.date);
const reload = () => {
    router.get(route('reports.attendance'), { date: selectedDate.value }, { preserveScroll: true, preserveState: true });
};

const cards = [
    { key: 'total', label: 'บุคลากรทั้งหมด', cls: 'text-gray-900' },
    { key: 'present', label: 'ลงเวลาแล้ว', cls: 'text-emerald-600' },
    { key: 'late', label: 'มาสาย', cls: 'text-amber-600' },
    { key: 'absent', label: 'ยังไม่ลงเวลา', cls: 'text-rose-600' },
];

// ค้นหาชื่อ (กรองทั้งสองตาราง)
const search = ref('');
const matchName = (x) => {
    const q = search.value.trim().toLowerCase();
    if (!q) return true;
    return (x.name ?? '').toLowerCase().includes(q) || (x.position ?? '').toLowerCase().includes(q);
};
const filteredPresent = computed(() => props.present.filter(matchName));
const filteredAbsent = computed(() => props.absent.filter(matchName));

// % มาทำงานวันนี้
const checkedPct = computed(() => {
    const total = props.summary.total ?? 0;
    const done = (props.summary.present ?? 0) + (props.summary.late ?? 0);
    return total ? Math.round((done / total) * 100) : 0;
});

const fmtTime = (t) => (t ? t.slice(0, 5) : '—');
</script>

<template>
    <Head title="รายงานการลงเวลา" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">รายงานการลงเวลา</h2>
                <div class="flex flex-wrap items-center gap-2">
                    <TextInput v-model="search" type="text" placeholder="ค้นหาชื่อ / ตำแหน่ง" class="w-56 text-sm" />
                    <input v-model="selectedDate" type="date" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="reload" />
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- สรุป -->
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <div v-for="c in cards" :key="c.key" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                        <p class="text-sm text-gray-500">{{ c.label }}</p>
                        <p class="mt-2 text-3xl font-bold" :class="c.cls">{{ summary[c.key] ?? 0 }}</p>
                    </div>
                </div>

                <!-- แถบสรุปการมาทำงาน -->
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-2 flex items-center justify-between text-sm">
                        <span class="font-medium text-gray-700">อัตราการมาทำงานวันนี้</span>
                        <span class="font-bold text-emerald-600">{{ checkedPct }}%</span>
                    </div>
                    <div class="h-3 w-full overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-emerald-500 transition-all" :style="{ width: checkedPct + '%' }" />
                    </div>
                    <p class="mt-2 text-xs text-gray-400">มาทำงาน {{ (summary.present ?? 0) + (summary.late ?? 0) }} / {{ summary.total ?? 0 }} คน</p>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- ลงเวลาแล้ว -->
                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                        <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">ลงเวลาแล้ว ({{ filteredPresent.length }})</div>
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    <th class="px-6 py-3">ชื่อ - นามสกุล</th>
                                    <th class="px-6 py-3 text-center">เข้า</th>
                                    <th class="px-6 py-3 text-center">ออก</th>
                                    <th class="px-6 py-3 text-center">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-if="filteredPresent.length === 0"><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-400">ไม่พบผู้ลงเวลา</td></tr>
                                <tr v-for="(p, i) in filteredPresent" :key="i" class="text-sm text-gray-700">
                                    <td class="px-6 py-3">
                                        <div class="font-medium text-gray-900">{{ p.name }}</div>
                                        <div class="text-xs text-gray-400">{{ p.position ?? '—' }}</div>
                                    </td>
                                    <td class="px-6 py-3 text-center font-mono tabular-nums text-gray-600">{{ fmtTime(p.check_in_time) }}</td>
                                    <td class="px-6 py-3 text-center font-mono tabular-nums" :class="p.check_out_time ? 'text-gray-600' : 'text-gray-300'">{{ fmtTime(p.check_out_time) }}</td>
                                    <td class="px-6 py-3 text-center">
                                        <span :class="p.status === 'late' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'" class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold">
                                            {{ p.status === 'late' ? 'มาสาย' : 'ปกติ' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- ยังไม่ลงเวลา -->
                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                        <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">ยังไม่ลงเวลา ({{ filteredAbsent.length }})</div>
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    <th class="px-6 py-3">ชื่อ - นามสกุล</th>
                                    <th class="px-6 py-3">ตำแหน่ง</th>
                                    <th class="px-6 py-3 text-center">บันทึกเป็น</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-if="filteredAbsent.length === 0"><td colspan="3" class="px-6 py-8 text-center text-sm text-gray-400">ลงเวลาครบทุกคน </td></tr>
                                <tr v-for="(a, i) in filteredAbsent" :key="i" class="text-sm text-gray-700">
                                    <td class="px-6 py-3 font-medium text-gray-900">{{ a.name }}</td>
                                    <td class="px-6 py-3 text-gray-500">{{ a.position ?? '—' }}</td>
                                    <td class="px-6 py-3 text-center">
                                        <span v-if="a.status_label" class="inline-flex rounded-full bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-700">{{ a.status_label }}</span>
                                        <span v-else class="text-xs text-gray-300">ยังไม่บันทึก</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
