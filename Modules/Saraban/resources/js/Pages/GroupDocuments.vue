<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { thaiDate } from '@/utils/format';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    documents: { type: Array, default: () => [] },
    category: { type: String, default: 'memo' },
    folders: { type: Array, default: () => [] },
    year: { type: Number, default: null },
    years: { type: Array, default: () => [] },
    groupName: { type: String, default: null },
});

const changeYear = (e) => router.get(route('saraban.group.documents', { category: props.category, year: e.target.value }), {}, { preserveState: false });

const dotColor = { normal: 'bg-emerald-500', urgent: 'bg-fuchsia-500', very_urgent: 'bg-orange-500', most_urgent: 'bg-rose-600' };
const priorityLabel = { normal: 'ปกติ', urgent: 'ด่วน', very_urgent: 'ด่วนมาก', most_urgent: 'ด่วนที่สุด' };
const statusLabel = { draft: 'ร่าง', pending: 'รออนุมัติ', approved: 'ดำเนินการแล้ว', rejected: 'ตีกลับ' };

const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.documents;
    return props.documents.filter((d) => (d.title ?? '').toLowerCase().includes(q) || (d.creator ?? '').toLowerCase().includes(q) || (d.document_number ?? '').toLowerCase().includes(q));
});
const currentFolder = computed(() => props.folders.find((f) => f.key === props.category) ?? { label: 'เอกสารของกลุ่ม' });
</script>

<template>
    <Head title="เอกสารของกลุ่ม" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">เอกสารของกลุ่ม — {{ currentFolder.label }}</h2>
                    <p class="text-xs text-gray-400">{{ groupName ?? 'กลุ่มของฉัน' }} · เอกสารของสมาชิกในกลุ่ม (อ่านอย่างเดียว)</p>
                </div>
                <div class="flex items-center gap-2">
                    <select :value="year" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="changeYear">
                        <option v-for="y in years" :key="y" :value="y">ปี {{ y }}</option>
                    </select>
                    <input v-model="search" type="text" placeholder="ค้นหา เรื่อง / ผู้จัดทำ" class="w-56 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-6xl space-y-5 px-4 sm:px-6 lg:px-8">
                <!-- แท็บหมวด -->
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-for="folder in folders"
                        :key="folder.key"
                        :href="route('saraban.group.documents', { category: folder.key, year })"
                        class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition"
                        :class="folder.key === category ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50'"
                    >
                        {{ folder.label }}
                        <span class="rounded-full px-2 text-xs font-semibold" :class="folder.key === category ? 'bg-white/20' : 'bg-gray-100 text-gray-500'">{{ folder.count }}</span>
                    </Link>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-[15px]">
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-4 py-3 text-center">ชั้นความเร็ว</th>
                                    <th class="px-4 py-3 text-center">ทะเบียน</th>
                                    <th class="px-4 py-3 text-left">เรื่อง</th>
                                    <th class="px-4 py-3 text-left">ผู้จัดทำ</th>
                                    <th class="px-4 py-3 text-center">วันที่</th>
                                    <th class="px-4 py-3 text-center">สถานะ</th>
                                    <th class="px-4 py-3 text-center">ดู</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-if="filtered.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-400">{{ search ? 'ไม่พบเอกสารที่ค้นหา' : 'ยังไม่มีเอกสารของกลุ่มในแฟ้มนี้' }}</td>
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
                                    <td class="px-4 py-3 text-gray-600">{{ d.creator ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center text-gray-500">{{ thaiDate(d.created_at) }}</td>
                                    <td class="px-4 py-3 text-center text-gray-500">{{ statusLabel[d.status] ?? d.status }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <Link :href="route('saraban.documents.show', d.id)" class="inline-flex text-indigo-600 hover:text-indigo-800" title="ดู">
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
