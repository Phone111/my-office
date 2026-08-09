<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    users: { type: Array, default: () => [] },
    standingSummary: { type: Object, default: () => ({}) },
    total: { type: Number, default: 0 },
    withProfile: { type: Number, default: 0 },
});

const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.users;
    return props.users.filter((u) => u.name.toLowerCase().includes(q) || (u.position ?? '').toLowerCase().includes(q));
});
</script>

<template>
    <Head title="ทะเบียนประวัติบุคลากร" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">ทะเบียนประวัติบุคลากร (ก.พ.7)</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
                <!-- สรุป -->
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                        <p class="text-sm text-gray-500">บุคลากรทั้งหมด</p>
                        <p class="mt-1 text-3xl font-bold text-indigo-600">{{ total }}</p>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                        <p class="text-sm text-gray-500">บันทึกประวัติแล้ว</p>
                        <p class="mt-1 text-3xl font-bold text-emerald-600">{{ withProfile }}</p>
                        <p class="text-xs text-gray-400">ยังไม่บันทึก {{ total - withProfile }}</p>
                    </div>
                    <div class="col-span-2 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                        <p class="mb-2 text-sm text-gray-500">สรุปตามวิทยฐานะ</p>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="(count, standing) in standingSummary" :key="standing" class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                                {{ standing }} <span class="font-bold text-indigo-600">{{ count }}</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ตารางรายชื่อ -->
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <h3 class="text-base font-bold text-gray-800">รายชื่อบุคลากร ({{ filtered.length }})</h3>
                        <input v-model="search" type="text" placeholder="ค้นหาชื่อ/ตำแหน่ง..." class="rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500">
                                    <th class="px-3 py-2">ชื่อ-สกุล</th>
                                    <th class="px-3 py-2">ตำแหน่ง</th>
                                    <th class="px-3 py-2">วิทยฐานะ</th>
                                    <th class="px-3 py-2">วันบรรจุ</th>
                                    <th class="px-3 py-2 text-center">ประวัติ</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="u in filtered" :key="u.id" class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-3 py-2 font-medium text-gray-800">{{ u.name }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ u.position ?? '—' }}</td>
                                    <td class="px-3 py-2">
                                        <span v-if="u.standing" class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">{{ u.standing }}</span>
                                        <span v-else class="text-gray-300">—</span>
                                    </td>
                                    <td class="px-3 py-2 text-gray-600">{{ u.appointed ?? '—' }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <span v-if="u.has_profile" class="text-emerald-500">●</span>
                                        <span v-else class="text-gray-300">○</span>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <Link :href="route('personnel-records.show', u.id)" class="text-sm font-medium text-indigo-600 hover:underline">ดู/แก้ไข ›</Link>
                                    </td>
                                </tr>
                                <tr v-if="!filtered.length">
                                    <td colspan="6" class="px-3 py-6 text-center text-gray-400">ไม่พบบุคลากร</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
