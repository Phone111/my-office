<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    positions: { type: Array, default: () => [] },
    types: { type: Array, default: () => [] }, // [{value, label}]
});

const flash = computed(() => usePage().props.flash ?? {});

const showForm = ref(false);
const editingId = ref(null);
const form = useForm({
    name: '',
    type: '',
    is_active: true,
});

const openCreate = () => {
    form.reset();
    form.clearErrors();
    form.is_active = true;
    editingId.value = null;
    showForm.value = true;
};
const openEdit = (p) => {
    form.clearErrors();
    editingId.value = p.id;
    form.name = p.name;
    form.type = p.type ?? '';
    form.is_active = p.is_active;
    showForm.value = true;
};
const submit = () => {
    const opts = { onSuccess: () => (showForm.value = false), preserveScroll: true };
    if (editingId.value) form.put(route('admin.positions.update', editingId.value), opts);
    else form.post(route('admin.positions.store'), opts);
};
const remove = (p) => {
    if (confirm(`ลบตำแหน่ง "${p.name}" ?`)) {
        router.delete(route('admin.positions.destroy', p.id), { preserveScroll: true });
    }
};
const move = (p, direction) => {
    router.post(route('admin.positions.move', p.id), { direction }, { preserveScroll: true });
};
</script>

<template>
    <Head title="จัดการตำแหน่ง" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">จัดการประเภทตำแหน่ง</h2>
                <PrimaryButton @click="openCreate">+ เพิ่มประเภทตำแหน่ง</PrimaryButton>
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
                                <th class="px-6 py-3 text-center">เลขที่</th>
                                <th class="px-6 py-3">ประเภทตำแหน่ง</th>
                                <th class="px-6 py-3">ประเภท</th>
                                <th class="px-6 py-3 text-center">สถานะ</th>
                                <th class="px-6 py-3 text-center">ลำดับ</th>
                                <th class="px-6 py-3 text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="positions.length === 0">
                                <td colspan="6" class="px-6 py-12">
                                    <EmptyState title="ยังไม่มีตำแหน่ง" />
                                </td>
                            </tr>
                            <tr v-for="(p, i) in positions" :key="p.id" class="text-sm text-gray-700 hover:bg-gray-50">
                                <td class="px-6 py-4 text-center text-gray-500">{{ i + 1 }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900">{{ p.name }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ p.type_label }}</td>
                                <td class="px-6 py-4 text-center">
                                    <StatusBadge :status="p.is_active ? 'active' : 'inactive'" :label="p.is_active ? 'เปิดการทำงาน' : 'ปิดการทำงาน'" />
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-indigo-600 disabled:opacity-30"
                                            :disabled="i === 0"
                                            title="เลื่อนขึ้น"
                                            @click="move(p, 'up')"
                                        >▲</button>
                                        <button
                                            class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-indigo-600 disabled:opacity-30"
                                            :disabled="i === positions.length - 1"
                                            title="เลื่อนลง"
                                            @click="move(p, 'down')"
                                        >▼</button>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <button class="font-medium text-indigo-600 hover:text-indigo-800" @click="openEdit(p)">แก้ไข</button>
                                        <button class="font-medium text-red-600 hover:text-red-800" @click="remove(p)">ลบ</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal เพิ่ม/แก้ไขตำแหน่ง -->
        <Modal :show="showForm" @close="showForm = false">
            <form class="space-y-5 p-6" @submit.prevent="submit">
                <h2 class="text-lg font-semibold text-gray-900">{{ editingId ? 'แก้ไขประเภทตำแหน่ง' : 'เพิ่มประเภทตำแหน่ง' }}</h2>

                <div>
                    <InputLabel for="name" value="ชื่อตำแหน่ง" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" autofocus />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel value="ประเภท" />
                    <div class="mt-2 flex flex-wrap gap-5">
                        <label v-for="t in types" :key="t.value" class="inline-flex items-center gap-2">
                            <input v-model="form.type" type="radio" :value="t.value" class="text-indigo-600 focus:ring-indigo-500" />
                            <span class="text-sm text-gray-700">{{ t.label }}</span>
                        </label>
                    </div>
                    <InputError :message="form.errors.type" class="mt-2" />
                </div>

                <div>
                    <InputLabel value="สถานะ" />
                    <div class="mt-2 flex flex-wrap gap-5">
                        <label class="inline-flex items-center gap-2">
                            <input v-model="form.is_active" type="radio" :value="false" class="text-indigo-600 focus:ring-indigo-500" />
                            <span class="text-sm text-gray-700">ปิดการทำงาน</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input v-model="form.is_active" type="radio" :value="true" class="text-indigo-600 focus:ring-indigo-500" />
                            <span class="text-sm text-gray-700">เปิดการทำงาน</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton type="button" @click="showForm = false">ยกเลิก</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">บันทึกรายการ</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
