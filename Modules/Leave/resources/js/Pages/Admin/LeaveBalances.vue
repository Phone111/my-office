<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    year: { type: Number, required: true },
    types: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
});
const flash = computed(() => usePage().props.flash?.success);

// state แก้ไข entitled ต่อ user/type
const edited = reactive({});
props.rows.forEach((r) => {
    props.types.forEach((t) => {
        edited[`${r.id}-${t.id}`] = r.types[t.id]?.entitled ?? t.default_days;
    });
});

const form = useForm({ entries: [] });
const save = () => {
    form.entries = [];
    props.rows.forEach((r) => {
        props.types.forEach((t) => {
            form.entries.push({ user_id: r.id, leave_type_id: t.id, entitled_days: Number(edited[`${r.id}-${t.id}`]) || 0 });
        });
    });
    form.post(route('leave.balances.save'), { preserveScroll: true });
};
</script>

<template>
    <Head title="บันทึกวันลาสะสม" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">บันทึกวันลาสะสม</h2>
                    <p class="text-xs text-gray-400">ปีงบประมาณ {{ year }} — ตั้งวันลาที่มีสิทธิ์ (สะสม + ประจำปี) ต่อบุคคล</p>
                </div>
                <button :disabled="form.processing" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50" @click="save">บันทึกทั้งหมด</button>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="w-full text-sm">
                        <thead class="border-b border-gray-100 bg-gray-50 text-xs text-gray-500">
                            <tr>
                                <th class="px-5 py-3 text-left font-medium">บุคลากร</th>
                                <th v-for="t in types" :key="t.id" class="px-3 py-3 text-center font-medium">
                                    {{ t.name }}<br /><span class="font-normal text-gray-400">มีสิทธิ์ / ใช้ไป / คงเหลือ</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="r in rows" :key="r.id" class="hover:bg-gray-50">
                                <td class="px-5 py-2 font-medium text-gray-800">{{ r.name }}</td>
                                <td v-for="t in types" :key="t.id" class="px-3 py-2 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <input
                                            v-model="edited[`${r.id}-${t.id}`]"
                                            type="number"
                                            min="0"
                                            step="0.5"
                                            class="w-16 rounded-md border-gray-300 py-1 text-center text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <span class="text-xs text-gray-400">/ {{ r.types[t.id]?.used ?? 0 }} / <span class="font-semibold text-emerald-600">{{ (Number(edited[`${r.id}-${t.id}`]) || 0) - (r.types[t.id]?.used ?? 0) }}</span></span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!rows.length"><td :colspan="types.length + 1" class="px-5 py-8 text-center text-gray-400">ไม่มีบุคลากร</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-400">* "มีสิทธิ์" = วันลาสะสมจากปีก่อน + ลาประจำปี (เช่น ลาพักผ่อน = สะสม + 10) — แก้ตัวเลขแล้วกด "บันทึกทั้งหมด"</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
