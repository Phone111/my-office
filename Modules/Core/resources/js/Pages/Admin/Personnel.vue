<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    users: { type: Array, default: () => [] },
    positions: { type: Array, default: () => [] },
    groups: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] }, // [{name, label}]
    roleGroups: { type: Array, default: () => [] }, // [{level, roles:[{name,label}]}]
});

const flash = computed(() => usePage().props.flash ?? {});
const roleLabel = (name) => props.roles.find((r) => r.name === name)?.label ?? name;

// roleGroups[0] = ระดับบทบาท (เลือก 1), roleGroups[1] = หน้าที่ย่อย (ติ๊กได้หลายอัน)
const levelOptions = computed(() => props.roleGroups[0]?.roles ?? []);
const dutyOptions = computed(() => props.roleGroups[1]?.roles ?? []);
const dutyNames = computed(() => dutyOptions.value.map((r) => r.name));

// ผอ./รองผอ. เป็น "ตำแหน่งผู้บริหาร" — เลือกได้เมื่อระดับ = ผู้บริหาร และเลือกได้อย่างเดียว
const EXEC_DUTY_NAMES = ['director', 'deputy_director'];
const isExecutive = computed(() => form.level === 'executive');
const execDutyOptions = computed(() => dutyOptions.value.filter((r) => EXEC_DUTY_NAMES.includes(r.name)));
const generalDutyOptions = computed(() => dutyOptions.value.filter((r) => !EXEC_DUTY_NAMES.includes(r.name)));
const execPosition = computed({
    get: () => form.duties.find((d) => EXEC_DUTY_NAMES.includes(d)) ?? '',
    set: (val) => {
        form.duties = form.duties.filter((d) => !EXEC_DUTY_NAMES.includes(d));
        if (val) form.duties.push(val);
    },
});
// เปลี่ยนระดับออกจากผู้บริหาร → ล้างตำแหน่ง ผอ./รองผอ. ทิ้ง
watch(
    () => form.level,
    (lvl) => {
        if (lvl !== 'executive') form.duties = form.duties.filter((d) => !EXEC_DUTY_NAMES.includes(d));
    },
);

// ค้นหา (ฝั่ง client)
const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.users;
    return props.users.filter(
        (u) =>
            u.name.toLowerCase().includes(q) ||
            (u.email ?? '').toLowerCase().includes(q) ||
            (u.username ?? '').toLowerCase().includes(q),
    );
});

const showForm = ref(false);
const editingId = ref(null);
const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    position_id: null,
    group_id: null,
    department_id: null,
    phone: '',
    level: '',     // ระดับบทบาท 1 อย่าง
    duties: [],    // หน้าที่ย่อยหลายอย่าง
    is_acting_director: false, // ผู้รักษาการในตำแหน่ง ผอ.
});

const openCreate = () => {
    form.reset();
    form.clearErrors();
    editingId.value = null;
    showForm.value = true;
};
const openEdit = (u) => {
    form.clearErrors();
    editingId.value = u.id;
    form.name = u.name;
    form.username = u.username ?? '';
    form.email = u.email;
    form.password = '';
    form.position_id = u.position_id;
    form.group_id = u.group_id;
    form.department_id = u.department_id;
    form.phone = u.phone ?? '';
    // แยก role ของผู้ใช้เป็น "ระดับ" (ตัวที่ไม่ใช่หน้าที่ย่อย) + "หน้าที่ย่อย"
    form.level = u.roles.find((r) => !dutyNames.value.includes(r)) ?? '';
    form.duties = u.roles.filter((r) => dutyNames.value.includes(r));
    form.is_acting_director = !!u.is_acting_director;
    showForm.value = true;
};
const submit = () => {
    const opts = { onSuccess: () => (showForm.value = false), preserveScroll: true };
    // รวมระดับ + หน้าที่ย่อย เป็น roles array ให้ backend (syncRoles)
    form.transform((d) => ({ ...d, roles: [d.level, ...d.duties].filter(Boolean) }));
    if (editingId.value) form.put(route('admin.personnel.update', editingId.value), opts);
    else form.post(route('admin.personnel.store'), opts);
};
const remove = (u) => {
    if (confirm(`ลบบุคลากร "${u.name}" ?`)) {
        router.delete(route('admin.personnel.destroy', u.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="จัดการบุคลากร" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">จัดการบุคลากร</h2>
                <div class="flex items-center gap-2">
                    <TextInput v-model="search" type="text" placeholder="ค้นหาชื่อ / อีเมล" class="w-48" />
                    <PrimaryButton @click="openCreate">+ เพิ่มบุคลากร</PrimaryButton>
                </div>
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

                <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3">ชื่อผู้ใช้ / อีเมล</th>
                                <th class="px-6 py-3">ชื่อ - นามสกุล</th>
                                <th class="px-6 py-3">ตำแหน่ง</th>
                                <th class="px-6 py-3">กลุ่ม</th>
                                <th class="px-6 py-3">ปฏิบัติหน้าที่</th>
                                <th class="px-6 py-3 text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="filtered.length === 0">
                                <td colspan="6" class="px-6 py-12">
                                    <EmptyState title="ไม่พบบุคลากร" />
                                </td>
                            </tr>
                            <tr v-for="u in filtered" :key="u.id" class="text-sm text-gray-700 hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div v-if="u.username" class="font-medium text-gray-700">{{ u.username }}</div>
                                    <div class="text-gray-500">{{ u.email }}</div>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900">{{ u.name }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ u.position ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ u.group ?? u.department ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span v-for="r in u.roles" :key="r" class="mr-1 inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700">
                                        {{ roleLabel(r) }}
                                    </span>
                                    <span v-if="u.roles.length === 0" class="text-gray-300">—</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <button class="font-medium text-indigo-600 hover:text-indigo-800" @click="openEdit(u)">แก้ไข</button>
                                        <button class="font-medium text-red-600 hover:text-red-800" @click="remove(u)">ลบ</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal เพิ่ม/แก้ไขบุคลากร -->
        <Modal :show="showForm" max-width="2xl" @close="showForm = false">
            <form class="space-y-5 p-6" @submit.prevent="submit">
                <h2 class="text-lg font-semibold text-gray-900">{{ editingId ? 'แก้ไขบุคลากร' : 'เพิ่มบุคลากรใหม่' }}</h2>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="username" value="ชื่อผู้ใช้ (Username)" />
                        <TextInput id="username" v-model="form.username" type="text" class="mt-1 block w-full" autofocus autocomplete="off" placeholder="ใช้เข้าสู่ระบบ (เว้นว่างได้)" />
                        <InputError :message="form.errors.username" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="email" value="อีเมล" />
                        <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="password" :value="editingId ? 'รหัสผ่าน (เว้นว่างหากไม่เปลี่ยน)' : 'รหัสผ่าน'" />
                        <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>
                </div>

                <div>
                    <InputLabel for="name" value="ชื่อ - นามสกุล" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="position" value="ตำแหน่ง" />
                        <select id="position" v-model="form.position_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option :value="null">—</option>
                            <option v-for="p in positions" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="phone" value="โทรศัพท์มือถือ" />
                        <TextInput id="phone" v-model="form.phone" type="text" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="group" value="กลุ่มงาน" />
                        <select id="group" v-model="form.group_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option :value="null">—</option>
                            <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="dept" value="กลุ่มสาระ" />
                        <select id="dept" v-model="form.department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option :value="null">—</option>
                            <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <!-- ระดับบทบาท: เลือก 1 -->
                    <div>
                        <InputLabel for="level" value="ระดับบทบาท" />
                        <select id="level" v-model="form.level" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— เลือกระดับ —</option>
                            <option v-for="r in levelOptions" :key="r.name" :value="r.name">{{ r.label }}</option>
                        </select>
                    </div>

                    <!-- ตำแหน่งผู้บริหาร: เลือกได้เมื่อระดับ = ผู้บริหาร (เลือกอย่างใดอย่างหนึ่ง) -->
                    <div v-if="isExecutive && execDutyOptions.length" class="mt-4 rounded-lg border border-indigo-100 bg-indigo-50/50 p-3">
                        <InputLabel value="ตำแหน่งผู้บริหาร" />
                        <p class="mt-0.5 text-xs text-gray-500">เลือกว่าเป็นผู้อำนวยการหรือรองผู้อำนวยการ</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <label v-for="d in execDutyOptions" :key="d.name" class="flex cursor-pointer items-center gap-2 rounded-md border bg-white px-3 py-2 text-sm hover:bg-gray-50" :class="execPosition === d.name ? 'border-indigo-400 ring-1 ring-indigo-300' : 'border-gray-200'">
                                <input type="radio" :value="d.name" v-model="execPosition" name="exec-position" class="text-indigo-600 focus:ring-indigo-500" />
                                <span>{{ d.label }}</span>
                            </label>
                            <button v-if="execPosition" type="button" class="text-xs text-gray-400 hover:text-gray-600" @click="execPosition = ''">ล้าง</button>
                        </div>
                    </div>

                    <!-- หน้าที่ย่อย: ติ๊กได้หลายอัน (สำหรับเจ้าหน้าที่) -->
                    <div v-if="generalDutyOptions.length" class="mt-4">
                        <InputLabel value="หน้าที่ย่อย (มอบเพิ่ม — เลือกได้หลายอย่าง)" />
                        <p class="mt-0.5 text-xs text-gray-500">หน้าที่ย่อยจะเปิดเมนู/สิทธิ์เฉพาะงานนั้นให้ เช่น สารบรรณ เห็นเมนูทะเบียนหนังสือ</p>
                        <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                            <label v-for="d in generalDutyOptions" :key="d.name" class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm hover:bg-gray-50">
                                <input type="checkbox" :value="d.name" v-model="form.duties" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <span>{{ d.label }}</span>
                            </label>
                        </div>
                    </div>
                    <InputError :message="form.errors.roles" class="mt-2" />

                    <!-- ผู้รักษาการในตำแหน่ง ผอ. (ใช้กับปุ่ม "เสนอรักษาการ" ในเส้นทางหนังสือ) -->
                    <label class="mt-4 flex items-center gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm">
                        <input v-model="form.is_acting_director" type="checkbox" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500" />
                        <span class="font-medium text-amber-800">ผู้รักษาการในตำแหน่งผู้อำนวยการ</span>
                        <span class="text-xs text-gray-500">(จะปรากฏเป็นตัวเลือก "เสนอรักษาการ" ในเส้นทางหนังสือ)</span>
                    </label>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton type="button" @click="showForm = false">ยกเลิก</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">เพิ่มสมาชิก</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
