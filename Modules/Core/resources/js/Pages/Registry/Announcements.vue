<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ items: { type: Array, default: () => [] } });

const flash = computed(() => usePage().props.flash ?? {});

const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.items;
    return props.items.filter(
        (n) => (n.title ?? '').toLowerCase().includes(q) || (n.ref ?? '').includes(q) || (n.author ?? '').toLowerCase().includes(q),
    );
});

const detail = ref(null);
</script>

<template>
    <Head title="ทะเบียนประกาศ" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ทะเบียนประกาศ</h2>
                <div class="flex items-center gap-3">
                    <TextInput v-model="search" type="text" placeholder="ค้นหา ฉบับที่ / เรื่อง / ผู้ประกาศ" class="w-64" />
                    <Link :href="route('reports.registry.announcements.create')">
                        <PrimaryButton>+ ออกประกาศ</PrimaryButton>
                    </Link>
                </div>
            </div>
        </template>
        <div class="py-12">
            <div class="mx-auto max-w-6xl space-y-3 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ flash.success }}</div>
                <p class="text-sm text-gray-500">ทั้งหมด {{ filtered.length }} รายการ</p>
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <EmptyState v-if="filtered.length === 0" :title="search ? 'ไม่พบประกาศ' : 'ยังไม่มีประกาศ'" />
                    <table v-else class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3 text-center">ฉบับที่</th>
                                <th class="px-6 py-3">เรื่อง</th>
                                <th class="px-6 py-3">ประกาศ ณ วันที่</th>
                                <th class="px-6 py-3">ผู้ออกประกาศ</th>
                                <th class="px-6 py-3 text-center">ไฟล์</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="n in filtered" :key="n.id" class="cursor-pointer text-sm text-gray-700 hover:bg-indigo-50/50" @click="detail = n">
                                <td class="px-6 py-3 text-center font-mono font-medium text-indigo-700">{{ n.ref }}</td>
                                <td class="px-6 py-3 font-medium text-gray-900">{{ n.title }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ n.announced_thai }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ n.author ?? '—' }}</td>
                                <td class="px-6 py-3 text-center text-gray-500">{{ n.attachments.length ? '' + n.attachments.length : '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- รายละเอียดประกาศ -->
        <Modal :show="detail !== null" @close="detail = null">
            <div v-if="detail" class="p-6">
                <p class="text-sm font-mono text-indigo-700">ประกาศฉบับที่ {{ detail.ref }}</p>
                <h2 class="mt-1 text-lg font-semibold text-gray-900">{{ detail.title }}</h2>
                <p class="mt-1 text-sm text-gray-500">ประกาศ ณ วันที่ {{ detail.announced_thai }} · โดย {{ detail.author || '—' }}</p>
                <div v-if="detail.attachments.length" class="mt-4 space-y-1">
                    <a v-for="(a, i) in detail.attachments" :key="a.url ?? i" :href="a.url" target="_blank" class="flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ a.name }}</a>
                </div>
                <p v-else class="mt-4 text-sm text-gray-400">ไม่มีไฟล์แนบ</p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="detail = null">ปิด</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
