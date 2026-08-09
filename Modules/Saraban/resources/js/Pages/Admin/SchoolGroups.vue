<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    groups: { type: Array, default: () => [] },
    schools: { type: Array, default: () => [] },
    ownerName: { type: String, default: null },
});
const flash = computed(() => usePage().props.flash?.success);

const form = useForm({ name: '', code: '', is_active: true, member_ids: [] });
const editingId = ref(null);
const showModal = ref(false);
const search = ref('');

const filteredSchools = computed(() => {
    const q = search.value.trim().toLowerCase();
    return q ? props.schools.filter((s) => s.name.toLowerCase().includes(q)) : props.schools;
});

const openCreate = () => {
    editingId.value = null;
    form.reset();
    form.is_active = true;
    search.value = '';
    showModal.value = true;
};
const openEdit = (g) => {
    editingId.value = g.id;
    form.name = g.name;
    form.code = g.code ?? '';
    form.is_active = g.is_active;
    form.member_ids = [...g.member_ids];
    search.value = '';
    showModal.value = true;
};
const submit = () => {
    const opts = { preserveScroll: true, onSuccess: () => (showModal.value = false) };
    if (editingId.value) form.put(route('saraban.school-groups.update', editingId.value), opts);
    else form.post(route('saraban.school-groups.store'), opts);
};
const remove = (g) => {
    if (confirm(`ลบกลุ่ม "${g.name}"? (ไม่กระทบข้อมูลโรงเรียน)`)) {
        useForm({}).delete(route('saraban.school-groups.destroy', g.id), { preserveScroll: true });
    }
};
const toggleAll = () => {
    const ids = filteredSchools.value.map((s) => s.id);
    const allIn = ids.every((i) => form.member_ids.includes(i));
    if (allIn) form.member_ids = form.member_ids.filter((i) => !ids.includes(i));
    else form.member_ids = [...new Set([...form.member_ids, ...ids])];
};
</script>

<template>
    <Head title="กลุ่มโรงเรียน" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">กลุ่มโรงเรียน (กลุ่มสถานศึกษา)</h2>
                    <p class="text-xs text-gray-400">{{ ownerName ?? '' }} — รวมโรงเรียนเป็นกลุ่มเพื่อส่งหนังสือทีเดียว</p>
                </div>
                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" @click="openCreate">+ เพิ่มกลุ่ม</button>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="w-full text-sm">
                        <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs text-gray-500">
                            <tr>
                                <th class="px-5 py-3 font-medium">ชื่อกลุ่ม</th>
                                <th class="px-5 py-3 font-medium">รหัส</th>
                                <th class="px-5 py-3 font-medium">จำนวนโรงเรียน</th>
                                <th class="px-5 py-3 font-medium">สถานะ</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="g in groups" :key="g.id" class="hover:bg-gray-50">
                                <td class="px-5 py-3 font-medium text-gray-800">{{ g.name }}</td>
                                <td class="px-5 py-3 text-gray-500">{{ g.code ?? '—' }}</td>
                                <td class="px-5 py-3"><span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">{{ g.member_count }} แห่ง</span></td>
                                <td class="px-5 py-3">
                                    <span :class="g.is_active ? 'text-emerald-600' : 'text-gray-400'">{{ g.is_active ? 'ใช้งาน' : 'ปิด' }}</span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <button class="text-indigo-600 hover:text-indigo-800" @click="openEdit(g)">แก้ไข</button>
                                    <button class="ml-3 text-rose-500 hover:text-rose-700" @click="remove(g)">ลบ</button>
                                </td>
                            </tr>
                            <tr v-if="!groups.length"><td colspan="5" class="px-5 py-8 text-center text-gray-400">ยังไม่มีกลุ่มโรงเรียน — กด "เพิ่มกลุ่ม"</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="showModal" max-width="2xl" @close="showModal = false">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-800">{{ editingId ? 'แก้ไขกลุ่มโรงเรียน' : 'เพิ่มกลุ่มโรงเรียน' }}</h3>
                <div class="mt-4 grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">ชื่อกลุ่ม</label>
                        <input v-model="form.name" type="text" placeholder="เช่น โรงเรียนในฝัน" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-rose-500">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">รหัส (ถ้ามี)</label>
                        <input v-model="form.code" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>

                <div class="mt-4">
                    <div class="mb-2 flex items-center justify-between">
                        <label class="text-sm font-medium text-gray-700">โรงเรียนสมาชิก <span class="text-xs text-gray-400">(เลือกแล้ว {{ form.member_ids.length }})</span></label>
                        <button type="button" class="text-xs font-medium text-indigo-600 hover:underline" @click="toggleAll">เลือก/ยกเลิกที่ค้นเจอ</button>
                    </div>
                    <input v-model="search" type="text" placeholder="ค้นหาโรงเรียน..." class="mb-2 w-full rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <div class="grid max-h-64 grid-cols-1 gap-1 overflow-y-auto rounded-lg border border-gray-100 p-2 sm:grid-cols-2">
                        <label v-for="s in filteredSchools" :key="s.id" class="flex items-center gap-2 rounded-md px-2 py-1 text-sm hover:bg-gray-50">
                            <input v-model="form.member_ids" type="checkbox" :value="s.id" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            {{ s.name }}
                        </label>
                        <p v-if="!filteredSchools.length" class="col-span-full px-2 py-3 text-center text-xs text-gray-400">ไม่พบโรงเรียน</p>
                    </div>
                </div>

                <label class="mt-3 flex items-center gap-2 text-sm text-gray-700">
                    <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" /> เปิดใช้งานกลุ่มนี้
                </label>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50" @click="showModal = false">ยกเลิก</button>
                    <button type="button" :disabled="form.processing || !form.name" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50" @click="submit">บันทึก</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
