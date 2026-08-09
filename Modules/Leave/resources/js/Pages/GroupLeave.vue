<script setup>
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    groupName: { type: String, default: null },
    summary: { type: Object, default: () => ({ total: 0, pending: 0, approved: 0, rejected: 0, cancelled: 0 }) },
});

const filter = ref('all');
const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.rows.filter((r) => {
        if (filter.value !== 'all' && r.status !== filter.value) return false;
        if (q && !(r.name ?? '').toLowerCase().includes(q) && !(r.type ?? '').toLowerCase().includes(q)) return false;
        return true;
    });
});

const cards = [
    { key: 'all', label: 'ทั้งหมด', color: 'text-gray-800', active: 'border-gray-400 bg-white' },
    { key: 'pending', label: 'รออนุมัติ', color: 'text-amber-600', active: 'border-amber-400 bg-amber-50' },
    { key: 'approved', label: 'อนุมัติแล้ว', color: 'text-emerald-600', active: 'border-emerald-400 bg-emerald-50' },
    { key: 'rejected', label: 'ตีกลับ', color: 'text-rose-600', active: 'border-rose-400 bg-rose-50' },
    { key: 'cancelled', label: 'ยกเลิก', color: 'text-gray-500', active: 'border-gray-400 bg-gray-50' },
];
const countOf = (k) => (k === 'all' ? props.summary.total : props.summary[k] ?? 0);
</script>

<template>
    <Head title="ทะเบียนลาของกลุ่ม" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">ทะเบียนลาของกลุ่ม</h2>
                    <p class="text-xs text-gray-400">{{ groupName ?? 'กลุ่มของฉัน' }} · ใบลาของสมาชิกในกลุ่ม (อ่านอย่างเดียว)</p>
                </div>
                <input v-model="search" type="text" placeholder="ค้นหา ชื่อ / ประเภทลา" class="w-56 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-6xl space-y-5 px-4 sm:px-6 lg:px-8">
                <!-- สรุป (คลิกกรอง) -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                    <button v-for="c in cards" :key="c.key" type="button" class="rounded-2xl border p-4 text-left transition" :class="filter === c.key ? c.active : 'border-gray-100 bg-white hover:bg-gray-50'" @click="filter = c.key">
                        <div class="text-2xl font-bold" :class="c.color">{{ countOf(c.key) }}</div>
                        <div class="text-xs text-gray-500">{{ c.label }}</div>
                    </button>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    <th class="px-4 py-3">ชื่อ-สกุล</th>
                                    <th class="px-4 py-3">ประเภทลา</th>
                                    <th class="px-4 py-3">วันที่เริ่ม</th>
                                    <th class="px-4 py-3">ถึงวันที่</th>
                                    <th class="px-4 py-3 text-center">จำนวนวัน</th>
                                    <th class="px-4 py-3">เหตุผล</th>
                                    <th class="px-4 py-3 text-center">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-if="filtered.length === 0"><td colspan="7" class="px-6 py-12 text-center text-sm text-gray-400">ไม่พบใบลา</td></tr>
                                <tr v-for="r in filtered" :key="r.id" class="text-sm text-gray-700 hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ r.name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ r.type }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ r.start }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ r.end }}</td>
                                    <td class="px-4 py-3 text-center text-gray-600">{{ r.days }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ r.reason ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <StatusBadge :status="r.status" />
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
