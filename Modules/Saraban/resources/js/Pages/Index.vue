<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { thaiDate } from '@/utils/format';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    documents: { type: Array, default: () => [] },
    category: { type: String, default: 'memo' },
    folders: { type: Array, default: () => [] },
    year: { type: Number, default: null },
    years: { type: Array, default: () => [] },
});

const changeYear = (e) => router.get(route('saraban.documents.index', { category: props.category, year: e.target.value }), {}, { preserveState: false });

const flash = computed(() => usePage().props.flash ?? {});
const roles = computed(() => usePage().props.auth?.roles ?? []);
const canRegisterIncoming = computed(() => ['saraban', 'secretary', 'admin'].some((r) => roles.value.includes(r)));

const currentFolder = computed(
    () => props.folders.find((f) => f.key === props.category) ?? { label: 'แฟ้มเอกสาร' },
);

// แฟ้ม "หนังสือรับ" → แสดงเป็นทะเบียน (ที่ / เรื่อง / ผู้ส่ง / วันที่ส่ง / ดู) อ่านอย่างเดียว
const RECEIVED = ['incoming', 'internal_in', 'general_in'];
const isReceived = computed(() => RECEIVED.includes(props.category));

// สี + ชื่อ ประเภทความเร่งด่วน
const dotColor = {
    normal: 'bg-emerald-500',
    urgent: 'bg-fuchsia-500',
    very_urgent: 'bg-orange-500',
    most_urgent: 'bg-rose-600',
};
const priorityLabel = {
    normal: 'ปกติ',
    urgent: 'ด่วน',
    very_urgent: 'ด่วนมาก',
    most_urgent: 'ด่วนที่สุด',
};

// ที่อยู่หนังสือ (สถานะปัจจุบัน)
const location = (d) =>
    ({
        draft: 'ร่าง',
        pending: 'รอเสนอแฟ้ม',
        approved: 'ดำเนินการแล้ว',
        rejected: 'ตีกลับ',
    })[d.status] ?? d.status;

// ค้นหา
const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.documents;
    return props.documents.filter(
        (d) => (d.title ?? '').toLowerCase().includes(q) || (d.document_number ?? '').toLowerCase().includes(q),
    );
});

const remove = (d) => {
    if (confirm(`ลบเอกสาร "${d.title}" ?`)) {
        router.delete(route('saraban.documents.destroy', d.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="แฟ้มเอกสาร" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ currentFolder.label }}</h2>
                <div class="flex items-center gap-2">
                    <select :value="year" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="changeYear">
                        <option v-for="y in years" :key="y" :value="y">ปี {{ y }}</option>
                    </select>
                    <TextInput v-model="search" type="text" placeholder="ระบุชื่อหนังสือที่ต้องการค้นหา" class="w-64" />
                    <Link v-if="category === 'incoming' && canRegisterIncoming" :href="route('saraban.incoming.create')">
                        <PrimaryButton>+ ลงทะเบียนรับ</PrimaryButton>
                    </Link>
                    <Link v-else-if="category === 'outgoing' && canRegisterIncoming" :href="route('saraban.outgoing.create')">
                        <PrimaryButton>+ ออกเลขทะเบียนส่ง</PrimaryButton>
                    </Link>
                    <Link v-else-if="category === 'order' && canRegisterIncoming" :href="route('saraban.orders.create')">
                        <PrimaryButton>+ ออกเลขคำสั่ง</PrimaryButton>
                    </Link>
                    <Link v-else-if="!isReceived" :href="route('saraban.documents.create', { category })">
                        <PrimaryButton>+ เขียนเอกสารใหม่</PrimaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ flash.success }}
                </div>

                <!-- แท็บหมวดหมู่แฟ้ม -->
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-for="folder in folders"
                        :key="folder.key"
                        :href="route('saraban.documents.index', { category: folder.key, year })"
                        class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition"
                        :class="folder.key === category ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50'"
                    >
                        {{ folder.label }}
                        <span class="rounded-full px-2 text-xs font-semibold" :class="folder.key === category ? 'bg-white/20' : 'bg-gray-100 text-gray-500'">{{ folder.count }}</span>
                    </Link>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <!-- ทะเบียนหนังสือรับ (อ่านอย่างเดียว) -->
                    <div v-if="isReceived" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-[15px]">
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-4 py-3 text-center">ที่</th>
                                    <th class="px-4 py-3 text-center">เลขรับ</th>
                                    <th class="px-4 py-3 text-center">เลขที่หนังสือ</th>
                                    <th class="px-4 py-3 text-left">เรื่อง</th>
                                    <th class="px-4 py-3 text-left">จาก</th>
                                    <th class="px-4 py-3 text-center">ลงวันที่</th>
                                    <th class="px-4 py-3 text-center">ดู</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-if="filtered.length === 0">
                                    <td colspan="7">
                                        <EmptyState :title="search ? 'ไม่พบเอกสารที่ค้นหา' : 'ยังไม่มีหนังสือรับในแฟ้มนี้'" />
                                    </td>
                                </tr>
                                <tr v-for="(d, i) in filtered" :key="d.id" class="text-gray-700 hover:bg-gray-50">
                                    <td class="px-4 py-3 text-center font-mono text-sm text-gray-500">{{ i + 1 }}</td>
                                    <td class="px-4 py-3 text-center font-mono text-sm font-semibold text-indigo-700">{{ d.document_number ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center text-gray-500">{{ d.source_number ?? '—' }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ d.title }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ d.source_name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center text-gray-500">{{ thaiDate(d.source_date ?? d.created_at) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <Link :href="route('saraban.documents.show', d.id)" class="inline-flex text-indigo-600 hover:text-indigo-800" title="ดู">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- แฟ้มเอกสารทั่วไป (เขียน/ส่ง) -->
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-[15px]">
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-4 py-3 text-center">ชั้นความเร็ว</th>
                                    <th class="px-4 py-3 text-center">ทะเบียน</th>
                                    <th class="px-4 py-3 text-left">เรื่อง</th>
                                    <th class="px-4 py-3 text-left">ถึง / เจ้าของเรื่อง</th>
                                    <th class="px-4 py-3 text-center">วันที่</th>
                                    <th class="px-4 py-3 text-center">ที่อยู่หนังสือ</th>
                                    <th class="px-4 py-3 text-center">ดูเรื่อง</th>
                                    <th class="px-4 py-3 text-center">แฟ้ม</th>
                                    <th class="px-4 py-3 text-center">ลบ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-if="filtered.length === 0">
                                    <td colspan="9">
                                        <EmptyState :title="search ? 'ไม่พบเอกสารที่ค้นหา' : 'ยังไม่มีเอกสารในแฟ้มนี้'" />
                                    </td>
                                </tr>
                                <tr v-for="d in filtered" :key="d.id" class="text-gray-700 hover:bg-gray-50">
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center gap-1.5" :title="priorityLabel[d.priority]">
                                            <span class="inline-block h-3.5 w-3.5 rounded-sm" :class="dotColor[d.priority]" />
                                            <span v-if="d.priority !== 'normal'" class="text-xs font-medium text-gray-600">{{ priorityLabel[d.priority] }}</span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono text-sm text-indigo-700">{{ d.document_number ?? '—' }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ d.title }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ d.source_name || d.division || '—' }}</td>
                                    <td class="px-4 py-3 text-center text-gray-500">{{ thaiDate(d.created_at) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="text-gray-600">{{ location(d) }}</div>
                                        <div v-if="d.current_approver" class="text-xs text-gray-400">อยู่ที่ {{ d.current_approver }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <Link :href="route('saraban.documents.show', d.id)" class="inline-flex text-indigo-600 hover:text-indigo-800" title="ดูเรื่อง">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                        </Link>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span v-if="d.filing" class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ d.filing }}</span>
                                        <span v-else class="text-xs text-gray-300">—</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button v-if="!d.filing" class="text-rose-600 hover:text-rose-800" title="ลบ" @click="remove(d)">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
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
