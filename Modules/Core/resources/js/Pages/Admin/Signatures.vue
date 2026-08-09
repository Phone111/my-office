<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    users: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});
const sigUrl = (path) => `/storage/${path}`;

const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.users;
    return props.users.filter((u) => u.name.toLowerCase().includes(q));
});

const acting = ref(null); // ผู้ใช้ที่กำลังจัดการลายเซ็น
const form = useForm({ user_id: null, file: null });

const openManage = (u) => {
    form.reset();
    form.clearErrors();
    form.user_id = u.id;
    acting.value = u;
};
const close = () => (acting.value = null);

const submit = () =>
    form.post(route('admin.signatures.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: close,
    });

const removeSignature = () => {
    if (acting.value?.signature_id && confirm('ลบลายเซ็นนี้?')) {
        router.delete(route('admin.signatures.destroy', acting.value.signature_id), {
            preserveScroll: true,
            onSuccess: close,
        });
    }
};
</script>

<template>
    <Head title="จัดการลายเซ็นบุคลากร" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">จัดการลายเซ็นของบุคลากร</h2>
                <TextInput v-model="search" type="text" placeholder="ระบุชื่อ หรือ ชื่อสกุล" class="w-56" />
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ flash.success }}
                </div>
                <div v-if="flash.error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ flash.error }}
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3">ชื่อ - นามสกุล</th>
                                <th class="px-6 py-3">กลุ่ม</th>
                                <th class="px-6 py-3">กลุ่มสาระ</th>
                                <th class="px-6 py-3 text-center">ลายเซ็น</th>
                                <th class="px-6 py-3 text-right">เพิ่ม/แก้ไข ลายเซ็น</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="filtered.length === 0">
                                <td colspan="5" class="px-6 py-12">
                                    <EmptyState title="ไม่พบบุคลากร" />
                                </td>
                            </tr>
                            <tr v-for="u in filtered" :key="u.id" class="text-sm text-gray-700 hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ u.name }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ u.group ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ u.department ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center">
                                        <img v-if="u.signature_path" :src="sigUrl(u.signature_path)" alt="ลายเซ็น" class="h-12 max-w-[160px] object-contain" />
                                        <span v-else class="text-xs text-gray-300">ยังไม่มีลายเซ็น</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button class="font-medium text-indigo-600 hover:text-indigo-800" @click="openManage(u)">จัดการลายเซ็น</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal จัดการลายเซ็น -->
        <Modal :show="acting !== null" @close="close">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">จัดการลายเซ็น</h2>
                <p v-if="acting" class="mt-1 text-sm text-gray-500">{{ acting.name }}</p>

                <div v-if="acting?.signature_path" class="mt-4 rounded-lg border border-gray-100 bg-gray-50 p-3 text-center">
                    <p class="mb-2 text-xs text-gray-400">ลายเซ็นปัจจุบัน</p>
                    <img :src="sigUrl(acting.signature_path)" alt="ลายเซ็น" class="mx-auto h-16 max-w-full object-contain" />
                </div>

                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <div>
                        <InputLabel for="file" :value="acting?.signature_path ? 'อัปโหลดลายเซ็นใหม่ (แทนที่)' : 'อัปโหลดลายเซ็น'" />
                        <input
                            id="file"
                            type="file"
                            accept="image/*"
                            class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                            @input="form.file = $event.target.files[0]"
                        />
                        <p class="mt-1 text-xs text-gray-400">รองรับไฟล์รูปภาพ (PNG/JPG) ขนาดไม่เกิน 2MB</p>
                        <InputError :message="form.errors.file" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <DangerButton v-if="acting?.signature_id" type="button" @click="removeSignature">ลบลายเซ็น</DangerButton>
                        <span v-else />
                        <div class="flex gap-3">
                            <SecondaryButton type="button" @click="close">ยกเลิก</SecondaryButton>
                            <PrimaryButton :disabled="form.processing || !form.file">บันทึก</PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
