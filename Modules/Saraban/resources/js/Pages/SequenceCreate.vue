<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    divisions: { type: Array, default: () => [] },
    year: { type: Number, default: 0 },
    justIssued: { type: Object, default: null },
});

const form = useForm({
    title: '',
    division: '',
    attachment: null,
});

const submit = () =>
    form.post(route('saraban.sequence.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
</script>

<template>
    <Head title="ออกเลขลำดับเอกสาร" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ออกเลขลำดับเอกสาร</h2>
                <Link :href="route('saraban.sequence.registry')" class="text-sm font-medium text-indigo-600 hover:underline">ทะเบียนลำดับเอกสาร →</Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-xl space-y-5 px-4 sm:px-6 lg:px-8">
                <!-- กล่องผลลัพธ์เลขที่ออก -->
                <div v-if="justIssued" class="rounded-2xl bg-gradient-to-b from-emerald-50 to-white p-6 text-center shadow-sm ring-1 ring-emerald-100">
                    <div class="text-sm font-semibold text-gray-600">เอกสารลำดับที่</div>
                    <div class="mt-1 text-3xl font-bold text-rose-600">{{ justIssued.number }}</div>
                    <div class="mt-1 text-sm text-rose-500">{{ justIssued.thai }}</div>
                </div>

                <!-- ฟอร์ม -->
                <form class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100" @submit.prevent="submit">
                    <div class="text-center text-sm font-semibold text-gray-700">ออกเลขลำดับเอกสาร · ปี พ.ศ. {{ year }}</div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">เรื่อง</label>
                        <input v-model="form.title" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="ระบุชื่อเรื่อง" />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-rose-500">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">ผู้ขอ</label>
                        <select v-model="form.division" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— เลือกกลุ่ม/หน่วยงาน —</option>
                            <option v-for="d in divisions" :key="d" :value="d">{{ d }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">แนบเอกสาร</label>
                        <input type="file" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100" @input="form.attachment = $event.target.files[0]" />
                        <p v-if="form.errors.attachment" class="mt-1 text-xs text-rose-500">{{ form.errors.attachment }}</p>
                    </div>

                    <div class="pt-2 text-center">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:opacity-60">
                            {{ form.processing ? 'กำลังบันทึก…' : 'บันทึกออกเลข' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
