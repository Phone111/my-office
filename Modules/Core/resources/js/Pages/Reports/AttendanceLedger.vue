<script setup>
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    month: { type: String, default: '' },
    people: { type: Array, default: () => [] },
});

const selectedMonth = ref(props.month);
const reload = () => {
    router.get(route('reports.attendance-ledger'), { month: selectedMonth.value }, { preserveScroll: true, preserveState: true });
};

const monthLabel = computed(() =>
    new Date(props.month + '-01').toLocaleDateString('th-TH', { month: 'long', year: 'numeric' }),
);

const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.people;
    return props.people.filter((p) => (p.name ?? '').toLowerCase().includes(q) || (p.position ?? '').toLowerCase().includes(q));
});
</script>

<template>
    <Head title="บัญชีลงเวลาปฏิบัติงาน" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">บัญชีลงเวลาปฏิบัติงาน</h2>
                <div class="flex flex-wrap items-center gap-2">
                    <TextInput v-model="search" type="text" placeholder="ค้นหาชื่อ / ตำแหน่ง" class="w-52 text-sm" />
                    <input v-model="selectedMonth" type="month" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="reload" />
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-500">สรุปการมาปฏิบัติงานประจำเดือน {{ monthLabel }}</p>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-[15px]">
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-4 py-3 text-center">ที่</th>
                                    <th class="px-4 py-3 text-left">ชื่อ - นามสกุล</th>
                                    <th class="px-4 py-3 text-center text-emerald-600">มาปกติ</th>
                                    <th class="px-4 py-3 text-center text-amber-600">มาสาย</th>
                                    <th class="px-4 py-3 text-center text-sky-600">ลา/ราชการ</th>
                                    <th class="px-4 py-3 text-center text-rose-600">ขาด</th>
                                    <th class="px-4 py-3 text-center">รวมมาทำงาน</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-if="filtered.length === 0"><td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">ไม่พบบุคลากร</td></tr>
                                <tr v-for="(p, i) in filtered" :key="i" class="text-gray-700 hover:bg-gray-50">
                                    <td class="px-4 py-3 text-center text-gray-400">{{ i + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">{{ p.name }}</div>
                                        <div class="text-xs text-gray-400">{{ p.position ?? '—' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center font-semibold tabular-nums" :class="p.present ? 'text-emerald-600' : 'text-gray-300'">{{ p.present }}</td>
                                    <td class="px-4 py-3 text-center font-semibold tabular-nums" :class="p.late ? 'text-amber-600' : 'text-gray-300'">{{ p.late }}</td>
                                    <td class="px-4 py-3 text-center font-semibold tabular-nums" :class="p.leave ? 'text-sky-600' : 'text-gray-300'">{{ p.leave }}</td>
                                    <td class="px-4 py-3 text-center font-semibold tabular-nums" :class="p.absent ? 'text-rose-600' : 'text-gray-300'">{{ p.absent }}</td>
                                    <td class="px-4 py-3 text-center font-bold tabular-nums text-gray-900">{{ p.worked }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
