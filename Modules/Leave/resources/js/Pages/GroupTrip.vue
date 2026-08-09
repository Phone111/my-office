<script setup>
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    groupName: { type: String, default: null },
    summary: { type: Object, default: () => ({ total: 0, pending: 0, approved: 0, rejected: 0 }) },
});

const filter = ref('all');
const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.rows.filter((r) => {
        if (filter.value !== 'all' && r.status !== filter.value) return false;
        if (q && !(r.title ?? '').toLowerCase().includes(q) && !(r.requester ?? '').toLowerCase().includes(q) && !(r.destination ?? '').toLowerCase().includes(q)) return false;
        return true;
    });
});

const cards = [
    { key: 'all', label: 'ทั้งหมด', color: 'text-gray-800', active: 'border-gray-400 bg-white' },
    { key: 'pending', label: 'รออนุมัติ', color: 'text-amber-600', active: 'border-amber-400 bg-amber-50' },
    { key: 'approved', label: 'อนุมัติแล้ว', color: 'text-emerald-600', active: 'border-emerald-400 bg-emerald-50' },
    { key: 'rejected', label: 'ตีกลับ', color: 'text-rose-600', active: 'border-rose-400 bg-rose-50' },
];
const countOf = (k) => (k === 'all' ? props.summary.total : props.summary[k] ?? 0);
</script>

<template>
    <Head title="ทะเบียนไปราชการของกลุ่ม" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">ทะเบียนไปราชการของกลุ่ม</h2>
                    <p class="text-xs text-gray-400">{{ groupName ?? 'กลุ่มของฉัน' }} · คำขอไปราชการของสมาชิกในกลุ่ม (อ่านอย่างเดียว)</p>
                </div>
                <input v-model="search" type="text" placeholder="ค้นหา เรื่อง / ผู้ขอ / ปลายทาง" class="w-56 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-6xl space-y-5 px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <button v-for="c in cards" :key="c.key" type="button" class="rounded-2xl border p-4 text-left transition" :class="filter === c.key ? c.active : 'border-gray-100 bg-white hover:bg-gray-50'" @click="filter = c.key">
                        <div class="text-2xl font-bold" :class="c.color">{{ countOf(c.key) }}</div>
                        <div class="text-xs text-gray-500">{{ c.label }}</div>
                    </button>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3">เรื่อง</th>
                                <th class="px-4 py-3">ปลายทาง</th>
                                <th class="px-4 py-3">ผู้ขออนุญาต</th>
                                <th class="px-4 py-3">เริ่ม</th>
                                <th class="px-4 py-3">สิ้นสุด</th>
                                <th class="px-4 py-3 text-center">สถานะ</th>
                                <th class="px-4 py-3 text-center">อ่าน</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="filtered.length === 0"><td colspan="7" class="px-6 py-12 text-center text-sm text-gray-400">ไม่พบคำขอไปราชการ</td></tr>
                            <tr v-for="r in filtered" :key="r.id" class="text-sm text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ r.title }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ r.destination ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ r.requester }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ r.start }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ r.end }}</td>
                                <td class="px-4 py-3 text-center">
                                    <StatusBadge :status="r.status" />
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Link :href="route('official-trips.show', r.id)" class="text-indigo-600 hover:underline">ดู</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
