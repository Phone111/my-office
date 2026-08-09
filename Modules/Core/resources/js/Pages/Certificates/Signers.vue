<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    unitName: { type: String, default: null },
    units: { type: Array, default: () => [] },
    selectedUnit: { type: [Number, String], default: null },
    canPickUnit: { type: Boolean, default: false },
});
const flash = computed(() => usePage().props.flash?.success);

const form = useForm({ name: '', position: '', signature: null });
const onFile = (e) => (form.signature = e.target.files[0] ?? null);
const submit = () =>
    form.transform((d) => (props.canPickUnit ? { ...d, unit: props.selectedUnit } : d)).post(route('area-certificates.signers.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => form.reset(),
    });

const switchUnit = (e) => router.get(route('area-certificates.signers'), { unit: e.target.value }, { preserveState: true, replace: true });
const remove = (r) => {
    if (confirm(`ลบผู้ลงนาม "${r.name}"?`)) router.delete(route('area-certificates.signers.destroy', r.id), { preserveScroll: true, data: props.canPickUnit ? { unit: props.selectedUnit } : {} });
};
</script>

<template>
    <Head title="ผู้ลงนามเกียรติบัตร" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">ทะเบียนผู้ลงนาม (เกียรติบัตร)</h2>
                    <p class="text-xs text-gray-400">{{ unitName }}</p>
                </div>
                <Link :href="route('area-certificates.index', canPickUnit ? { unit: selectedUnit } : {})" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">← ทะเบียนเกียรติบัตร</Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div v-if="canPickUnit" class="flex items-center gap-2">
                    <label class="text-sm text-gray-500">หน่วยงาน:</label>
                    <select :value="selectedUnit" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="switchUnit">
                        <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>

                <!-- เพิ่มผู้ลงนาม -->
                <form class="space-y-3 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100" @submit.prevent="submit">
                    <p class="text-sm font-semibold text-gray-700">เพิ่มผู้ลงนาม</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">ชื่อ-สกุล</label>
                            <input v-model="form.name" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-rose-500">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">ตำแหน่ง</label>
                            <input v-model="form.position" type="text" placeholder="เช่น ผู้อำนวยการสำนักงานเขต..." class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">ภาพลายเซ็น (ถ้ามี)</label>
                        <input type="file" accept="image/*" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700" @change="onFile" />
                        <p v-if="form.errors.signature" class="mt-1 text-xs text-rose-500">{{ form.errors.signature }}</p>
                    </div>
                    <div class="flex justify-end">
                        <button :disabled="form.processing" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60">เพิ่มผู้ลงนาม</button>
                    </div>
                </form>

                <!-- รายการผู้ลงนาม -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">ผู้ลงนาม ({{ rows.length }})</div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3">ชื่อ-สกุล</th><th class="px-4 py-3">ตำแหน่ง</th><th class="px-4 py-3">ลายเซ็น</th><th class="px-4 py-3 text-center">ลบ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="rows.length === 0"><td colspan="4" class="px-6 py-8"><EmptyState title="ยังไม่มีผู้ลงนาม" /></td></tr>
                            <tr v-for="r in rows" :key="r.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ r.name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ r.position ?? '—' }}</td>
                                <td class="px-4 py-3"><img v-if="r.signature_url" :src="r.signature_url" alt="ลายเซ็น" class="h-10" /><span v-else class="text-gray-400">—</span></td>
                                <td class="px-4 py-3 text-center"><button class="text-rose-600 hover:underline" @click="remove(r)">ลบ</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <SecondaryButton @click="router.visit(route('area-certificates.index', canPickUnit ? { unit: selectedUnit } : {}))">← กลับ</SecondaryButton>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
