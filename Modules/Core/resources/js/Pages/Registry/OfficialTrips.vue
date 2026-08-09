<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ trips: { type: Array, default: () => [] } });

const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.trips;
    return props.trips.filter(
        (t) =>
            (t.name ?? '').toLowerCase().includes(q) ||
            (t.title ?? '').toLowerCase().includes(q) ||
            (t.destination ?? '').toLowerCase().includes(q),
    );
});

const detail = ref(null);
</script>

<template>
    <Head title="ทะเบียนไปราชการสำนักงาน" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ทะเบียนไปราชการสำนักงาน</h2>
                <TextInput v-model="search" type="text" placeholder="ค้นหาชื่อ / เรื่อง / ประเภท" class="w-64" />
            </div>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-3 px-4 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-500">ทั้งหมด {{ filtered.length }} รายการ</p>
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3">ผู้ขอ</th>
                                <th class="px-6 py-3">เรื่อง</th>
                                <th class="px-6 py-3">สถานที่ไป</th>
                                <th class="px-6 py-3">ช่วงวันที่</th>
                                <th class="px-6 py-3 text-center">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="filtered.length === 0"><td colspan="5" class="px-6 py-12 text-center text-sm text-gray-400">ไม่พบรายการไปราชการ</td></tr>
                            <tr v-for="(t, i) in filtered" :key="i" class="cursor-pointer text-sm text-gray-700 hover:bg-indigo-50/50" @click="detail = t">
                                <td class="px-6 py-3 font-medium text-gray-900">{{ t.name }}</td>
                                <td class="px-6 py-3">{{ t.title }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ t.destination }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ t.start }} – {{ t.end }}</td>
                                <td class="px-6 py-3 text-center">
                                    <StatusBadge :status="t.status" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- รายละเอียดไปราชการ -->
        <Modal :show="detail !== null" @close="detail = null">
            <div v-if="detail" class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">{{ detail.name }}</h2>
                <StatusBadge :status="detail.status" class="mt-1" />
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex gap-3"><dt class="w-28 shrink-0 text-gray-400">เรื่อง</dt><dd class="text-gray-700">{{ detail.title }}</dd></div>
                    <div class="flex gap-3"><dt class="w-28 shrink-0 text-gray-400">สถานที่ไป</dt><dd class="text-gray-700">{{ detail.destination }}</dd></div>
                    <div class="flex gap-3"><dt class="w-28 shrink-0 text-gray-400">วัตถุประสงค์</dt><dd class="whitespace-pre-line text-gray-700">{{ detail.purpose || '—' }}</dd></div>
                    <div class="flex gap-3"><dt class="w-28 shrink-0 text-gray-400">ช่วงวันที่</dt><dd class="text-gray-700">{{ detail.start }} – {{ detail.end }}</dd></div>
                </dl>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="detail = null">ปิด</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
