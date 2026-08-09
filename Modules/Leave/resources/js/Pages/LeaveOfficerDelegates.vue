<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash?.success);

const choice = reactive(Object.fromEntries(props.rows.map((r) => [r.id, r.is_officer])));
const saving = reactive({});

function save(row) {
    saving[row.id] = true;
    router.post(
        route('leave.officer-delegates.save'),
        { user_id: row.id, acts: choice[row.id] },
        {
            preserveScroll: true,
            onFinish: () => {
                saving[row.id] = false;
            },
        },
    );
}
</script>

<template>
    <Head title="ผู้ปฏิบัติงานแทน (เจ้าหน้าที่วันลา)" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">+ จัดการบุคลากรผู้ปฏิบัติหน้าที่เจ้าหน้าที่วันลา</h2>
                <p class="text-xs text-gray-400">แต่งตั้งบุคลากรให้ทำหน้าที่ "เจ้าหน้าที่การลา" (กรณีเจ้าหน้าที่วันลาลา/ไปราชการ)</p>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">
                    {{ flash }}
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    <th class="px-4 py-3 text-center">ที่</th>
                                    <th class="px-4 py-3">ชื่อ - นามสกุล</th>
                                    <th class="px-4 py-3">ปฏิบัติหน้าที่</th>
                                    <th class="px-4 py-3 text-center">สถานะ</th>
                                    <th class="px-4 py-3 text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-if="rows.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-400">ไม่มีบุคลากร</td>
                                </tr>
                                <tr v-for="(r, i) in rows" :key="r.id" class="text-sm text-gray-700 hover:bg-gray-50">
                                    <td class="px-4 py-3 text-center text-gray-500">{{ i + 1 }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ r.name }}</td>
                                    <td class="px-4 py-3">
                                        <span v-if="r.is_officer" class="font-medium text-rose-600">เจ้าหน้าที่การลา</span>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <label class="inline-flex items-center gap-2">
                                            <input type="checkbox" v-model="choice[r.id]" class="rounded text-indigo-600 focus:ring-indigo-500" />
                                            <span>เจ้าหน้าที่การลา</span>
                                        </label>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            type="button"
                                            :disabled="saving[r.id] || choice[r.id] === r.is_officer"
                                            class="rounded-md bg-indigo-600 px-4 py-1.5 text-xs font-medium text-white shadow-sm transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-gray-300"
                                            @click="save(r)"
                                        >
                                            {{ saving[r.id] ? 'กำลังบันทึก…' : 'บันทึก' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <p class="text-xs text-gray-400">จำนวน {{ rows.length }} คน · ติ๊ก "เจ้าหน้าที่การลา" แล้วกด "บันทึก" เพื่อแต่งตั้ง/ถอนหน้าที่</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
