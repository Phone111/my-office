<script setup>
import Checkbox from '@/Components/Checkbox.vue';
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
    departments: { type: Array, default: () => [] },
    groups: { type: Array, default: () => [] },
    types: { type: Object, default: () => ({}) },
});

const flash = computed(() => usePage().props.flash ?? {});
const typeOptions = computed(() => Object.entries(props.types).map(([value, label]) => ({ value, label })));

const showForm = ref(false);
const editingId = ref(null);
const form = useForm({
    name: '',
    group_id: null,
    type: '',
    is_active: true,
});

const openCreate = () => {
    form.reset();
    form.clearErrors();
    editingId.value = null;
    showForm.value = true;
};
const openEdit = (d) => {
    form.clearErrors();
    editingId.value = d.id;
    form.name = d.name;
    form.group_id = d.group_id;
    form.type = d.type ?? '';
    form.is_active = d.is_active;
    showForm.value = true;
};
const submit = () => {
    const opts = { onSuccess: () => (showForm.value = false), preserveScroll: true };
    if (editingId.value) form.put(route('admin.departments.update', editingId.value), opts);
    else form.post(route('admin.departments.store'), opts);
};
const remove = (d) => {
    if (confirm(`ลบกลุ่มสาระ "${d.name}" ?`)) {
        router.delete(route('admin.departments.destroy', d.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="จัดการกลุ่มสาระ" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">จัดการกลุ่มสาระ</h2>
                <PrimaryButton @click="openCreate">+ เพิ่มกลุ่มสาระ</PrimaryButton>
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
                                <th class="px-6 py-3 w-16">ลำดับ</th>
                                <th class="px-6 py-3">ชื่อสังกัด</th>
                                <th class="px-6 py-3">กลุ่มภาระกิจ</th>
                                <th class="px-6 py-3">สถานะ</th>
                                <th class="px-6 py-3 w-16">level</th>
                                <th class="px-6 py-3 text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="departments.length === 0">
                                <td colspan="6" class="px-6 py-12">
                                    <EmptyState title="ยังไม่มีกลุ่มสาระ" />
                                </td>
                            </tr>
                            <tr v-for="d in departments" :key="d.id" class="text-sm text-gray-700 hover:bg-gray-50">
                                <td class="px-6 py-4 text-gray-400">{{ d.sort_order }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900">{{ d.name }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ d.group ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <StatusBadge :status="d.is_active ? 'active' : 'inactive'" />
                                </td>
                                <td class="px-6 py-4 text-gray-400">{{ d.level }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <button class="font-medium text-indigo-600 hover:text-indigo-800" @click="openEdit(d)">แก้ไข</button>
                                        <button class="font-medium text-red-600 hover:text-red-800" @click="remove(d)">ลบ</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal เพิ่ม/แก้ไขกลุ่มสาระ -->
        <Modal :show="showForm" @close="showForm = false">
            <form class="space-y-5 p-6" @submit.prevent="submit">
                <h2 class="text-lg font-semibold text-gray-900">{{ editingId ? 'แก้ไขกลุ่มสาระ' : 'เพิ่มกลุ่มสาระ' }}</h2>

                <div>
                    <InputLabel for="name" value="ชื่องาน / ชื่อสังกัด" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" autofocus />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="group" value="กลุ่ม / ฝ่าย" />
                    <select id="group" v-model="form.group_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option :value="null">— เลือกกลุ่ม/ฝ่าย —</option>
                        <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
                    </select>
                    <InputError :message="form.errors.group_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel value="สิทธิการใช้" />
                    <div class="mt-2 flex flex-wrap gap-4">
                        <label v-for="t in typeOptions" :key="t.value" class="flex items-center gap-2 text-sm text-gray-600">
                            <input v-model="form.type" :value="t.value" type="radio" class="border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            {{ t.label }}
                        </label>
                    </div>
                    <InputError :message="form.errors.type" class="mt-2" />
                </div>

                <label class="flex items-center gap-2">
                    <Checkbox v-model:checked="form.is_active" />
                    <span class="text-sm text-gray-600">เปิดใช้งาน</span>
                </label>
                <p v-if="!editingId" class="text-xs text-gray-400">ลำดับ (level) จะถูกกำหนดอัตโนมัติ</p>

                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton type="button" @click="showForm = false">เคลียร์/ยกเลิก</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">เพิ่มรายการ</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
