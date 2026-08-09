<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import DangerButton from '@/Components/DangerButton.vue';
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

defineProps({
    vehicles: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});

const showForm = ref(false);
const editingId = ref(null);
const form = useForm({ name: '', license_plate: '', seats: 4, is_active: true });

const openCreate = () => {
    form.reset();
    form.clearErrors();
    editingId.value = null;
    showForm.value = true;
};

const openEdit = (v) => {
    form.clearErrors();
    editingId.value = v.id;
    form.name = v.name;
    form.license_plate = v.license_plate;
    form.seats = v.seats;
    form.is_active = v.is_active;
    showForm.value = true;
};

const submit = () => {
    const opts = { onSuccess: () => (showForm.value = false), preserveScroll: true };
    if (editingId.value) {
        form.put(route('booking.vehicles.update', editingId.value), opts);
    } else {
        form.post(route('booking.vehicles.store'), opts);
    }
};

const remove = (v) => {
    if (confirm(`ลบรถ "${v.name}" ?`)) {
        router.delete(route('booking.vehicles.destroy', v.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="จัดการรถยนต์" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">จัดการรถยนต์</h2>
                <PrimaryButton @click="openCreate">+ เพิ่มรถยนต์</PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div
                    v-if="flash.success"
                    class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
                >
                    {{ flash.success }}
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <EmptyState v-if="vehicles.length === 0" title="ยังไม่มีรถยนต์ในระบบ" />
                    <table v-else class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3">ชื่อ/รุ่น</th>
                                <th class="px-6 py-3">ทะเบียน</th>
                                <th class="px-6 py-3">ที่นั่ง</th>
                                <th class="px-6 py-3">สถานะ</th>
                                <th class="px-6 py-3 text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="v in vehicles" :key="v.id" class="text-sm text-gray-700 hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ v.name }}</td>
                                <td class="px-6 py-4">{{ v.license_plate }}</td>
                                <td class="px-6 py-4">{{ v.seats }}</td>
                                <td class="px-6 py-4">
                                    <StatusBadge
                                        :status="v.is_active ? 'active' : 'inactive'"
                                        :label="v.is_active ? 'พร้อมใช้งาน' : 'งดใช้งาน'"
                                    />
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <button class="font-medium text-indigo-600 hover:text-indigo-800" @click="openEdit(v)">แก้ไข</button>
                                        <button class="font-medium text-red-600 hover:text-red-800" @click="remove(v)">ลบ</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="showForm" @close="showForm = false">
            <form class="space-y-5 p-6" @submit.prevent="submit">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ editingId ? 'แก้ไขรถยนต์' : 'เพิ่มรถยนต์' }}
                </h2>

                <div>
                    <InputLabel for="name" value="ชื่อ/รุ่นรถ" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" autofocus />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="plate" value="ทะเบียนรถ" />
                    <TextInput id="plate" v-model="form.license_plate" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.license_plate" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="seats" value="จำนวนที่นั่ง" />
                    <TextInput id="seats" v-model="form.seats" type="number" min="1" class="mt-1 block w-full" />
                    <InputError :message="form.errors.seats" class="mt-2" />
                </div>
                <label class="flex items-center gap-2">
                    <Checkbox v-model:checked="form.is_active" />
                    <span class="text-sm text-gray-600">พร้อมใช้งาน</span>
                </label>

                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton type="button" @click="showForm = false">ยกเลิก</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">บันทึก</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
