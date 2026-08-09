<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    ageGroups: { type: Array, default: () => [] },
    reasons: { type: Array, default: () => [] },
    schools: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});
const flash = computed(() => usePage().props.flash?.success);

const show = ref(false);
const editingId = ref(null);
const form = useForm({ citizen_id: '', prename: '', name: '', surname: '', birthdate: '', age_group: '6-11', address: '', tambon: '', amphoe: '', province: '', service_school_id: '', enrolled: false, enroll_school: '', non_enroll_reason: '', note: '' });

const openCreate = () => { editingId.value = null; form.reset(); show.value = true; };
const submit = () => {
    if (editingId.value) form.put(route('opportunity.update', editingId.value), { preserveScroll: true, onSuccess: () => (show.value = false) });
    else form.post(route('opportunity.store'), { preserveScroll: true, onSuccess: () => { show.value = false; form.reset(); } });
};
const remove = (r) => { if (confirm(`ลบ "${r.fullname}"?`)) router.delete(route('opportunity.destroy', r.id), { preserveScroll: true }); };

const filterBy = (key, val) => router.get(route('opportunity.index'), { ...props.filters, [key]: val || undefined }, { preserveState: true, replace: true });
</script>

<template>
    <Head title="สิทธิและโอกาสทางการศึกษา" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">สิทธิและโอกาสทางการศึกษา</h2>
                <div class="flex gap-2">
                    <Link :href="route('opportunity.report')" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">รายงาน</Link>
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" @click="openCreate">เพิ่มประชากรวัยเรียน</button>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-100"><p class="text-2xl font-bold text-gray-800">{{ stats.total }}</p><p class="text-xs text-gray-400">ประชากรวัยเรียนทั้งหมด</p></div>
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-emerald-100"><p class="text-2xl font-bold text-emerald-600">{{ stats.enrolled }}</p><p class="text-xs text-gray-400">เข้าเรียนแล้ว</p></div>
                    <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-rose-100"><p class="text-2xl font-bold text-rose-600">{{ stats.not_enrolled }}</p><p class="text-xs text-gray-400">ยังไม่เข้าเรียน</p></div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <select :value="filters.age_group" class="rounded-md border-gray-300 text-sm" @change="filterBy('age_group', $event.target.value)">
                        <option value="">ทุกช่วงอายุ</option>
                        <option v-for="a in ageGroups" :key="a.key" :value="a.key">{{ a.label }}</option>
                    </select>
                    <select :value="filters.status" class="rounded-md border-gray-300 text-sm" @change="filterBy('status', $event.target.value)">
                        <option value="">ทุกสถานะ</option>
                        <option value="enrolled">เข้าเรียนแล้ว</option>
                        <option value="not">ยังไม่เข้าเรียน</option>
                    </select>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3">ชื่อ-สกุล</th><th class="px-4 py-3">ช่วงอายุ</th><th class="px-4 py-3">ที่อยู่</th><th class="px-4 py-3">สถานะ</th><th class="px-4 py-3">สาเหตุไม่เข้าเรียน</th><th class="px-4 py-3 text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="rows.length === 0"><td colspan="6"><EmptyState title="ยังไม่มีข้อมูล" description="ยังไม่มีประชากรวัยเรียนในระบบ" /></td></tr>
                            <tr v-for="r in rows" :key="r.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ r.fullname }}<span v-if="r.citizen_id" class="block text-xs text-gray-400">{{ r.citizen_id }}</span></td>
                                <td class="px-4 py-3 text-gray-600">{{ r.age_group }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ r.address || '—' }}</td>
                                <td class="px-4 py-3"><span v-if="r.enrolled" class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">เข้าเรียน{{ r.enroll_school ? ' · '+r.enroll_school : '' }}</span><span v-else class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-600">ยังไม่เข้าเรียน</span></td>
                                <td class="px-4 py-3 text-gray-500">{{ r.reason ?? '—' }}</td>
                                <td class="px-4 py-3 text-center"><button class="text-rose-600 hover:underline" @click="remove(r)">ลบ</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="show" @close="show = false">
            <div class="space-y-3 p-6">
                <h3 class="text-lg font-semibold text-gray-800">เพิ่มประชากรวัยเรียน</h3>
                <div class="grid grid-cols-3 gap-2">
                    <input v-model="form.prename" type="text" placeholder="คำนำหน้า" class="rounded-md border-gray-300 text-sm" />
                    <input v-model="form.name" type="text" placeholder="ชื่อ" class="rounded-md border-gray-300 text-sm" />
                    <input v-model="form.surname" type="text" placeholder="สกุล" class="rounded-md border-gray-300 text-sm" />
                </div>
                <p v-if="form.errors.name" class="text-xs text-rose-500">{{ form.errors.name }}</p>
                <div class="grid grid-cols-2 gap-2">
                    <input v-model="form.citizen_id" type="text" placeholder="เลขบัตรประชาชน" class="rounded-md border-gray-300 text-sm" />
                    <select v-model="form.age_group" class="rounded-md border-gray-300 text-sm"><option v-for="a in ageGroups" :key="a.key" :value="a.key">{{ a.label }}</option></select>
                </div>
                <input v-model="form.address" type="text" placeholder="ที่อยู่ (บ้านเลขที่/หมู่)" class="w-full rounded-md border-gray-300 text-sm" />
                <div class="grid grid-cols-3 gap-2">
                    <input v-model="form.tambon" type="text" placeholder="ตำบล" class="rounded-md border-gray-300 text-sm" />
                    <input v-model="form.amphoe" type="text" placeholder="อำเภอ" class="rounded-md border-gray-300 text-sm" />
                    <input v-model="form.province" type="text" placeholder="จังหวัด" class="rounded-md border-gray-300 text-sm" />
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="form.enrolled" type="checkbox" class="rounded border-gray-300 text-indigo-600" /> เข้าเรียนแล้ว</label>
                <input v-if="form.enrolled" v-model="form.enroll_school" type="text" placeholder="เรียนที่โรงเรียน" class="w-full rounded-md border-gray-300 text-sm" />
                <select v-else v-model="form.non_enroll_reason" class="w-full rounded-md border-gray-300 text-sm">
                    <option value="">— สาเหตุไม่เข้าเรียน —</option>
                    <option v-for="r in reasons" :key="r.key" :value="r.key">{{ r.label }}</option>
                </select>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="show = false">ยกเลิก</SecondaryButton>
                    <button :disabled="form.processing" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60" @click="submit">บันทึก</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
