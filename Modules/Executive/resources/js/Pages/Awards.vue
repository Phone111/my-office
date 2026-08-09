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
    awards: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});

// ผอ. ดูได้อย่างเดียว — เพิ่ม/แก้/ลบ เฉพาะเลขาฯ/รองผอ./แอดมิน
const userRoles = computed(() => usePage().props.auth?.roles ?? []);
const canManage = computed(() => ['secretary', 'deputy_director', 'admin'].some((r) => userRoles.value.includes(r)));

const showForm = ref(false);
const editingId = ref(null);
const form = useForm({
    staff_name: '',
    award_name: '',
    level: '',
    awarded_by: '',
    awarded_date: '',
    note: '',
});

const toIso = (d) => {
    if (!d) return '';
    const [day, month, year] = d.split('/');
    return `${year}-${month}-${day}`;
};

const openCreate = () => {
    form.reset();
    form.clearErrors();
    editingId.value = null;
    showForm.value = true;
};

const openEdit = (a) => {
    form.clearErrors();
    editingId.value = a.id;
    form.staff_name = a.staff_name;
    form.award_name = a.award_name;
    form.level = a.level ?? '';
    form.awarded_by = a.awarded_by ?? '';
    form.awarded_date = toIso(a.awarded_date);
    form.note = a.note ?? '';
    showForm.value = true;
};

const submit = () => {
    const opts = { onSuccess: () => (showForm.value = false), preserveScroll: true };
    if (editingId.value) {
        form.put(route('executive.awards.update', editingId.value), opts);
    } else {
        form.post(route('executive.awards.store'), opts);
    }
};

const remove = (a) => {
    if (confirm(`ลบรางวัลของ "${a.staff_name}" ?`)) {
        router.delete(route('executive.awards.destroy', a.id), { preserveScroll: true });
    }
};

const levels = ['โรงเรียน', 'เขตพื้นที่', 'จังหวัด', 'ภาค', 'ชาติ'];

// ค้นหา
const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.awards;
    return props.awards.filter(
        (a) =>
            (a.staff_name ?? '').toLowerCase().includes(q) ||
            (a.award_name ?? '').toLowerCase().includes(q) ||
            (a.level ?? '').toLowerCase().includes(q),
    );
});

// ดูรายละเอียด
const detail = ref(null);
</script>

<template>
    <Head title="สรุปรางวัลบุคลากร" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">สรุปรางวัลของบุคลากร</h2>
                <PrimaryButton v-if="canManage" @click="openCreate">+ เพิ่มรางวัล</PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ flash.success }}
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3">
                        <input v-model="search" type="text" placeholder="ค้นหาชื่อ / รางวัล / ระดับ" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-80" />
                    </div>
                    <EmptyState v-if="filtered.length === 0" title="ไม่พบข้อมูลรางวัล" />
                    <table v-else class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3">บุคลากร</th>
                                <th class="px-6 py-3">รางวัล</th>
                                <th class="px-6 py-3">ระดับ</th>
                                <th class="px-6 py-3">มอบโดย</th>
                                <th class="px-6 py-3">วันที่</th>
                                <th v-if="canManage" class="px-6 py-3 text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="a in filtered" :key="a.id" class="cursor-pointer text-sm text-gray-700 hover:bg-indigo-50/50" @click="detail = a">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ a.staff_name }}</td>
                                <td class="px-6 py-4">{{ a.award_name }}</td>
                                <td class="px-6 py-4">
                                    <span v-if="a.level" class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">{{ a.level }}</span>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ a.awarded_by ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ a.awarded_date }}</td>
                                <td v-if="canManage" class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <button class="font-medium text-indigo-600 hover:text-indigo-800" @click.stop="openEdit(a)">แก้ไข</button>
                                        <button class="font-medium text-red-600 hover:text-red-800" @click.stop="remove(a)">ลบ</button>
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
                <h2 class="text-lg font-semibold text-gray-900">{{ editingId ? 'แก้ไขรางวัล' : 'เพิ่มรางวัล' }}</h2>
                <div>
                    <InputLabel for="staff" value="ชื่อบุคลากร" />
                    <TextInput id="staff" v-model="form.staff_name" type="text" class="mt-1 block w-full" autofocus />
                    <InputError :message="form.errors.staff_name" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="award" value="ชื่อรางวัล" />
                    <TextInput id="award" v-model="form.award_name" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.award_name" class="mt-2" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <InputLabel for="level" value="ระดับ" />
                        <select id="level" v-model="form.level" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— ไม่ระบุ —</option>
                            <option v-for="l in levels" :key="l" :value="l">{{ l }}</option>
                        </select>
                        <InputError :message="form.errors.level" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="date" value="วันที่ได้รับ" />
                        <TextInput id="date" v-model="form.awarded_date" type="date" class="mt-1 block w-full" />
                        <InputError :message="form.errors.awarded_date" class="mt-2" />
                    </div>
                </div>
                <div>
                    <InputLabel for="by" value="หน่วยงานที่มอบ" />
                    <TextInput id="by" v-model="form.awarded_by" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.awarded_by" class="mt-2" />
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton type="button" @click="showForm = false">ยกเลิก</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">บันทึก</PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- รายละเอียดรางวัล -->
        <Modal :show="detail !== null" @close="detail = null">
            <div v-if="detail" class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">{{ detail.award_name }}</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex gap-3"><dt class="w-32 shrink-0 text-gray-400">บุคลากร</dt><dd class="text-gray-700">{{ detail.staff_name }}</dd></div>
                    <div class="flex gap-3"><dt class="w-32 shrink-0 text-gray-400">ระดับ</dt><dd class="text-gray-700">{{ detail.level || '—' }}</dd></div>
                    <div class="flex gap-3"><dt class="w-32 shrink-0 text-gray-400">วันที่ได้รับ</dt><dd class="text-gray-700">{{ detail.awarded_date }}</dd></div>
                    <div class="flex gap-3"><dt class="w-32 shrink-0 text-gray-400">หน่วยงานที่มอบ</dt><dd class="text-gray-700">{{ detail.awarded_by || '—' }}</dd></div>
                    <div v-if="detail.note" class="flex gap-3"><dt class="w-32 shrink-0 text-gray-400">หมายเหตุ</dt><dd class="whitespace-pre-line text-gray-700">{{ detail.note }}</dd></div>
                </dl>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="detail = null">ปิด</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
