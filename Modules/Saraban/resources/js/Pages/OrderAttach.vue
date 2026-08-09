<script setup>
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    doc: { type: Object, required: true },
});

const form = useForm({
    title: props.doc.title ?? '',
    effective_date: props.doc.effective_date ?? '',
    source_date: props.doc.source_date ?? '',
    attachments: [],
});

const fileSlots = ref([null, null, null, null]);
const onSlot = (i, e) => (fileSlots.value[i] = e.target.files[0] ?? null);

const submit = () => {
    form.transform((data) => ({ ...data, attachments: fileSlots.value.filter(Boolean) })).post(route('saraban.orders.attach', props.doc.id), { forceFormData: true });
};
</script>

<template>
    <Head title="แนบไฟล์คำสั่ง" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">แนบไฟล์คำสั่ง</h2>
                <Link :href="route('saraban.orders.pending')" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">&larr; แฟ้มรอแนบไฟล์คำสั่ง</Link>
            </div>
        </template>

        <div class="py-10">
            <form class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8" @submit.prevent="submit">
                <div class="space-y-5 rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center gap-3">
                        <span class="w-32 shrink-0 text-right text-[15px] font-semibold text-gray-800">คำสั่งที่ :</span>
                        <span class="font-mono text-[15px] font-semibold text-indigo-700">{{ doc.document_number }}</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <label class="w-32 shrink-0 text-right text-[15px] font-semibold text-gray-800">เรื่อง :</label>
                        <input v-model="form.title" type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <InputError :message="form.errors.title" class="ml-32 pl-3" />

                    <div class="flex items-center gap-3">
                        <label class="w-32 shrink-0 text-right text-[15px] font-semibold text-gray-800">ทั้งนี้ตั้งแต่วันที่ :</label>
                        <input v-model="form.effective_date" type="date" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <div class="flex items-center gap-3">
                        <label class="w-32 shrink-0 text-right text-[15px] font-semibold text-gray-800">สั่ง ณ วันที่ :</label>
                        <input v-model="form.source_date" type="date" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="w-32 shrink-0 text-right text-[15px] font-semibold text-gray-800">กลุ่มที่ออกคำสั่ง :</span>
                        <span class="text-[15px] text-gray-600">{{ doc.division ?? '—' }}</span>
                    </div>

                    <div class="space-y-2 border-t border-gray-100 pt-4">
                        <div v-for="i in 4" :key="i" class="flex items-center gap-3">
                            <label class="w-32 shrink-0 text-right text-[15px] font-semibold text-gray-800">แนบเอกสาร {{ i }} :</label>
                            <input type="file" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100" @change="(e) => onSlot(i - 1, e)" />
                        </div>
                        <InputError :message="form.errors.attachments" class="ml-32 pl-3" />
                    </div>
                </div>

                <div class="flex justify-center pt-4">
                    <PrimaryButton :disabled="form.processing">บันทึกเอกสาร</PrimaryButton>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
