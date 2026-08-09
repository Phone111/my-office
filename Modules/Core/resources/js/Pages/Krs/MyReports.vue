<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import EmptyState from '@/Components/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    year: { type: Number, default: 0 },
    rows: { type: Array, default: () => [] },
    rounds: { type: Array, default: () => [] },
});
const flash = computed(() => usePage().props.flash?.success);

const selectedYear = ref(props.year);
const reload = () => router.get(route('krs.my'), { year: selectedYear.value }, { preserveState: true, preserveScroll: true });

const roundMeta = (r) => {
    if (!r) return { label: 'ยังไม่ส่ง', cls: 'bg-gray-100 text-gray-500' };
    if (r.status === 'received') return { label: 'รับแล้ว', cls: 'bg-emerald-100 text-emerald-700' };
    return { label: 'ส่งแล้ว', cls: 'bg-amber-100 text-amber-700' };
};

// ===== ฟอร์มส่งรายงาน =====
const show = ref(false);
const ctx = ref({ indicator: null, round: null });
const form = useForm({ indicator_id: null, round: null, file: null, note: '' });
const openSubmit = (ind, round) => {
    ctx.value = { indicator: ind, round };
    form.reset();
    form.clearErrors();
    form.indicator_id = ind.id;
    form.round = round;
    form.note = ind.rounds[round]?.note ?? '';
    show.value = true;
};
const onFile = (e) => (form.file = e.target.files[0]);
const submit = () => form.post(route('krs.submit'), { forceFormData: true, preserveScroll: true, onSuccess: () => (show.value = false) });
</script>

<template>
    <Head title="รายงานตัวชี้วัดของฉัน" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">รายงานตัวชี้วัด (คำรับรองฯ) ของฉัน</h2>
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-gray-500">ปี พ.ศ.</span>
                    <input v-model.number="selectedYear" type="number" class="w-24 rounded-md border-gray-300 text-sm shadow-sm" @change="reload" />
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3">ตัวชี้วัด</th>
                                <th v-for="r in rounds" :key="r" class="px-4 py-3 text-center">รอบ {{ r }} เดือน</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="rows.length === 0"><td :colspan="rounds.length + 1"><EmptyState title="ยังไม่มีตัวชี้วัดที่มอบหมายให้คุณในปีนี้" /></td></tr>
                            <tr v-for="i in rows" :key="i.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3"><span class="text-xs text-gray-400">{{ i.category_label }}</span><br /><span class="font-semibold text-gray-900">{{ i.code }}</span> {{ i.name }}</td>
                                <td v-for="r in rounds" :key="r" class="px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="roundMeta(i.rounds[r]).cls">{{ roundMeta(i.rounds[r]).label }}</span>
                                    <div class="mt-1 flex items-center justify-center gap-2 text-xs">
                                        <a v-if="i.rounds[r]?.url" :href="i.rounds[r].url" target="_blank" class="text-indigo-500 hover:underline">ไฟล์</a>
                                        <button v-if="!i.rounds[r] || i.rounds[r].status !== 'received'" class="text-indigo-600 hover:underline" @click="openSubmit(i, r)">{{ i.rounds[r] ? 'แก้ไข' : 'ส่ง' }}</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="show" @close="show = false">
            <form class="space-y-4 p-6" @submit.prevent="submit">
                <h2 class="text-lg font-semibold text-gray-900">ส่งรายงานตัวชี้วัด — รอบ {{ ctx.round }} เดือน</h2>
                <p class="text-sm text-gray-500"><span class="font-semibold text-gray-700">{{ ctx.indicator?.code }}</span> {{ ctx.indicator?.name }}</p>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">แนบไฟล์รายงาน</label>
                    <input type="file" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700" @change="onFile" />
                    <p v-if="form.errors.file" class="mt-1 text-xs text-rose-500">{{ form.errors.file }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">หมายเหตุ</label>
                    <textarea v-model="form.note" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton type="button" @click="show = false">ยกเลิก</SecondaryButton>
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">ส่งรายงาน</button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
