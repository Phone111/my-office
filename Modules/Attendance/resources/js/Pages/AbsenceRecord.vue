<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    date: { type: String, default: '' },
    absentees: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] }, // [{value,label}]
});

const flash = computed(() => usePage().props.flash ?? {});

const selectedDate = ref(props.date);
const rows = ref(props.absentees.map((a) => ({ ...a })));
watch(
    () => props.absentees,
    (v) => (rows.value = v.map((a) => ({ ...a }))),
);

const reload = () => router.get(route('attendance.absence'), { date: selectedDate.value }, { preserveScroll: true });

const processing = ref(false);
const save = () => {
    router.post(
        route('attendance.absence.store'),
        {
            date: selectedDate.value,
            records: rows.value.map((r) => ({ user_id: r.id, status: r.status, note: r.note })),
        },
        {
            preserveScroll: true,
            onStart: () => (processing.value = true),
            onFinish: () => (processing.value = false),
        },
    );
};
</script>

<template>
    <Head title="บันทึกผู้ไม่ลงเวลาวันนี้" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">บันทึกผู้ไม่ลงเวลา</h2>
                <input v-model="selectedDate" type="date" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="reload" />
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ flash.success }}
                </div>

                <p class="text-sm text-gray-500">เลือกสถานะ/ระบุเหตุผลของผู้ที่ยังไม่ได้ลงเวลา (เว้นว่าง = ไม่บันทึก)</p>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3">ชื่อ - นามสกุล</th>
                                <th class="px-6 py-3">ตำแหน่ง</th>
                                <th class="px-6 py-3 w-44">สถานะ</th>
                                <th class="px-6 py-3">หมายเหตุ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="rows.length === 0"><td colspan="4" class="px-6 py-12 text-center text-sm text-gray-400">ลงเวลาครบทุกคนแล้ว </td></tr>
                            <tr v-for="r in rows" :key="r.id" class="text-sm text-gray-700">
                                <td class="px-6 py-3 font-medium text-gray-900">{{ r.name }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ r.position ?? '—' }}</td>
                                <td class="px-6 py-3">
                                    <select v-model="r.status" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">—</option>
                                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                                    </select>
                                </td>
                                <td class="px-6 py-3">
                                    <TextInput v-model="r.note" type="text" class="block w-full" placeholder="ระบุเพิ่มเติม (ถ้ามี)" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="rows.length" class="flex justify-end">
                    <PrimaryButton :disabled="processing" @click="save">บันทึก</PrimaryButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
