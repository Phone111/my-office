<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    users: { type: Array, default: () => [] },
});

const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.users;
    return props.users.filter(
        (u) =>
            u.name.toLowerCase().includes(q) ||
            (u.phone ?? '').toLowerCase().includes(q) ||
            (u.position ?? '').toLowerCase().includes(q) ||
            (u.group ?? '').toLowerCase().includes(q),
    );
});

const detail = ref(null);
</script>

<template>
    <Head title="สมุดโทรศัพท์" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">สมุดโทรศัพท์</h2>
                <TextInput v-model="search" type="text" placeholder="ค้นหาชื่อ / ตำแหน่ง / เบอร์" class="w-64" />
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-3 px-4 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-500">บุคลากร {{ filtered.length }} คน</p>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-[15px]">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <th class="px-5 py-3 w-12 text-center">ที่</th>
                                    <th class="px-5 py-3">ชื่อ - นามสกุล</th>
                                    <th class="px-5 py-3">ตำแหน่ง</th>
                                    <th class="px-5 py-3">กลุ่ม / สังกัด</th>
                                    <th class="px-5 py-3 text-right">โทรศัพท์</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="filtered.length === 0">
                                    <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-400">ไม่พบรายชื่อ</td>
                                </tr>
                                <tr
                                    v-for="(u, i) in filtered"
                                    :key="i"
                                    class="cursor-pointer border-t border-gray-50 hover:bg-indigo-50/50"
                                    :class="i % 2 ? 'bg-gray-50/40' : 'bg-white'"
                                    @click="detail = u"
                                >
                                    <td class="px-5 py-3.5 text-center text-gray-400">{{ i + 1 }}</td>
                                    <td class="px-5 py-3.5 font-medium text-gray-900">{{ u.name }}</td>
                                    <td class="px-5 py-3.5 text-gray-600">{{ u.position ?? '—' }}</td>
                                    <td class="px-5 py-3.5 text-gray-600">{{ u.group ?? '—' }}</td>
                                    <td class="px-5 py-3.5 text-right">
                                        <a v-if="u.phone" :href="`tel:${u.phone}`" class="font-semibold text-emerald-600 hover:underline" @click.stop>{{ u.phone }}</a>
                                        <span v-else class="text-gray-300">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- รายละเอียดบุคลากร -->
        <Modal :show="detail !== null" @close="detail = null">
            <div v-if="detail" class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">{{ detail.name }}</h2>
                <p class="text-sm text-gray-400">{{ detail.position || '—' }}<span v-if="detail.group"> · {{ detail.group }}</span></p>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex gap-3">
                        <dt class="w-24 shrink-0 text-gray-400">โทรศัพท์</dt>
                        <dd>
                            <a v-if="detail.phone" :href="`tel:${detail.phone}`" class="font-semibold text-emerald-600 hover:underline">{{ detail.phone }}</a>
                            <span v-else class="text-gray-400">—</span>
                        </dd>
                    </div>
                    <div class="flex gap-3">
                        <dt class="w-24 shrink-0 text-gray-400">อีเมล</dt>
                        <dd>
                            <a v-if="detail.email" :href="`mailto:${detail.email}`" class="text-indigo-600 hover:underline">{{ detail.email }}</a>
                            <span v-else class="text-gray-400">—</span>
                        </dd>
                    </div>
                </dl>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="detail = null">ปิด</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
