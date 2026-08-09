<script setup>
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    myUnit: { type: String, default: null },
});
const flash = computed(() => usePage().props.flash?.success);

const statusLabel = {
    sent: 'รอผู้รับลงทะเบียน',
    received: 'ผู้รับลงทะเบียนแล้ว',
    forwarded: 'มอบในหน่วยงานแล้ว',
};
const prioCls = (k) => ({ urgent: 'text-amber-600', very_urgent: 'text-orange-600', most_urgent: 'text-rose-600' }[k] ?? 'text-gray-400');

const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.rows;
    return props.rows.filter((r) => (r.subject ?? '').toLowerCase().includes(q) || (r.to ?? '').toLowerCase().includes(q));
});
</script>

<template>
    <Head title="ทะเบียนหนังสือส่ง (ระหว่างหน่วยงาน)" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">ทะเบียนหนังสือส่ง (ระหว่างหน่วยงาน)</h2>
                    <p class="text-xs text-gray-400">{{ myUnit }}</p>
                </div>
                <Link :href="route('saraban.area-mail.compose')" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">+ ส่งหนังสือ</Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>
                <div class="flex justify-end">
                    <input v-model="search" type="text" placeholder="ค้นหา เรื่อง/ผู้รับ" class="w-56 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3">เลขที่</th>
                                <th class="px-4 py-3">ลงวันที่</th>
                                <th class="px-4 py-3">เรื่อง</th>
                                <th class="px-4 py-3">ถึง</th>
                                <th class="px-4 py-3 text-center">สถานะ</th>
                                <th class="px-4 py-3 text-center">ดู</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="filtered.length === 0"><td colspan="6" class="px-6 py-10 text-center text-sm text-gray-400">ยังไม่มีหนังสือส่ง</td></tr>
                            <tr v-for="r in filtered" :key="r.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-500">{{ r.number ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ r.doc_date_thai }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    <span v-if="r.priority_key !== 'normal'" :class="prioCls(r.priority_key)" class="mr-1 text-xs font-bold">[{{ r.priority }}]</span>{{ r.subject }}
                                    <span v-if="r.confidential" class="ml-1 text-xs text-rose-500">[ลับ]</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ r.to }}</td>
                                <td class="px-4 py-3 text-center">
                                    <StatusBadge :status="r.status" :label="statusLabel[r.status]" />
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Link :href="route('saraban.area-mail.show', r.id)" class="text-indigo-600 hover:underline">ดู</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
