<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    activeYear: { type: Number, required: true },
    systemYear: { type: Number, required: true },
    isCustom: { type: Boolean, default: false },
    byYear: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});
const baht = (n) => Number(n).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const form = useForm({ year: props.activeYear });
const save = () => form.post(route('executive.plan-year.set'), { preserveScroll: true });
const openNext = () => {
    if (!confirm(`เปิดปีจัดทำแผนใหม่ พ.ศ. ${props.activeYear + 1}?`)) return;
    router.post(route('executive.plan-year.set'), { year: props.activeYear + 1 }, { preserveScroll: true });
};
const useSystem = () => router.post(route('executive.plan-year.system'), {}, { preserveScroll: true });
</script>

<template>
    <Head title="จัดการปีจัดทำแผน" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">จัดการปีจัดทำแผน</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ flash.success }}</div>

                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-sm text-gray-500">ปีงบประมาณที่ใช้จัดทำแผน/โครงการขณะนี้</p>
                            <p class="mt-1 text-3xl font-bold text-indigo-700">พ.ศ. {{ activeYear }}</p>
                            <p class="mt-1 text-xs" :class="isCustom ? 'text-amber-600' : 'text-gray-400'">
                                {{ isCustom ? 'ตั้งค่าเอง · ปีระบบจริงคือ พ.ศ. ' + systemYear : 'ใช้ปีงบประมาณตามระบบอัตโนมัติ' }}
                            </p>
                        </div>
                        <button type="button" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" @click="openNext">
                            + เปิดปีใหม่ (พ.ศ. {{ activeYear + 1 }})
                        </button>
                    </div>

                    <div class="mt-6 border-t border-gray-100 pt-5">
                        <label class="text-sm font-semibold text-gray-700">ตั้งปีจัดทำแผน (พ.ศ.)</label>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <input v-model.number="form.year" type="number" min="2500" max="2700" class="w-36 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <PrimaryButton :disabled="form.processing" @click="save">บันทึกปีจัดทำแผน</PrimaryButton>
                            <button v-if="isCustom" type="button" class="text-sm font-medium text-gray-500 hover:text-gray-700" @click="useSystem">ใช้ปีตามระบบ (พ.ศ. {{ systemYear }})</button>
                        </div>
                        <InputError :message="form.errors.year" class="mt-2" />
                        <p class="mt-2 text-xs text-gray-400">เมื่อเพิ่มโครงการใหม่ ระบบจะเติมปีงบประมาณนี้ให้อัตโนมัติ</p>
                    </div>
                </div>

                <!-- สรุปงบแยกปี -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 bg-indigo-50/60 px-6 py-3 font-semibold text-gray-700">งบประมาณโครงการ (แยกตามปี)</div>
                    <EmptyState v-if="byYear.length === 0" title="ยังไม่มีโครงการ" />
                    <table v-else class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-2">ปีงบ</th>
                                <th class="px-6 py-2 text-center">จำนวนโครงการ</th>
                                <th class="px-6 py-2 text-right">งบจัดสรร</th>
                                <th class="px-6 py-2 text-right">เบิกจ่าย</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="g in byYear" :key="g.year" class="text-gray-700">
                                <td class="px-6 py-2 font-medium text-gray-900">{{ g.year }}</td>
                                <td class="px-6 py-2 text-center">{{ g.projects }}</td>
                                <td class="px-6 py-2 text-right tabular-nums">{{ baht(g.allocated) }}</td>
                                <td class="px-6 py-2 text-right tabular-nums text-indigo-600">{{ baht(g.disbursed) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
