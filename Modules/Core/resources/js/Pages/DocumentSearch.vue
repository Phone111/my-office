<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    registers: { type: Array, default: () => [] }, // [{key,label,counts:{be:count}}]
    years: { type: Array, default: () => [] }, // [be,...]
    selected: { type: Object, default: () => ({ register: null, year: null }) },
    results: { type: Array, default: null },
    resultTitle: { type: String, default: null },
});

// ไอคอน + สี ต่อทะเบียน
const META = {
    incoming: { color: 'bg-sky-100 text-sky-700', icon: 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3' },
    outgoing: { color: 'bg-emerald-100 text-emerald-700', icon: 'M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5' },
    order: { color: 'bg-violet-100 text-violet-700', icon: 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664M9.75 3.104c.251.023.501.05.75.082m-1.5-.082a24.301 24.301 0 0 0-4.5 0m0 0V8.25m0-5.146V3.104' },
    memo: { color: 'bg-amber-100 text-amber-700', icon: 'M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z' },
    circular: { color: 'bg-rose-100 text-rose-700', icon: 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535' },
    trip: { color: 'bg-indigo-100 text-indigo-700', icon: 'M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z' },
};
const meta = (k) => META[k] ?? { color: 'bg-gray-100 text-gray-600', icon: '' };
const total = (r) => Object.values(r.counts).reduce((a, b) => a + b, 0);
</script>

<template>
    <Head title="ระบบสืบค้นข้อมูล" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">ระบบสืบค้นข้อมูล</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- ตารางทะเบียน × ปี -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center gap-2 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-white px-6 py-4">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                        <div>
                            <p class="font-semibold text-gray-800">เลือกทะเบียนและปีที่ต้องการสืบค้น</p>
                            <p class="text-xs text-gray-400">คลิกที่ปี พ.ศ. เพื่อดูรายการเอกสารในทะเบียนนั้น</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto p-4">
                        <p v-if="years.length === 0" class="px-2 py-10 text-center text-sm text-gray-400">ยังไม่มีข้อมูลในทะเบียน</p>
                        <table v-else class="min-w-full border-separate" style="border-spacing: 0">
                            <thead>
                                <tr>
                                    <th class="sticky left-0 z-10 bg-white px-3 pb-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">ทะเบียน \ ปี พ.ศ.</th>
                                    <th v-for="y in years" :key="y" class="px-2 pb-3 text-center text-sm font-bold text-gray-500">{{ y }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="r in registers" :key="r.key" class="group">
                                    <th class="sticky left-0 z-10 bg-white py-1.5 pr-4 text-left group-hover:bg-gray-50">
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg" :class="meta(r.key).color">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" :d="meta(r.key).icon" /></svg>
                                            </span>
                                            <div class="min-w-0">
                                                <div class="whitespace-nowrap text-sm font-semibold text-gray-800">{{ r.label }}</div>
                                                <div class="text-xs text-gray-400">รวม {{ total(r) }} ฉบับ</div>
                                            </div>
                                        </div>
                                    </th>
                                    <td v-for="y in years" :key="y" class="px-1 py-1.5 text-center align-middle">
                                        <Link
                                            v-if="r.counts[y]"
                                            :href="route('documents.search', { register: r.key, year: y })"
                                            class="inline-flex min-w-[3rem] flex-col items-center rounded-lg px-2 py-1.5 text-sm font-semibold transition"
                                            :class="selected.register === r.key && selected.year === y ? 'bg-indigo-600 text-white shadow' : 'text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-50'"
                                            preserve-scroll
                                        >
                                            <span class="text-base font-bold leading-none">{{ r.counts[y] }}</span>
                                            <span class="text-[10px] font-normal" :class="selected.register === r.key && selected.year === y ? 'text-indigo-100' : 'text-gray-400'">ฉบับ</span>
                                        </Link>
                                        <span v-else class="text-gray-200">·</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ผลลัพธ์ -->
                <div v-if="results !== null" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center gap-2 border-b border-gray-100 bg-indigo-50/60 px-6 py-3">
                        <span class="font-semibold text-gray-800">{{ resultTitle }}</span>
                        <span class="rounded-full bg-indigo-600 px-2 py-0.5 text-xs font-bold text-white">{{ results.length }} ฉบับ</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    <th class="px-4 py-3">เลขทะเบียน</th>
                                    <th class="px-4 py-3">เรื่อง</th>
                                    <th class="px-4 py-3">วัน-เดือน-ปี</th>
                                    <th class="px-4 py-3">จาก</th>
                                    <th class="px-4 py-3">ถึง</th>
                                    <th class="px-4 py-3 text-center">ดู</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-if="results.length === 0"><td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400">ไม่พบเอกสารในปีนี้</td></tr>
                                <tr v-for="(d, i) in results" :key="i" class="text-sm text-gray-700 odd:bg-white even:bg-gray-50/40 hover:bg-indigo-50/40">
                                    <td class="whitespace-nowrap px-4 py-3 font-mono font-semibold text-indigo-700">{{ d.number }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ d.title }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-500">{{ d.date_thai ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ d.from ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ d.to ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <Link :href="d.link" class="inline-flex text-indigo-600 hover:text-indigo-800" title="ดู">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                        </Link>
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
