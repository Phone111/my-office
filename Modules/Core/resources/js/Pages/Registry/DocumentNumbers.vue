<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ counters: { type: Array, default: () => [] } });

const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.counters;
    return props.counters.filter(
        (c) => (c.book ?? '').toLowerCase().includes(q) || String(c.year ?? '').includes(q),
    );
});

const detail = ref(null);
</script>

<template>
    <Head title="ทะเบียนลำดับเลขเอกสาร" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ทะเบียนลำดับเลขเอกสาร</h2>
                <TextInput v-model="search" type="text" placeholder="ค้นหาประเภท / ปี" class="w-56" />
            </div>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-3 px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3">ประเภทเอกสาร</th>
                                <th class="px-6 py-3 text-center">ปี</th>
                                <th class="px-6 py-3 text-center">เลขล่าสุด</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="filtered.length === 0"><td colspan="3" class="px-6 py-12 text-center text-sm text-gray-400">ไม่พบข้อมูล</td></tr>
                            <tr v-for="(c, i) in filtered" :key="i" class="cursor-pointer text-sm text-gray-700 hover:bg-indigo-50/50" @click="detail = c">
                                <td class="px-6 py-3 font-medium text-gray-900">{{ c.book }}</td>
                                <td class="px-6 py-3 text-center text-gray-500">{{ c.year }}</td>
                                <td class="px-6 py-3 text-center font-semibold text-indigo-600">{{ c.last_no }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- รายละเอียดลำดับเลข -->
        <Modal :show="detail !== null" @close="detail = null">
            <div v-if="detail" class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">{{ detail.book }}</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between border-b border-gray-50 pb-2"><dt class="text-gray-500">ปี</dt><dd class="font-semibold text-gray-900">{{ detail.year }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">เลขล่าสุดที่ออก</dt><dd class="font-semibold text-indigo-600">{{ detail.last_no }}</dd></div>
                </dl>
                <p class="mt-3 text-xs text-gray-400">เลขถัดไปที่จะออกคือ {{ Number(detail.last_no) + 1 }}</p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="detail = null">ปิด</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
