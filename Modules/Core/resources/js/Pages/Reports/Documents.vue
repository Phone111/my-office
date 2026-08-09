<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    documents: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] }, // [{value,label}]
    current: { type: String, default: null },
});

const filter = (category) => {
    router.get(route('reports.documents'), category ? { category } : {}, { preserveScroll: true, preserveState: true });
};

const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.documents;
    return props.documents.filter(
        (d) =>
            (d.title ?? '').toLowerCase().includes(q) ||
            (d.number ?? '').toLowerCase().includes(q) ||
            (d.creator ?? '').toLowerCase().includes(q),
    );
});

const detail = ref(null);
</script>

<template>
    <Head title="ทะเบียนหนังสือ" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">ทะเบียนหนังสือ</h2>
                    <p class="text-xs text-gray-500">ทะเบียนรวมทั้งองค์กร · อ่านอย่างเดียว</p>
                </div>
                <TextInput v-model="search" type="text" placeholder="ค้นหาเลขที่ / เรื่อง / ผู้สร้าง" class="w-64" />
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <!-- ป้ายบอกขอบเขต (กันสับสนกับ "แฟ้มเอกสาร" ส่วนตัว) -->
                <div class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-800">
                    <span></span>
                    <span>นี่คือ <strong>ทะเบียนรวมทั้งองค์กร</strong> — แสดงเอกสารของทุกคน (ดูอย่างเดียว แก้/ลบไม่ได้) · หากต้องการจัดการเอกสารของตัวเอง ไปที่เมนู “แฟ้มเอกสาร”</span>
                </div>

                <!-- ตัวกรองประเภท -->
                <div class="flex flex-wrap gap-2">
                    <button
                        class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                        :class="!current ? 'bg-slate-700 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50'"
                        @click="filter(null)"
                    >ทั้งหมด</button>
                    <button
                        v-for="c in categories"
                        :key="c.value"
                        class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                        :class="current === c.value ? 'bg-slate-700 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50'"
                        @click="filter(c.value)"
                    >{{ c.label }}</button>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3">เลขที่หนังสือ</th>
                                <th class="px-6 py-3">ประเภท</th>
                                <th class="px-6 py-3">เรื่อง</th>
                                <th class="px-6 py-3">ผู้สร้าง</th>
                                <th class="px-6 py-3 text-center">สถานะ</th>
                                <th class="px-6 py-3">วันที่</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="filtered.length === 0"><td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400">ไม่พบเอกสาร</td></tr>
                            <tr v-for="d in filtered" :key="d.id" class="cursor-pointer text-sm text-gray-700 hover:bg-indigo-50/50" @click="detail = d">
                                <td class="px-6 py-3 font-medium text-gray-900">{{ d.number ?? '—' }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ d.category_label }}</td>
                                <td class="px-6 py-3">{{ d.title }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ d.creator ?? '—' }}</td>
                                <td class="px-6 py-3 text-center">
                                    <StatusBadge :status="d.status" />
                                </td>
                                <td class="px-6 py-3 text-gray-500">{{ d.created_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- รายละเอียดหนังสือ -->
        <Modal :show="detail !== null" @close="detail = null">
            <div v-if="detail" class="p-6">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-lg font-semibold text-gray-900">{{ detail.title }}</h2>
                    <StatusBadge :status="detail.status" class="shrink-0" />
                </div>
                <div class="mt-1 flex flex-wrap gap-2 text-xs">
                    <span v-if="detail.number" class="rounded-full bg-indigo-50 px-2.5 py-0.5 font-mono font-medium text-indigo-700">{{ detail.number }}</span>
                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-gray-600">{{ detail.category_label }}</span>
                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-gray-600">{{ detail.created_at }}</span>
                </div>
                <p class="mt-3 text-sm text-gray-500">ผู้สร้าง: {{ detail.creator || '—' }}</p>
                <div v-if="detail.content" class="rich-content mt-3 text-sm leading-relaxed text-gray-700" v-html="detail.content" />
                <a v-if="detail.file_url" :href="detail.file_url" target="_blank" class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-800">ดาวน์โหลดไฟล์แนบ</a>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="detail = null">ปิด</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
