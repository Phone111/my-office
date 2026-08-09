<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    pending: { type: Array, default: () => [] },
    received: { type: Array, default: () => [] },
    myUnit: { type: String, default: null },
});
const flash = computed(() => usePage().props.flash?.success);
const prioCls = (k) => ({ urgent: 'text-amber-600', very_urgent: 'text-orange-600', most_urgent: 'text-rose-600' }[k] ?? 'text-gray-400');

const registerReceipt = (r) => {
    if (confirm(`ลงทะเบียนรับหนังสือ "${r.subject}" ?`)) router.post(route('saraban.area-mail.receive', r.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="หนังสือรับ (ระหว่างหน่วยงาน)" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">หนังสือรับ (ระหว่างหน่วยงาน)</h2>
                <p class="text-xs text-gray-400">{{ myUnit }}</p>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <!-- รอลงทะเบียนรับ -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-amber-100">
                    <div class="border-b border-amber-100 bg-amber-50/60 px-6 py-3 text-sm font-semibold text-amber-700">รอลงทะเบียนรับ ({{ pending.length }})</div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3">เลขที่</th><th class="px-4 py-3">ลงวันที่</th><th class="px-4 py-3">เรื่อง</th><th class="px-4 py-3">จาก</th><th class="px-4 py-3 text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="pending.length === 0"><td colspan="5" class="px-6 py-8 text-center text-sm text-gray-400">ไม่มีหนังสือรอลงทะเบียน</td></tr>
                            <tr v-for="r in pending" :key="r.id" class="text-gray-700 hover:bg-amber-50/40">
                                <td class="px-4 py-3 text-gray-500">{{ r.number ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ r.doc_date_thai }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900"><span v-if="r.priority_key !== 'normal'" :class="prioCls(r.priority_key)" class="mr-1 text-xs font-bold">[{{ r.priority }}]</span>{{ r.subject }}<span v-if="r.confidential" class="ml-1 text-xs text-rose-500">[ลับ]</span></td>
                                <td class="px-4 py-3 text-gray-600">{{ r.from }}</td>
                                <td class="px-4 py-3 text-center">
                                    <button class="rounded bg-emerald-600 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-700" @click="registerReceipt(r)">ลงทะเบียนรับ</button>
                                    <Link :href="route('saraban.area-mail.show', r.id)" class="ml-2 text-indigo-600 hover:underline">ดู</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ทะเบียนรับแล้ว -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">ทะเบียนหนังสือรับ ({{ received.length }})</div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3">เลขรับ</th><th class="px-4 py-3">วันรับ</th><th class="px-4 py-3">เรื่อง</th><th class="px-4 py-3">จาก</th><th class="px-4 py-3">มอบให้</th><th class="px-4 py-3 text-center">ดู</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="received.length === 0"><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">ยังไม่มีหนังสือรับ</td></tr>
                            <tr v-for="r in received" :key="r.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono font-semibold text-indigo-700">{{ r.receive_number }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ r.received_thai }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ r.subject }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ r.from }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ r.assignee ?? '—' }}</td>
                                <td class="px-4 py-3 text-center"><Link :href="route('saraban.area-mail.show', r.id)" class="text-indigo-600 hover:underline">ดู</Link></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
