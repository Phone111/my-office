<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    unitName: { type: String, default: null },
    schools: { type: Array, default: () => [] },
    selectedUnit: { type: Number, default: 0 },
    canPickSchool: { type: Boolean, default: false },
});

const flash = computed(() => usePage().props.flash?.success);
const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.rows;
    return props.rows.filter((r) => (r.name ?? '').toLowerCase().includes(q));
});

const pickSchool = (e) => router.get(route('school-clerks.index'), { unit: e.target.value }, { preserveState: true, preserveScroll: true });
const toggle = (r) => router.post(route('school-clerks.save'), { user_id: r.id, acts: !r.is_clerk }, { preserveScroll: true });
</script>

<template>
    <Head title="ตั้งค่าสารบรรณโรงเรียน" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">ตั้งค่าสารบรรณโรงเรียน</h2>
                    <p class="text-xs text-gray-400">แต่งตั้งบุคลากรในโรงเรียนเป็น “สารบรรณโรงเรียน” (รับ-ส่งหนังสือของโรงเรียน)</p>
                </div>
                <input v-model="search" type="text" placeholder="ค้นหาชื่อ" class="w-48 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <!-- ตัวเลือกโรงเรียน (admin/area_admin) -->
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white px-5 py-3 shadow-sm ring-1 ring-gray-100">
                    <div class="text-sm">
                        <span class="text-gray-400">โรงเรียน:</span>
                        <span class="ml-1 font-semibold text-gray-800">{{ unitName ?? '— (บัญชีของคุณยังไม่สังกัดโรงเรียน) —' }}</span>
                    </div>
                    <select v-if="canPickSchool" :value="selectedUnit" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="pickSchool">
                        <option v-for="s in schools" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3">ชื่อ - สกุล</th>
                                <th class="px-4 py-3">ตำแหน่ง</th>
                                <th class="px-4 py-3 text-center">สารบรรณโรงเรียน</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="filtered.length === 0"><td colspan="3" class="px-6 py-10 text-center text-sm text-gray-400">ไม่มีบุคลากรในโรงเรียนนี้</td></tr>
                            <tr v-for="r in filtered" :key="r.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ r.name }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ r.position ?? '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <button
                                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold transition"
                                        :class="r.is_clerk ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
                                        @click="toggle(r)"
                                    >
                                        {{ r.is_clerk ? 'เป็นสารบรรณ' : 'แต่งตั้ง' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
