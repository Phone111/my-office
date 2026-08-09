<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    area: { type: Object, default: null },
    schools: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({ schools: 0, active: 0 }) },
});

const flash = computed(() => usePage().props.flash ?? {});
const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.schools;
    return props.schools.filter((s) => (s.name ?? '').toLowerCase().includes(q) || (s.code ?? '').toLowerCase().includes(q));
});

// ===== สำนักงานเขต =====
const areaForm = useForm({ name: props.area?.name ?? '', code: props.area?.code ?? '', book_prefix: props.area?.book_prefix ?? '', address: props.area?.address ?? '', phone: props.area?.phone ?? '' });
const showArea = ref(false);
const openArea = () => {
    areaForm.name = props.area?.name ?? '';
    areaForm.code = props.area?.code ?? '';
    areaForm.book_prefix = props.area?.book_prefix ?? '';
    areaForm.address = props.area?.address ?? '';
    areaForm.phone = props.area?.phone ?? '';
    areaForm.clearErrors();
    showArea.value = true;
};
const saveArea = () => areaForm.put(route('units.area.update', props.area.id), { preserveScroll: true, onSuccess: () => (showArea.value = false) });

// ===== โรงเรียน =====
const showSchool = ref(false);
const editingId = ref(null);
const form = useForm({ name: '', code: '', book_prefix: '', address: '', phone: '', is_active: true });
const openCreate = () => {
    form.reset();
    form.clearErrors();
    editingId.value = null;
    showSchool.value = true;
};
const openEdit = (s) => {
    form.clearErrors();
    editingId.value = s.id;
    form.name = s.name;
    form.code = s.code ?? '';
    form.book_prefix = s.book_prefix ?? '';
    form.address = s.address ?? '';
    form.phone = s.phone ?? '';
    form.is_active = s.is_active;
    showSchool.value = true;
};
const submit = () => {
    const opts = { preserveScroll: true, onSuccess: () => (showSchool.value = false) };
    if (editingId.value) form.put(route('units.update', editingId.value), opts);
    else form.post(route('units.store'), opts);
};
const remove = (s) => {
    if (s.users_count > 0) { alert('ลบไม่ได้ — ยังมีผู้ใช้สังกัดโรงเรียนนี้'); return; }
    if (confirm(`ลบโรงเรียน "${s.name}" ?`)) router.delete(route('units.destroy', s.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="หน่วยงาน/โรงเรียนในสังกัด" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">หน่วยงาน / โรงเรียนในสังกัด</h2>
                <button type="button" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500" @click="openCreate">+ เพิ่มโรงเรียน</button>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash.success }}</div>
                <div v-if="flash.error" class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-100">{{ flash.error }}</div>

                <!-- สำนักงานเขต -->
                <div v-if="area" class="rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 p-6 text-white shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-indigo-100">สำนักงานเขต (หน่วยงานต้นสังกัด)</p>
                            <p class="mt-1 text-2xl font-bold">{{ area.name }}</p>
                            <p v-if="area.code" class="text-sm text-indigo-100">รหัส {{ area.code }}</p>
                            <p class="mt-1 text-sm text-indigo-100">{{ area.users_count }} คน <span v-if="area.phone">· {{ area.phone }}</span></p>
                            <p v-if="area.address" class="text-sm text-indigo-100">{{ area.address }}</p>
                        </div>
                        <button type="button" class="rounded-md bg-white/15 px-3 py-1.5 text-sm font-medium text-white ring-1 ring-white/30 hover:bg-white/25" @click="openArea">แก้ไขข้อมูลเขต</button>
                    </div>
                </div>

                <!-- สรุป + ค้นหา -->
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-gray-500">โรงเรียนในสังกัด <span class="font-semibold text-gray-800">{{ summary.schools }}</span> แห่ง · ใช้งาน {{ summary.active }}</p>
                    <input v-model="search" type="text" placeholder="ค้นหาชื่อ/รหัสโรงเรียน" class="w-56 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>

                <!-- ตารางโรงเรียน -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3">โรงเรียน</th>
                                <th class="px-4 py-3">รหัส</th>
                                <th class="px-4 py-3">โทร</th>
                                <th class="px-4 py-3 text-center">ผู้ใช้</th>
                                <th class="px-4 py-3 text-center">สถานะ</th>
                                <th class="px-4 py-3 text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="filtered.length === 0"><td colspan="6" class="px-6 py-10 text-center text-sm text-gray-400">ยังไม่มีโรงเรียนในสังกัด</td></tr>
                            <tr v-for="s in filtered" :key="s.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ s.name }}<div v-if="s.address" class="text-xs text-gray-400">{{ s.address }}</div></td>
                                <td class="px-4 py-3 text-gray-500">{{ s.code ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ s.phone ?? '—' }}</td>
                                <td class="px-4 py-3 text-center tabular-nums">{{ s.users_count }}</td>
                                <td class="px-4 py-3 text-center">
                                    <StatusBadge :status="s.is_active ? 'active' : 'inactive'" :label="s.is_active ? 'ใช้งาน' : 'ปิด'" />
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button class="font-medium text-indigo-600 hover:text-indigo-800" @click="openEdit(s)">แก้ไข</button>
                                    <button class="ml-3 font-medium text-rose-600 hover:text-rose-800" @click="remove(s)">ลบ</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ฟอร์มโรงเรียน -->
        <Modal :show="showSchool" @close="showSchool = false">
            <form class="space-y-4 p-6" @submit.prevent="submit">
                <h2 class="text-lg font-semibold text-gray-900">{{ editingId ? 'แก้ไขโรงเรียน' : 'เพิ่มโรงเรียนในสังกัด' }}</h2>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">ชื่อโรงเรียน</label>
                    <input v-model="form.name" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-rose-500">{{ form.errors.name }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">รหัสโรงเรียน</label>
                        <input v-model="form.code" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">โทรศัพท์</label>
                        <input v-model="form.phone" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">เลขที่หนังสือ (prefix)</label>
                    <input v-model="form.book_prefix" type="text" placeholder="เช่น ศธ 04066" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <p class="mt-1 text-xs text-gray-400">ใช้ออกเลขหนังสือส่งอัตโนมัติ เช่น “ศธ 04066/1”</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">ที่อยู่</label>
                    <input v-model="form.address" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" /> เปิดใช้งาน
                </label>
                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton type="button" @click="showSchool = false">ยกเลิก</SecondaryButton>
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60">บันทึก</button>
                </div>
            </form>
        </Modal>

        <!-- ฟอร์มสำนักงานเขต -->
        <Modal :show="showArea" @close="showArea = false">
            <form class="space-y-4 p-6" @submit.prevent="saveArea">
                <h2 class="text-lg font-semibold text-gray-900">ข้อมูลสำนักงานเขต</h2>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">ชื่อสำนักงานเขต</label>
                    <input v-model="areaForm.name" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <p v-if="areaForm.errors.name" class="mt-1 text-xs text-rose-500">{{ areaForm.errors.name }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">รหัสเขต</label>
                        <input v-model="areaForm.code" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">โทรศัพท์</label>
                        <input v-model="areaForm.phone" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">เลขที่หนังสือ (prefix)</label>
                    <input v-model="areaForm.book_prefix" type="text" placeholder="เช่น ศธ 04066" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">ที่อยู่</label>
                    <input v-model="areaForm.address" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton type="button" @click="showArea = false">ยกเลิก</SecondaryButton>
                    <button type="submit" :disabled="areaForm.processing" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60">บันทึก</button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
