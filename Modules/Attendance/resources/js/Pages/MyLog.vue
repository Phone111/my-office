<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    me: { type: Object, default: () => ({}) },
    rows: { type: Array, default: () => [] },
    summary: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
});

const statusCls = {
    present: 'bg-emerald-100 text-emerald-700',
    late: 'bg-amber-100 text-amber-700',
    leave: 'bg-sky-100 text-sky-700',
    official: 'bg-violet-100 text-violet-700',
    absent: 'bg-rose-100 text-rose-700',
    forgot: 'bg-gray-100 text-gray-500',
};

const filter = ref('all');
const filtered = computed(() => (filter.value === 'all' ? props.rows : props.rows.filter((r) => r.status === filter.value)));
const shownSummary = computed(() => props.summary.filter((s) => s.count > 0));

const printPage = () => window.print();
</script>

<template>
    <Head title="สมุดลงเวลาของฉัน" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">สมุดลงเวลาของฉัน</h2>
                <button type="button" class="no-print rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200" @click="printPage">พิมพ์</button>
            </div>
        </template>

        <div class="py-10">
            <div id="mylog-print" class="mx-auto max-w-4xl space-y-5 px-4 sm:px-6 lg:px-8">
                <!-- หัว: รูป + ชื่อ -->
                <div class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 text-2xl font-bold text-gray-400">
                        <img v-if="me.photo" :src="me.photo" alt="" class="h-full w-full object-cover" />
                        <span v-else>{{ (me.name ?? '?').charAt(0) }}</span>
                    </div>
                    <div>
                        <div class="text-lg font-semibold text-gray-900">{{ me.name }}</div>
                        <div class="text-sm text-gray-400">{{ me.position ?? '—' }} · ลงเวลาทั้งหมด {{ total }} วัน</div>
                    </div>
                </div>

                <!-- สรุปยอด (คลิกกรอง) -->
                <div v-if="shownSummary.length" class="no-print grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <button type="button" class="rounded-2xl border p-4 text-left transition" :class="filter === 'all' ? 'border-indigo-400 bg-indigo-50' : 'border-gray-100 bg-white hover:bg-gray-50'" @click="filter = 'all'">
                        <div class="text-2xl font-bold text-gray-800">{{ total }}</div>
                        <div class="text-xs text-gray-500">ทั้งหมด</div>
                    </button>
                    <button v-for="s in shownSummary" :key="s.key" type="button" class="rounded-2xl border p-4 text-left transition" :class="filter === s.key ? 'border-indigo-400 bg-indigo-50' : 'border-gray-100 bg-white hover:bg-gray-50'" @click="filter = s.key">
                        <div class="text-2xl font-bold text-gray-800">{{ s.count }}</div>
                        <div class="text-xs text-gray-500">{{ s.label }}</div>
                    </button>
                </div>

                <!-- ตาราง -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-center text-sm font-semibold text-gray-700">ลงเวลาปฏิบัติราชการ</div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3 text-left">วัน - เดือน - ปี</th>
                                <th class="px-4 py-3 text-center">สถานะ</th>
                                <th class="px-4 py-3 text-center">เริ่มปฏิบัติงาน</th>
                                <th class="px-4 py-3 text-center">เลิกปฏิบัติงาน</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="filtered.length === 0"><td colspan="4" class="px-6 py-12 text-center text-sm text-gray-400">ยังไม่มีข้อมูลการลงเวลา</td></tr>
                            <tr v-for="(r, i) in filtered" :key="i" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ r.date }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="statusCls[r.status] ?? 'bg-gray-100 text-gray-500'">{{ r.status_label }}</span>
                                </td>
                                <td class="px-4 py-3 text-center tabular-nums" :class="r.check_in ? 'text-gray-700' : 'text-gray-300'">{{ r.check_in ?? '—' }}</td>
                                <td class="px-4 py-3 text-center tabular-nums" :class="r.check_out ? 'text-gray-700' : 'text-gray-300'">{{ r.check_out ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@media print {
    body * { visibility: hidden; }
    #mylog-print, #mylog-print * { visibility: visible; }
    #mylog-print { position: absolute; left: 0; top: 0; width: 100%; }
    .no-print { display: none !important; }
}
</style>
