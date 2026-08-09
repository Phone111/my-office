<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    signers: { type: Array, default: () => [] },
    unitName: { type: String, default: null },
    units: { type: Array, default: () => [] },
    selectedUnit: { type: [Number, String], default: null },
    canPickUnit: { type: Boolean, default: false },
});
const flash = computed(() => usePage().props.flash?.success);

const showModal = ref(false);
const today = new Date().toISOString().slice(0, 10);
const form = useForm({ category: '', title: '', recipients: '', recipient_org: '', issued_date: today, signer_id: '', note: '' });

const submit = () =>
    form.transform((d) => (props.canPickUnit ? { ...d, unit: props.selectedUnit } : d)).post(route('area-certificates.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showModal.value = false;
        },
    });

const switchUnit = (e) => router.get(route('area-certificates.index'), { unit: e.target.value }, { preserveState: true, replace: true });
const remove = (r) => {
    if (confirm(`ลบเกียรติบัตรเลขที่ ${r.cert_label} (${r.recipient_name})?`)) router.delete(route('area-certificates.destroy', r.id), { preserveScroll: true, data: props.canPickUnit ? { unit: props.selectedUnit } : {} });
};
const recipientCount = computed(() => form.recipients.split(/\r\n|\r|\n/).map((s) => s.trim()).filter(Boolean).length);
</script>

<template>
    <Head title="ทะเบียนเกียรติบัตรเขต" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">ทะเบียนเกียรติบัตร</h2>
                    <p class="text-xs text-gray-400">{{ unitName }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="route('area-certificates.signers', canPickUnit ? { unit: selectedUnit } : {})" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">ผู้ลงนาม</Link>
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500" @click="showModal = true">ออกเกียรติบัตร</button>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div v-if="canPickUnit" class="flex items-center gap-2">
                    <label class="text-sm text-gray-500">หน่วยงาน:</label>
                    <select :value="selectedUnit" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="switchUnit">
                        <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">ทะเบียนเกียรติบัตร ({{ rows.length }})</div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3">เลขที่</th><th class="px-4 py-3">วันที่</th><th class="px-4 py-3">เรื่อง/รางวัล</th><th class="px-4 py-3">ผู้รับ</th><th class="px-4 py-3">ผู้ลงนาม</th><th class="px-4 py-3 text-center">ลบ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="rows.length === 0"><td colspan="6" class="px-6 py-8"><EmptyState title="ยังไม่มีเกียรติบัตร" /></td></tr>
                            <tr v-for="r in rows" :key="r.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono font-semibold text-indigo-700">{{ r.cert_label }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ r.issued_thai }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ r.title }}<span v-if="r.category" class="block text-xs text-gray-400">{{ r.category }}</span></td>
                                <td class="px-4 py-3 text-gray-700">{{ r.recipient_name }}<span v-if="r.recipient_org" class="block text-xs text-gray-400">{{ r.recipient_org }}</span></td>
                                <td class="px-4 py-3 text-gray-500">{{ r.signer ?? '—' }}</td>
                                <td class="px-4 py-3 text-center"><button class="text-rose-600 hover:underline" @click="remove(r)">ลบ</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal ออกเกียรติบัตร -->
        <Modal :show="showModal" @close="showModal = false">
            <div class="space-y-4 p-6">
                <h3 class="text-lg font-semibold text-gray-800">ออกเกียรติบัตร</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">ประเภท/กิจกรรม</label>
                        <input v-model="form.category" type="text" placeholder="เช่น การแข่งขันศิลปหัตถกรรม" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">วันที่ออก</label>
                        <input v-model="form.issued_date" type="date" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">เรื่อง/รางวัล</label>
                    <input v-model="form.title" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-rose-500">{{ form.errors.title }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">ผู้รับเกียรติบัตร <span class="text-xs font-normal text-gray-400">(หนึ่งบรรทัดต่อหนึ่งคน — ออกเป็นชุดได้)</span></label>
                    <textarea v-model="form.recipients" rows="4" placeholder="นายสมชาย ใจดี&#10;นางสาวสมหญิง รักเรียน" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <p class="mt-1 text-xs text-gray-400">จะออก {{ recipientCount }} ฉบับ เลขวิ่งต่อเนื่อง</p>
                    <p v-if="form.errors.recipients" class="text-xs text-rose-500">{{ form.errors.recipients }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">หน่วยงาน/โรงเรียนของผู้รับ</label>
                        <input v-model="form.recipient_org" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">ผู้ลงนาม</label>
                        <select v-model="form.signer_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— ไม่ระบุ —</option>
                            <option v-for="s in signers" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">หมายเหตุ</label>
                    <input v-model="form.note" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
                <div class="flex justify-end gap-3">
                    <SecondaryButton type="button" @click="showModal = false">ยกเลิก</SecondaryButton>
                    <button :disabled="form.processing" class="rounded-md bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60" @click="submit">ออกเลขเกียรติบัตร</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
