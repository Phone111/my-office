<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    byType: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
    students: { type: Array, default: () => [] },
    bySchool: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] },
    activeType: { type: String, default: null },
    unitName: { type: String, default: '' },
    units: { type: Array, default: () => [] },
    selectedUnit: { type: Number, default: null },
    canPickSchool: { type: Boolean, default: false },
    isAreaMode: { type: Boolean, default: false },
});

const pickUnit = ref(props.selectedUnit ?? '');
const go = (params) => router.get(route('students.disability'), params, { preserveScroll: true });
const changeUnit = () => go({ unit: pickUnit.value || undefined });
const filterType = (t) => go({ unit: props.selectedUnit || undefined, type: props.activeType === t ? undefined : t });
const print = () => window.print();
</script>

<template>
    <Head title="นักเรียนพิการเรียนรวม" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800">นักเรียนพิการเรียนรวม</h2>
                <Link :href="route('students.index')" class="text-sm text-gray-500 hover:text-gray-700">← ข้อมูลนักเรียน</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
                <!-- แถบเครื่องมือ -->
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 print:hidden">
                    <div class="flex items-center gap-2">
                        <select v-if="canPickSchool" v-model="pickUnit" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="changeUnit">
                            <option value="">ทั้งเขต (ทุกโรงเรียน)</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                        <span class="text-sm font-semibold text-gray-700">{{ unitName }}</span>
                    </div>
                    <button type="button" class="rounded-md border border-gray-300 px-4 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="print">🖨 พิมพ์</button>
                </div>

                <!-- สรุปยอด -->
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-indigo-700">{{ total }}</span>
                        <span class="text-sm text-gray-500">คน — นักเรียนพิการเรียนรวม</span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            v-for="t in byType"
                            :key="t.type"
                            type="button"
                            class="rounded-full px-3 py-1 text-xs font-semibold transition"
                            :class="activeType === t.type ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100'"
                            @click="filterType(t.type)"
                        >{{ t.type }} · {{ t.count }}</button>
                        <span v-if="!byType.length" class="text-sm text-gray-400">ไม่มีข้อมูลนักเรียนพิการในขอบเขตนี้</span>
                    </div>
                </div>

                <!-- สรุปรายโรงเรียน (โหมดทั้งเขต) -->
                <div v-if="isAreaMode && bySchool.length" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-5 py-2.5 text-sm font-semibold text-gray-700">สรุปรายโรงเรียน</div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(s, i) in bySchool" :key="i" class="hover:bg-gray-50">
                                <td class="px-5 py-2 text-gray-400">{{ i + 1 }}</td>
                                <td class="px-5 py-2 font-medium text-gray-800">{{ s.school ?? '—' }}</td>
                                <td class="px-5 py-2 text-right font-semibold text-indigo-700">{{ s.count }} คน</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- รายชื่อนักเรียน -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-5 py-2.5 text-sm font-semibold text-gray-700">
                        รายชื่อ <span v-if="activeType" class="text-indigo-600">· {{ activeType }}</span> ({{ students.length }})
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-medium text-gray-500">
                                    <th class="px-4 py-2 text-center">ที่</th>
                                    <th v-if="isAreaMode" class="px-4 py-2">โรงเรียน</th>
                                    <th class="px-4 py-2">เลขประจำตัว</th>
                                    <th class="px-4 py-2">ชื่อ-สกุล</th>
                                    <th class="px-4 py-2">ชั้น/ห้อง</th>
                                    <th class="px-4 py-2">ประเภทความพิการ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-if="!students.length"><td :colspan="isAreaMode ? 6 : 5" class="px-6 py-10 text-center text-sm text-gray-400">ไม่มีรายชื่อ</td></tr>
                                <tr v-for="(s, i) in students" :key="s.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-center text-gray-400">{{ i + 1 }}</td>
                                    <td v-if="isAreaMode" class="px-4 py-2 text-gray-500">{{ s.school ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ s.student_code ?? '—' }}</td>
                                    <td class="px-4 py-2 font-medium text-gray-900">{{ s.fullname }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ s.grade }}{{ s.room ? '/'+s.room : '' }}</td>
                                    <td class="px-4 py-2"><span class="rounded-full bg-violet-50 px-2 py-0.5 text-xs font-medium text-violet-700">{{ s.disability }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
