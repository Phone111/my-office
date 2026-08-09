<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
});

const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.rows.filter((r) => !q || (r.title ?? '').toLowerCase().includes(q) || (r.number ?? '').toLowerCase().includes(q) || (r.division ?? '').toLowerCase().includes(q));
});
const printPage = () => window.print();
</script>

<template>
    <Head title="ทะเบียนลำดับเอกสาร" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ทะเบียนลำดับเอกสาร</h2>
                <div class="no-print flex items-center gap-2">
                    <input v-model="search" type="text" placeholder="ค้นหา เลข / เรื่อง / กลุ่ม" class="w-56 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <Link :href="route('saraban.sequence.create')" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500">+ ออกเลข</Link>
                    <button type="button" class="rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200" @click="printPage">พิมพ์</button>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div id="seq-print" class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <th class="border border-gray-100 px-3 py-2 text-center">เลขที่</th>
                                    <th class="border border-gray-100 px-4 py-2 text-left">เรื่อง</th>
                                    <th class="border border-gray-100 px-4 py-2 text-left">ผู้ขอ</th>
                                    <th class="border border-gray-100 px-4 py-2 text-left">วันที่ออกเลข</th>
                                    <th class="border border-gray-100 px-3 py-2 text-center">ไฟล์</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="filtered.length === 0"><td colspan="5" class="px-6 py-10 text-center text-sm text-gray-400">ยังไม่มีรายการ</td></tr>
                                <tr v-for="r in filtered" :key="r.id" class="text-gray-700 hover:bg-indigo-50/40">
                                    <td class="border border-gray-100 px-3 py-2 text-center font-semibold text-rose-600">{{ r.number }}</td>
                                    <td class="border border-gray-100 px-4 py-2 font-medium text-gray-900">{{ r.title }}</td>
                                    <td class="border border-gray-100 px-4 py-2 text-gray-500">{{ r.division ?? '—' }}</td>
                                    <td class="border border-gray-100 px-4 py-2 text-gray-500">{{ r.issued_thai }}</td>
                                    <td class="border border-gray-100 px-3 py-2 text-center">
                                        <a v-if="r.file" :href="r.file" target="_blank" class="text-indigo-600 hover:underline">เปิด</a>
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
    #seq-print, #seq-print * { visibility: visible; }
    #seq-print { position: absolute; left: 0; top: 0; width: 100%; }
    .no-print { display: none !important; }
}
</style>
