<script setup>
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    doc: { type: Object, required: true }, // {id,document_number,title,source_name,source_date}
});

const form = useForm({
    title: props.doc.title ?? '',
    source_date: props.doc.source_date ?? '',
    source_name: props.doc.source_name ?? '',
    cover_file: null,
    attachments: [],
});

const fileSlots = ref([null, null, null, null]);
const onCover = (e) => (form.cover_file = e.target.files[0] ?? null);
const onSlot = (i, e) => (fileSlots.value[i] = e.target.files[0] ?? null);

const submit = () => {
    form.transform((data) => ({ ...data, attachments: fileSlots.value.filter(Boolean) })).post(route('saraban.outgoing.attach', props.doc.id), { forceFormData: true });
};
</script>

<template>
    <Head title="แนบไฟล์หนังสือส่ง" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">แนบไฟล์หนังสือส่ง</h2>
                <Link :href="route('saraban.outgoing.pending')" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">&larr; แฟ้มรอแนบไฟล์ส่ง</Link>
            </div>
        </template>

        <div class="py-10">
            <form class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8" @submit.prevent="submit">
                <div class="space-y-5 rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
                    <!-- เลขทะเบียน -->
                    <div class="flex items-center gap-3">
                        <span class="w-28 shrink-0 text-right text-[15px] font-semibold text-gray-800">เลขทะเบียน :</span>
                        <span class="font-mono text-[15px] font-semibold text-indigo-700">{{ doc.document_number }}</span>
                    </div>

                    <!-- เรื่อง -->
                    <div class="flex items-center gap-3">
                        <label class="w-28 shrink-0 text-right text-[15px] font-semibold text-gray-800">เรื่อง :</label>
                        <input v-model="form.title" type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <InputError :message="form.errors.title" class="ml-28 pl-3" />

                    <!-- ลงวันที่ -->
                    <div class="flex items-center gap-3">
                        <label class="w-28 shrink-0 text-right text-[15px] font-semibold text-gray-800">ลงวันที่ :</label>
                        <input v-model="form.source_date" type="date" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <!-- ส่งถึง -->
                    <div class="flex items-center gap-3">
                        <label class="w-28 shrink-0 text-right text-[15px] font-semibold text-gray-800">ส่งถึง :</label>
                        <input v-model="form.source_name" type="text" placeholder="*ระบุชื่อหน่วยงาน/ผู้รับ" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <InputError :message="form.errors.source_name" class="ml-28 pl-3" />

                    <!-- ไฟล์แนบ -->
                    <div class="space-y-2 border-t border-gray-100 pt-4">
                        <div class="flex items-center gap-3">
                            <label class="w-28 shrink-0 text-right text-[15px] font-semibold text-gray-800">หนังสือนำ :</label>
                            <input type="file" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100" @change="onCover" />
                        </div>
                        <div v-for="i in 4" :key="i" class="flex items-center gap-3">
                            <label class="w-28 shrink-0 text-right text-[15px] text-gray-600">เอกสารแนบ {{ i }} :</label>
                            <input type="file" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-gray-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-600 hover:file:bg-gray-100" @change="(e) => onSlot(i - 1, e)" />
                        </div>
                        <InputError :message="form.errors.cover_file" class="ml-28 pl-3" />
                    </div>
                </div>

                <div class="flex justify-center pt-4">
                    <PrimaryButton :disabled="form.processing">บันทึกการส่ง</PrimaryButton>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
