<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import Modal from '@/Components/Modal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    approverOptions: { type: Array, default: () => [] }, // [{role,label,users:[{id,name}]}]
});
const flash = computed(() => usePage().props.flash?.success);

// ===== เสนอแฟ้ม (เลือกผู้รับ) =====
const proposing = ref(null);
const proposeForm = useForm({ approver_id: null });
const defaultApprover = () => {
    const dir = props.approverOptions.find((g) => g.role === 'director');
    return dir?.users?.[0]?.id ?? props.approverOptions[0]?.users?.[0]?.id ?? null;
};
const openPropose = (row) => {
    proposing.value = row;
    proposeForm.clearErrors();
    proposeForm.approver_id = defaultApprover();
};
const submitPropose = () => {
    proposeForm.post(route('saraban.documents.propose', proposing.value.id), {
        preserveScroll: true,
        onSuccess: () => (proposing.value = null),
    });
};

// ===== แนบไฟล์ =====
const fileInput = ref(null);
const attachTarget = ref(null);
const pickFile = (row) => {
    attachTarget.value = row;
    fileInput.value?.click();
};
const onFilePicked = (e) => {
    const file = e.target.files[0];
    if (!file || !attachTarget.value) return;
    router.post(route('saraban.documents.attach', attachTarget.value.id), { file }, {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => {
            e.target.value = '';
            attachTarget.value = null;
        },
    });
};

const removeDraft = (row) => {
    if (confirm(`ลบร่าง "${row.title}" ?`)) router.delete(route('saraban.documents.destroy', row.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="รอเสนอแฟ้ม" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">เอกสารรอเสนอแฟ้ม (ร่าง)</h2>
                <Link :href="route('saraban.documents.create')" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">+ เขียนบันทึกใหม่</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>
                <p class="text-sm text-gray-500">บันทึกที่ยังไม่ได้เสนอ — กด "เสนอแฟ้ม" เพื่อเลือกผู้รับและส่ง</p>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3">ประเภท</th>
                                <th class="px-4 py-3">เรื่อง</th>
                                <th class="px-4 py-3">วันที่บันทึก</th>
                                <th class="px-4 py-3 text-center">สถานะ</th>
                                <th class="px-4 py-3 text-right">การปฏิบัติ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="!rows.length"><td colspan="5" class="px-4 py-10"><EmptyState title="ไม่มีร่างรอเสนอ" description="เขียนบันทึกใหม่ แล้วร่างจะมาอยู่ที่นี่" /></td></tr>
                            <tr v-for="r in rows" :key="r.id" class="hover:bg-gray-50/60">
                                <td class="px-4 py-3 text-gray-600">{{ r.category_label }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800">
                                    {{ r.title }}
                                    <span v-if="r.has_file" class="ml-1 text-xs text-emerald-600">· มีไฟล์แนบ</span>
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ r.created_thai }}</td>
                                <td class="px-4 py-3 text-center"><span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">รอเสนอแฟ้ม</span></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        <button class="font-semibold text-indigo-600 hover:underline" @click="openPropose(r)">เสนอแฟ้ม</button>
                                        <button class="text-gray-500 hover:underline" @click="pickFile(r)">แนบไฟล์</button>
                                        <Link :href="route('saraban.documents.edit', r.id)" class="text-gray-500 hover:underline">แก้ไข</Link>
                                        <button class="text-rose-500 hover:underline" @click="removeDraft(r)">ลบ</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <input ref="fileInput" type="file" class="hidden" @change="onFilePicked" />

        <!-- Popup เลือกผู้รับเสนอ (เหมือนระบบเดิม) -->
        <Modal :show="!!proposing" max-width="lg" @close="proposing = null">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-800">เสนอแฟ้ม</h3>
                <p class="mt-0.5 text-sm text-gray-500">{{ proposing?.title }}</p>

                <p class="mb-2 mt-5 text-center text-sm font-semibold text-rose-600">เลือกเสนอตามที่ต้องการ 1 รายการ</p>
                <div v-if="approverOptions.length" class="space-y-3 rounded-xl bg-gray-50 p-4">
                    <div v-for="g in approverOptions" :key="g.role">
                        <p class="text-xs font-medium text-gray-500">{{ g.label }}</p>
                        <div class="ml-1 flex flex-wrap gap-x-5 gap-y-1">
                            <label v-for="u in g.users" :key="u.id" class="inline-flex cursor-pointer items-center gap-1.5 text-sm text-gray-700">
                                <input v-model="proposeForm.approver_id" type="radio" :value="u.id" class="text-indigo-600 focus:ring-indigo-500" />
                                {{ u.name }}
                            </label>
                        </div>
                    </div>
                </div>
                <p v-else class="rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-600">ไม่พบผู้รับเสนอในหน่วยงาน</p>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-200" @click="proposing = null">ยกเลิก</button>
                    <button
                        type="button"
                        :disabled="!proposeForm.approver_id || proposeForm.processing"
                        class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                        @click="submitPropose"
                    >
                        ยืนยันเสนอแฟ้ม
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
