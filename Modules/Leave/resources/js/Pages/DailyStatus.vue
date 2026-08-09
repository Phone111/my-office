<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    today: { type: String, default: '' },
    summary: { type: Object, default: () => ({ total: 0, trip: 0, leave: 0, normal: 0 }) },
});

const search = ref('');
const filter = ref('all'); // all | trip | leave | normal
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.rows.filter((r) => {
        if (filter.value !== 'all' && r.kind !== filter.value) return false;
        if (q && !(r.name ?? '').toLowerCase().includes(q) && !(r.group ?? '').toLowerCase().includes(q)) return false;
        return true;
    });
});

const badge = (kind) =>
    ({
        trip: 'bg-indigo-100 text-indigo-700',
        leave: 'bg-amber-100 text-amber-700',
        normal: 'bg-emerald-100 text-emerald-700',
    })[kind] ?? 'bg-gray-100 text-gray-600';
</script>

<template>
    <Head title="ไปราชการ/ลาวันนี้" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">การมาปฏิบัติราชการวันนี้ของบุคลากร</h2>
                <span class="text-sm text-gray-500">วันที่ {{ today }}</span>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-6xl space-y-5 px-4 sm:px-6 lg:px-8">
                <!-- สรุปยอด (คลิกกรองได้) -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <button type="button" class="rounded-2xl border p-4 text-left transition" :class="filter === 'all' ? 'border-gray-400 bg-white shadow-sm' : 'border-gray-100 bg-white hover:bg-gray-50'" @click="filter = 'all'">
                        <div class="text-2xl font-bold text-gray-800">{{ summary.total }}</div>
                        <div class="text-xs text-gray-500">บุคลากรทั้งหมด</div>
                    </button>
                    <button type="button" class="rounded-2xl border p-4 text-left transition" :class="filter === 'normal' ? 'border-emerald-400 bg-emerald-50' : 'border-gray-100 bg-white hover:bg-gray-50'" @click="filter = 'normal'">
                        <div class="text-2xl font-bold text-emerald-600">{{ summary.normal }}</div>
                        <div class="text-xs text-gray-500">ปฏิบัติงานปกติ</div>
                    </button>
                    <button type="button" class="rounded-2xl border p-4 text-left transition" :class="filter === 'trip' ? 'border-indigo-400 bg-indigo-50' : 'border-gray-100 bg-white hover:bg-gray-50'" @click="filter = 'trip'">
                        <div class="text-2xl font-bold text-indigo-600">{{ summary.trip }}</div>
                        <div class="text-xs text-gray-500">ไปราชการ</div>
                    </button>
                    <button type="button" class="rounded-2xl border p-4 text-left transition" :class="filter === 'leave' ? 'border-amber-400 bg-amber-50' : 'border-gray-100 bg-white hover:bg-gray-50'" @click="filter = 'leave'">
                        <div class="text-2xl font-bold text-amber-600">{{ summary.leave }}</div>
                        <div class="text-xs text-gray-500">ลา</div>
                    </button>
                </div>

                <div class="flex justify-end">
                    <input v-model="search" type="text" placeholder="ค้นหาชื่อ / กลุ่มงาน" class="w-64 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>

                <!-- ตารางบุคลากร -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    <th class="px-4 py-3 text-center">ที่</th>
                                    <th class="px-4 py-3">ชื่อ-สกุล</th>
                                    <th class="px-4 py-3">กลุ่มงาน</th>
                                    <th class="px-4 py-3 text-center">สถานะ</th>
                                    <th class="px-4 py-3">รายละเอียด</th>
                                    <th class="px-4 py-3">วันเริ่มต้น</th>
                                    <th class="px-4 py-3">วันสิ้นสุด</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-if="filtered.length === 0"><td colspan="7" class="px-6 py-12 text-center text-sm text-gray-400">ไม่พบรายการ</td></tr>
                                <tr v-for="(r, i) in filtered" :key="i" class="text-sm text-gray-700 hover:bg-gray-50">
                                    <td class="px-4 py-3 text-center text-gray-400">{{ i + 1 }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ r.name }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ r.group ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="badge(r.kind)">{{ r.status }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ r.detail ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ r.start ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ r.end ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
