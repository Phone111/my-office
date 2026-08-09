<script setup>
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ rows: { type: Array, default: () => [] } });
const flash = computed(() => usePage().props.flash?.success);

const filter = ref('all');
const search = ref('');
const filters = [
    { key: 'all', label: 'ทั้งหมด' },
    { key: 'submitted', label: 'รอจัดรถ' },
    { key: 'assigned', label: 'รออนุมัติ' },
    { key: 'booked', label: 'อนุมัติแล้ว' },
];

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.rows.filter((r) => {
        if (filter.value !== 'all' && r.status !== filter.value) return false;
        if (q && !(r.requester ?? '').toLowerCase().includes(q) && !(r.plate ?? '').toLowerCase().includes(q) && !(r.purpose ?? '').toLowerCase().includes(q)) return false;
        return true;
    });
});

const cancel = (r) => {
    if (confirm(`ยกเลิกการจองของ ${r.requester}?`)) {
        router.post(route('booking.vehicle-flow.officer-cancel', r.id), {}, { preserveScroll: true });
    }
};
const printPage = () => window.print();
</script>

<template>
    <Head title="ทะเบียนการจองรถ" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ทะเบียนการจองรถ / ตรวจสอบทะเบียนรถ</h2>
                <div class="no-print flex items-center gap-2">
                    <input v-model="search" type="text" placeholder="ค้นหา ผู้ขอ / ทะเบียน / เรื่อง" class="w-56 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <button type="button" class="rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200" @click="printPage">พิมพ์</button>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="no-print inline-flex flex-wrap gap-1 rounded-xl bg-gray-100 p-1">
                    <button v-for="f in filters" :key="f.key" type="button" class="rounded-lg px-4 py-1.5 text-sm font-medium transition" :class="filter === f.key ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'" @click="filter = f.key">{{ f.label }}</button>
                </div>

                <div id="vreg-print" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <th class="border border-gray-100 px-4 py-2 text-left">วันที่ขอใช้</th>
                                    <th class="border border-gray-100 px-4 py-2 text-left">ทะเบียนรถ</th>
                                    <th class="border border-gray-100 px-4 py-2 text-left">ผู้ขอใช้รถ</th>
                                    <th class="border border-gray-100 px-4 py-2 text-left">เรื่อง</th>
                                    <th class="border border-gray-100 px-3 py-2 text-center">สถานะ</th>
                                    <th class="no-print border border-gray-100 px-3 py-2 text-center">ยกเลิก</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="filtered.length === 0"><td colspan="6" class="px-6 py-10 text-center text-sm text-gray-400">ไม่มีรายการ</td></tr>
                                <tr v-for="r in filtered" :key="r.id" class="text-gray-700 hover:bg-indigo-50/40">
                                    <td class="border border-gray-100 px-4 py-2 text-gray-600">{{ r.when }}</td>
                                    <td class="border border-gray-100 px-4 py-2 font-medium text-gray-900">{{ r.plate ?? '—' }}<div class="text-xs text-gray-400">{{ r.vehicle }}</div></td>
                                    <td class="border border-gray-100 px-4 py-2 text-gray-600">{{ r.requester }}</td>
                                    <td class="border border-gray-100 px-4 py-2 text-gray-500">{{ r.purpose }}</td>
                                    <td class="border border-gray-100 px-3 py-2 text-center">
                                        <StatusBadge :status="r.status" :label="r.status_label" />
                                    </td>
                                    <td class="no-print border border-gray-100 px-3 py-2 text-center">
                                        <button v-if="r.can_cancel" type="button" class="text-rose-600 hover:text-rose-800" @click="cancel(r)">ยกเลิก</button>
                                        <span v-else class="text-gray-300">—</span>
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

<style>
@media print {
    body * { visibility: hidden; }
    #vreg-print, #vreg-print * { visibility: visible; }
    #vreg-print { position: absolute; left: 0; top: 0; width: 100%; }
    .no-print { display: none !important; }
}
</style>
