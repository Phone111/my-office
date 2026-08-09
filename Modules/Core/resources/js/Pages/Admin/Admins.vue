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
    admins: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] }, // [{ name, label }]
});

const flash = computed(() => usePage().props.flash ?? {});

// ป้ายชื่อระดับสิทธิ์ — ใช้ป้ายไทยจาก backend (แหล่งข้อมูลกลาง)
const roleLabel = (name) => props.roles.find((r) => r.name === name)?.label ?? name;
const avatarUrl = (path) => (path ? `/storage/${path}` : null);

/* ---------- เลือกหลายรายการ ---------- */
const selected = ref([]);
const allChecked = computed(
    () => props.admins.length > 0 && selected.value.length === props.admins.length,
);
const toggleAll = (e) => {
    selected.value = e.target.checked ? props.admins.map((a) => a.id) : [];
};
const bulkDelete = () => {
    if (selected.value.length === 0) return;
    if (confirm(`ลบผู้ดูแลระบบ ${selected.value.length} รายการ?\nบุคคลเหล่านี้จะเข้าใช้ระบบไม่ได้`)) {
        router.post(
            route('admin.admins.bulk-destroy'),
            { ids: selected.value },
            { preserveScroll: true, onSuccess: () => (selected.value = []) },
        );
    }
};

/* ---------- เพิ่ม/แก้ไขผู้ดูแลระบบ ---------- */
const showForm = ref(false);
const editingId = ref(null);
const form = useForm({ username: '', password: '', name: '', email: '', photo: null });

const openCreate = () => {
    form.reset();
    form.clearErrors();
    editingId.value = null;
    showForm.value = true;
};
const openEdit = (a) => {
    form.reset();
    form.clearErrors();
    editingId.value = a.id;
    form.username = a.username ?? '';
    form.name = a.name;
    form.email = a.email;
    form.password = '';
    showForm.value = true;
};
const submit = () => {
    const opts = { onSuccess: () => (showForm.value = false), preserveScroll: true, forceFormData: true };
    if (editingId.value) {
        // อัปเดตพร้อมไฟล์รูป → spoof เป็น PUT
        form.transform((d) => ({ ...d, _method: 'put' }));
        form.post(route('admin.admins.update', editingId.value), opts);
    } else {
        form.transform((d) => d);
        form.post(route('admin.admins.store'), opts);
    }
};
const removeOne = (a) => {
    if (confirm(`ลบผู้ดูแลระบบ "${a.name}" ?`)) {
        router.delete(route('admin.admins.destroy', a.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="จัดการผู้ดูแลระบบ" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">จัดการผู้ดูแลระบบ</h2>
                <PrimaryButton @click="openCreate">+ เพิ่มผู้ดูแลระบบ</PrimaryButton>
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

                <!-- แถบดำเนินการเมื่อเลือกรายการ -->
                <div v-if="selected.length > 0" class="flex items-center justify-between rounded-lg bg-indigo-50 px-4 py-2 text-sm">
                    <span class="text-indigo-700">เลือก {{ selected.length }} รายการ</span>
                    <DangerButton @click="bulkDelete">ลบที่เลือก</DangerButton>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3">
                                    <input type="checkbox" :checked="allChecked" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @change="toggleAll" />
                                </th>
                                <th class="px-6 py-3">ชื่อ - นามสกุล</th>
                                <th class="px-6 py-3">ชื่อผู้ใช้</th>
                                <th class="px-6 py-3">Email</th>
                                <th class="px-6 py-3">ระดับสิทธิ์</th>
                                <th class="px-6 py-3 text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="admins.length === 0">
                                <td colspan="6" class="px-6 py-12">
                                    <EmptyState title="ไม่มีผู้ดูแลระบบ" />
                                </td>
                            </tr>
                            <tr v-for="a in admins" :key="a.id" class="text-sm text-gray-700 hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <input v-model="selected" :value="a.id" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img v-if="avatarUrl(a.profile_image)" :src="avatarUrl(a.profile_image)" alt="" class="h-8 w-8 rounded-full object-cover" />
                                        <span v-else class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">{{ a.name.charAt(0) }}</span>
                                        <span class="font-medium text-gray-900">{{ a.name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ a.username ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ a.email }}</td>
                                <td class="px-6 py-4">
                                    <span v-for="r in a.roles" :key="r" class="mr-1 inline-flex rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">
                                        {{ roleLabel(r) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <button class="font-medium text-indigo-600 hover:text-indigo-800" @click="openEdit(a)">แก้ไข</button>
                                        <button class="font-medium text-red-600 hover:text-red-800" @click="removeOne(a)">ลบ</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal เพิ่ม/แก้ไขผู้ดูแลระบบ -->
        <Modal :show="showForm" @close="showForm = false">
            <form class="space-y-5 p-6" @submit.prevent="submit">
                <h2 class="text-lg font-semibold text-gray-900">{{ editingId ? 'แก้ไขผู้ดูแลระบบ' : 'เพิ่มผู้ดูแลระบบ' }}</h2>

                <div>
                    <InputLabel for="username" value="ชื่อผู้ใช้ (สำหรับเข้าสู่ระบบ)" />
                    <TextInput id="username" v-model="form.username" type="text" class="mt-1 block w-full" autofocus autocomplete="off" />
                    <p class="mt-1 text-xs text-gray-400">ภาษาอังกฤษ/ตัวเลข/ขีด ( - _ ) เช่น admin01</p>
                    <InputError :message="form.errors.username" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="password" :value="editingId ? 'รหัสผ่าน (เว้นว่างหากไม่เปลี่ยน)' : 'รหัสผ่าน'" />
                    <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                    <InputError :message="form.errors.password" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="name" value="ชื่อ - นามสกุล" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="email" value="Email" />
                    <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" />
                    <InputError :message="form.errors.email" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="photo" value="รูปภาพ" />
                    <input
                        id="photo"
                        type="file"
                        accept="image/*"
                        class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                        @input="form.photo = $event.target.files[0]"
                    />
                    <p class="mt-1 text-xs text-gray-400">รูปโปรไฟล์ (ไม่บังคับ) ขนาดไม่เกิน 2MB</p>
                    <InputError :message="form.errors.photo" class="mt-2" />
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton type="button" @click="showForm = false">ยกเลิก</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">บันทึก</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
