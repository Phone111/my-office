<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    groupName: { type: String, default: null },
});

const page = usePage();
const flash = computed(() => page.props.flash?.success);

// ตัวเลือกที่ผู้ใช้กำลังเลือกอยู่ในแต่ละแถว (ก่อนกดบันทึก)
const choice = reactive(
    Object.fromEntries(props.rows.map((r) => [r.id, r.is_clerk])),
);

const saving = reactive({});

function save(row) {
    saving[row.id] = true;
    router.post(
        route('saraban.group.delegates.save'),
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
    <Head title="จัดการผู้ปฏิบัติงานแทน" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    + จัดการบุคลากรที่ปฏิบัติหน้าที่เจ้าหน้าที่ธุรการกลุ่ม
                </h2>
                <p class="text-xs text-gray-400">{{ groupName ?? 'กลุ่มของฉัน' }} · แต่งตั้งสมาชิกให้ทำหน้าที่ธุรการกลุ่ม</p>
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
                                    <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-400">ไม่มีสมาชิกในกลุ่ม</td>
                                </tr>
                                <tr v-for="(r, i) in rows" :key="r.id" class="text-sm text-gray-700 hover:bg-gray-50">
                                    <td class="px-4 py-3 text-center text-gray-500">{{ i + 1 }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ r.name }}</td>
                                    <td class="px-4 py-3">
                                        <span v-if="r.is_clerk" class="font-medium text-indigo-600">จนท.ธุรการกลุ่ม</span>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-5">
                                            <label class="inline-flex items-center gap-1.5">
                                                <input type="radio" :value="true" v-model="choice[r.id]" class="text-indigo-600 focus:ring-indigo-500" />
                                                <span>จนท.ธุรการกลุ่ม</span>
                                            </label>
                                            <label class="inline-flex items-center gap-1.5">
                                                <input type="radio" :value="false" v-model="choice[r.id]" class="text-gray-500 focus:ring-gray-400" />
                                                <span>ไม่ปฏิบัติหน้าที่</span>
                                            </label>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            type="button"
                                            :disabled="saving[r.id] || choice[r.id] === r.is_clerk"
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
                <p class="text-xs text-gray-400">จำนวน {{ rows.length }} รายการ · กดเลือกสถานะแล้วกด "บันทึก" เพื่อแต่งตั้ง/ถอนหน้าที่</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
