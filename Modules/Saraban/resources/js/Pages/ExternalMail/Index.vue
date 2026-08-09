<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    myUnit: { type: String, default: null },
    pending: { type: Number, default: 0 },
});
const flash = computed(() => usePage().props.flash?.success);
const prioCls = (k) => ({ urgent: 'text-amber-600', very_urgent: 'text-orange-600', most_urgent: 'text-rose-600' }[k] ?? 'text-gray-400');
</script>

<template>
    <Head title="รับหนังสือ สพฐ./ศธจ./จังหวัด" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">รับหนังสือจากหน่วยงานภายนอก (เหนือเขต)</h2>
                    <p class="text-xs text-gray-400">{{ myUnit }} · สพฐ. / ศธจ. / จังหวัด / สพป.-สพม. อื่น</p>
                </div>
                <Link :href="route('saraban.external-mail.create')" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">ลงทะเบียนรับหนังสือ</Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div v-if="pending" class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-700 ring-1 ring-amber-100">มีหนังสือรับที่ยังไม่ได้มอบกลุ่มงาน {{ pending }} ฉบับ</div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">ทะเบียนหนังสือรับจากภายนอก ({{ rows.length }})</div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3">เลขรับ</th><th class="px-4 py-3">วันรับ</th><th class="px-4 py-3">จาก</th><th class="px-4 py-3">เลขที่/ลงวันที่</th><th class="px-4 py-3">เรื่อง</th><th class="px-4 py-3">มอบ</th><th class="px-4 py-3 text-center">ดู</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="rows.length === 0"><td colspan="7" class="px-6 py-8 text-center text-sm text-gray-400">ยังไม่มีหนังสือรับ</td></tr>
                            <tr v-for="r in rows" :key="r.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono font-semibold text-indigo-700">{{ r.receive_label }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ r.received_thai }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ r.source }}<span v-if="r.source_name" class="block text-xs text-gray-400">{{ r.source_name }}</span></td>
                                <td class="px-4 py-3 text-gray-500">{{ r.number ?? '—' }}<span v-if="r.doc_date_thai" class="block text-xs text-gray-400">{{ r.doc_date_thai }}</span></td>
                                <td class="px-4 py-3 font-medium text-gray-900"><span v-if="r.priority_key !== 'normal'" :class="prioCls(r.priority_key)" class="mr-1 text-xs font-bold">[{{ r.priority }}]</span>{{ r.subject }}<span v-if="r.confidential" class="ml-1 text-xs text-rose-500">[ลับ]</span></td>
                                <td class="px-4 py-3 text-gray-500">
                                    <span v-if="r.group" class="block">{{ r.group }}</span>
                                    <span v-if="r.assignee" class="block text-xs text-gray-400">{{ r.assignee }}</span>
                                    <span v-if="!r.group && !r.assignee" class="text-amber-600">ยังไม่มอบ</span>
                                </td>
                                <td class="px-4 py-3 text-center"><Link :href="route('saraban.external-mail.show', r.id)" class="text-indigo-600 hover:underline">ดู</Link></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
