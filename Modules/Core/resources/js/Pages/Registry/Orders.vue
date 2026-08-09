<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ orders: { type: Array, default: () => [] } });

const flash = computed(() => usePage().props.flash ?? {});

const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.orders;
    return props.orders.filter(
        (o) => o.title.toLowerCase().includes(q) || (o.number ?? '').toLowerCase().includes(q),
    );
});

const thaiDate = (d) =>
    d ? new Date(d).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' }) : '—';

const detail = ref(null);
</script>

<template>
    <Head title="ทะเบียนคำสั่ง" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ทะเบียนคำสั่ง</h2>
                <TextInput v-model="search" type="text" placeholder="ค้นหาเลขที่ / เรื่องคำสั่ง" class="w-64" />
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-3 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ flash.success }}</div>
                <p class="text-sm text-gray-500">ทั้งหมด {{ filtered.length }} รายการ</p>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    <th class="px-4 py-3 text-center">แก้ไข</th>
                                    <th class="px-4 py-3">เลขที่คำสั่ง</th>
                                    <th class="px-4 py-3">เรื่อง</th>
                                    <th class="px-4 py-3">ทั้งนี้ตั้งแต่วันที่</th>
                                    <th class="px-4 py-3">สั่ง ณ วันที่</th>
                                    <th class="px-4 py-3 text-center">ไฟล์แนบ</th>
                                    <th class="px-4 py-3">เจ้าของเรื่อง</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-if="filtered.length === 0"><td colspan="7" class="px-6 py-12 text-center text-sm text-gray-400">ไม่พบคำสั่ง</td></tr>
                                <tr v-for="(o, i) in filtered" :key="i" class="text-sm text-gray-700 hover:bg-indigo-50/50">
                                    <td class="px-4 py-3 text-center">
                                        <Link v-if="o.can_edit" :href="route('saraban.orders.attach-form', o.id)" class="text-indigo-600 hover:text-indigo-800" title="แก้ไข/แนบไฟล์"></Link>
                                        <span v-else class="text-gray-300">—</span>
                                    </td>
                                    <td class="cursor-pointer whitespace-nowrap px-4 py-3 font-semibold text-indigo-700" @click="detail = o">{{ o.number ?? '—' }}</td>
                                    <td class="cursor-pointer px-4 py-3 font-medium text-gray-900" @click="detail = o">{{ o.title }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-500">{{ o.effective_thai ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-500">{{ o.order_thai ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span v-if="o.has_file" class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">มีไฟล์</span>
                                        <span v-else class="text-xs text-gray-300">ไม่มีไฟล์</span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ o.owner ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- รายละเอียดคำสั่ง -->
        <Modal :show="detail !== null" @close="detail = null">
            <div v-if="detail" class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">{{ detail.title }}</h2>
                <div class="mt-1 flex flex-wrap gap-2 text-xs">
                    <span v-if="detail.number" class="rounded-full bg-indigo-50 px-2.5 py-0.5 font-mono font-medium text-indigo-700">{{ detail.number }}</span>
                </div>
                <dl class="mt-3 space-y-1.5 text-sm">
                    <div class="flex gap-2"><dt class="w-32 shrink-0 text-gray-400">ทั้งนี้ตั้งแต่วันที่</dt><dd class="text-gray-700">{{ detail.effective_thai ?? '—' }}</dd></div>
                    <div class="flex gap-2"><dt class="w-32 shrink-0 text-gray-400">สั่ง ณ วันที่</dt><dd class="text-gray-700">{{ detail.order_thai ?? '—' }}</dd></div>
                    <div class="flex gap-2"><dt class="w-32 shrink-0 text-gray-400">เจ้าของเรื่อง</dt><dd class="text-gray-700">{{ detail.owner || detail.creator || '—' }}</dd></div>
                </dl>
                <div v-if="detail.files && detail.files.length" class="mt-4 flex flex-wrap gap-2">
                    <a v-for="(f, i) in detail.files" :key="f.url ?? i" :href="f.url" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-1.5 text-sm font-medium text-indigo-700 hover:bg-indigo-100">{{ f.name }}</a>
                </div>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="detail = null">ปิด</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
