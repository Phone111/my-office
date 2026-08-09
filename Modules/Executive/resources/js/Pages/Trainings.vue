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
import { computed, ref } from 'vue';

const props = defineProps({
    trainings: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});

// ผอ. ดูได้อย่างเดียว — เพิ่ม/แก้/ลบ เฉพาะเลขาฯ/รองผอ./แอดมิน
const userRoles = computed(() => usePage().props.auth?.roles ?? []);
const canManage = computed(() => ['secretary', 'deputy_director', 'admin'].some((r) => userRoles.value.includes(r)));

const showForm = ref(false);
const editingId = ref(null);
const form = useForm({
    staff_name: '',
    course_name: '',
    organizer: '',
    start_date: '',
    end_date: '',
    hours: 0,
    location: '',
    note: '',
});

const openCreate = () => {
    form.reset();
    form.clearErrors();
    editingId.value = null;
    showForm.value = true;
};

const openEdit = (t) => {
    form.clearErrors();
    editingId.value = t.id;
    form.staff_name = t.staff_name;
    form.course_name = t.course_name;
    form.organizer = t.organizer ?? '';
    form.start_date = toIso(t.start_date);
    form.end_date = toIso(t.end_date);
    form.hours = t.hours;
    form.location = t.location ?? '';
    form.note = t.note ?? '';
    showForm.value = true;
};

// แปลง d/m/Y -> Y-m-d สำหรับ input date
const toIso = (d) => {
    if (!d) return '';
    const [day, month, year] = d.split('/');
    return `${year}-${month}-${day}`;
};

const submit = () => {
    const opts = { onSuccess: () => (showForm.value = false), preserveScroll: true };
    if (editingId.value) {
        form.put(route('executive.trainings.update', editingId.value), opts);
    } else {
        form.post(route('executive.trainings.store'), opts);
    }
};

const remove = (t) => {
    if (confirm(`ลบรายการอบรมของ "${t.staff_name}" ?`)) {
        router.delete(route('executive.trainings.destroy', t.id), { preserveScroll: true });
    }
};

// ค้นหา
const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.trainings;
    return props.trainings.filter(
        (t) =>
            (t.staff_name ?? '').toLowerCase().includes(q) ||
            (t.course_name ?? '').toLowerCase().includes(q) ||
            (t.organizer ?? '').toLowerCase().includes(q),
    );
});

// ดูรายละเอียด
const detail = ref(null);
</script>

<template>
    <Head title="สรุปการอบรมบุคลากร" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">สรุปการอบรมของบุคลากร</h2>
                <PrimaryButton v-if="canManage" @click="openCreate">+ เพิ่มการอบรม</PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ flash.success }}
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3">
                        <input v-model="search" type="text" placeholder="ค้นหาชื่อ / หลักสูตร / หน่วยงาน" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-80" />
                    </div>
                    <EmptyState v-if="filtered.length === 0" title="ไม่พบข้อมูลการอบรม" />
                    <table v-else class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3">บุคลากร</th>
                                <th class="px-6 py-3">หลักสูตร</th>
                                <th class="px-6 py-3">หน่วยงานจัด</th>
                                <th class="px-6 py-3">ช่วงวันที่</th>
                                <th class="px-6 py-3">ชม.</th>
                                <th v-if="canManage" class="px-6 py-3 text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="t in filtered" :key="t.id" class="cursor-pointer text-sm text-gray-700 hover:bg-indigo-50/50" @click="detail = t">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ t.staff_name }}</td>
                                <td class="px-6 py-4">{{ t.course_name }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ t.organizer ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ t.start_date }}{{ t.end_date ? ' – ' + t.end_date : '' }}</td>
                                <td class="px-6 py-4">{{ t.hours }}</td>
                                <td v-if="canManage" class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <button class="font-medium text-indigo-600 hover:text-indigo-800" @click.stop="openEdit(t)">แก้ไข</button>
                                        <button class="font-medium text-red-600 hover:text-red-800" @click.stop="remove(t)">ลบ</button>
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
                <h2 class="text-lg font-semibold text-gray-900">{{ editingId ? 'แก้ไขการอบรม' : 'เพิ่มการอบรม' }}</h2>
                <div>
                    <InputLabel for="staff" value="ชื่อบุคลากร" />
                    <TextInput id="staff" v-model="form.staff_name" type="text" class="mt-1 block w-full" autofocus />
                    <InputError :message="form.errors.staff_name" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="course" value="หลักสูตร/หัวข้อ" />
                    <TextInput id="course" v-model="form.course_name" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.course_name" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="org" value="หน่วยงานที่จัด" />
                    <TextInput id="org" v-model="form.organizer" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.organizer" class="mt-2" />
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <InputLabel for="sd" value="วันเริ่ม" />
                        <TextInput id="sd" v-model="form.start_date" type="date" class="mt-1 block w-full" />
                        <InputError :message="form.errors.start_date" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="ed" value="วันสิ้นสุด" />
                        <TextInput id="ed" v-model="form.end_date" type="date" class="mt-1 block w-full" />
                        <InputError :message="form.errors.end_date" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="hrs" value="ชั่วโมง" />
                        <TextInput id="hrs" v-model="form.hours" type="number" min="0" class="mt-1 block w-full" />
                        <InputError :message="form.errors.hours" class="mt-2" />
                    </div>
                </div>
                <div>
                    <InputLabel for="loc" value="สถานที่" />
                    <TextInput id="loc" v-model="form.location" type="text" class="mt-1 block w-full" />
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton type="button" @click="showForm = false">ยกเลิก</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">บันทึก</PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- รายละเอียดการอบรม -->
        <Modal :show="detail !== null" @close="detail = null">
            <div v-if="detail" class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">{{ detail.course_name }}</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex gap-3"><dt class="w-32 shrink-0 text-gray-400">บุคลากร</dt><dd class="text-gray-700">{{ detail.staff_name }}</dd></div>
                    <div class="flex gap-3"><dt class="w-32 shrink-0 text-gray-400">หน่วยงานที่จัด</dt><dd class="text-gray-700">{{ detail.organizer || '—' }}</dd></div>
                    <div class="flex gap-3"><dt class="w-32 shrink-0 text-gray-400">ช่วงวันที่</dt><dd class="text-gray-700">{{ detail.start_date }}{{ detail.end_date ? ' – ' + detail.end_date : '' }}</dd></div>
                    <div class="flex gap-3"><dt class="w-32 shrink-0 text-gray-400">จำนวนชั่วโมง</dt><dd class="text-gray-700">{{ detail.hours }} ชม.</dd></div>
                    <div v-if="detail.location" class="flex gap-3"><dt class="w-32 shrink-0 text-gray-400">สถานที่</dt><dd class="text-gray-700">{{ detail.location }}</dd></div>
                    <div v-if="detail.note" class="flex gap-3"><dt class="w-32 shrink-0 text-gray-400">หมายเหตุ</dt><dd class="whitespace-pre-line text-gray-700">{{ detail.note }}</dd></div>
                </dl>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="detail = null">ปิด</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
