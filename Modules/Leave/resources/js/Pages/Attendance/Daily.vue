<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    statuses: { type: Object, default: () => ({}) },
    date: { type: String, default: '' },
    dateThai: { type: String, default: '' },
    summary: { type: Object, default: () => ({}) },
});

const pickDate = ref(props.date);
const goDate = () => router.get(route('leave.attendance.daily'), { date: pickDate.value }, { preserveScroll: true });

const summaryList = computed(() =>
    Object.entries(props.statuses)
        .map(([code, label]) => ({ code, label, count: props.summary[code] ?? 0 }))
        .filter((s) => s.count > 0),
);

const tone = (code) =>
    ({
        present: 'bg-emerald-100 text-emerald-700',
        trip: 'bg-indigo-100 text-indigo-700',
        sick: 'bg-amber-100 text-amber-700',
        personal: 'bg-amber-100 text-amber-700',
        maternity: 'bg-pink-100 text-pink-700',
        other_leave: 'bg-amber-100 text-amber-700',
        late: 'bg-orange-100 text-orange-700',
        absent: 'bg-rose-100 text-rose-700',
    })[code] ?? 'bg-gray-100 text-gray-500';

const print = () => window.print();
</script>

<template>
    <Head title="รายงานการปฏิบัติราชการรายวัน" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800">สรุปการปฏิบัติราชการรายวัน</h2>
                <Link :href="route('leave.attendance.monthly')" class="text-sm text-gray-500 hover:text-gray-700">รายงานรอบเดือน ›</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 print:hidden">
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-600">เลือกวัน</label>
                        <input v-model="pickDate" type="date" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="goDate" />
                    </div>
                    <button type="button" class="rounded-md border border-gray-300 px-4 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="print">🖨 พิมพ์</button>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="mb-1 text-center text-lg font-bold text-gray-800">การปฏิบัติราชการ วันที่ {{ dateThai }}</h3>

                    <!-- สรุปยอด -->
                    <div class="my-4 flex flex-wrap justify-center gap-2">
                        <span v-for="s in summaryList" :key="s.code" class="rounded-full px-3 py-1 text-xs font-semibold" :class="tone(s.code)">{{ s.label }} {{ s.count }}</span>
                    </div>

                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium text-gray-500">
                                <th class="px-4 py-2 text-center">ที่</th>
                                <th class="px-4 py-2">ชื่อ-สกุล</th>
                                <th class="px-4 py-2">กลุ่มงาน</th>
                                <th class="px-4 py-2 text-center">สถานะ</th>
                                <th class="px-4 py-2">หมายเหตุ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(r, i) in rows" :key="i" class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-center text-gray-400">{{ i + 1 }}</td>
                                <td class="px-4 py-2 font-medium text-gray-900">{{ r.name }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ r.group ?? '—' }}</td>
                                <td class="px-4 py-2 text-center">
                                    <span v-if="r.status" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="tone(r.status)">{{ r.label }}</span>
                                    <span v-else class="text-xs text-gray-300">ยังไม่บันทึก</span>
                                </td>
                                <td class="px-4 py-2 text-gray-500">{{ r.note ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
