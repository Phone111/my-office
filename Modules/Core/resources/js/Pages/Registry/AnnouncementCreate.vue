<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    nextNumber: { type: Number, default: 1 },
    year: { type: Number, default: 0 },
});

const today = new Date().toISOString().slice(0, 10);
const form = useForm({ title: '', announced_date: today, files: [null, null, null, null] });
const setFile = (i, e) => (form.files[i] = e.target.files[0] ?? null);

const submit = () =>
    form
        .transform((d) => ({ ...d, files: d.files.filter(Boolean) }))
        .post(route('reports.registry.announcements.store'), { forceFormData: true });
</script>

<template>
    <Head title="ออกประกาศ" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ระบบประกาศ</h2>
                <Link :href="route('reports.registry.announcements')" class="text-sm text-gray-500 hover:text-gray-700">← ทะเบียนประกาศ</Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <form class="space-y-5 rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100" @submit.prevent="submit">
                    <div class="rounded-lg bg-indigo-50 px-4 py-3 text-sm text-indigo-700">
                        ฉบับที่ : <span class="font-bold">{{ nextNumber }}</span> &nbsp;·&nbsp; ปีที่ออกประกาศ : <span class="font-bold">{{ year }}</span>
                        <span class="ml-1 text-xs text-indigo-400">(ออกเลขอัตโนมัติเมื่อบันทึก)</span>
                    </div>

                    <div>
                        <InputLabel for="title" value="เรื่อง" />
                        <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" autofocus />
                        <InputError :message="form.errors.title" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="announced_date" value="ประกาศ ณ วันที่" />
                        <TextInput id="announced_date" v-model="form.announced_date" type="date" class="mt-1 block w-full sm:max-w-xs" />
                        <InputError :message="form.errors.announced_date" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="แนบเอกสาร (สูงสุด 4 ไฟล์)" />
                        <div class="mt-2 space-y-2">
                            <input
                                v-for="i in 4"
                                :key="i"
                                type="file"
                                class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                                @change="setFile(i - 1, $event)"
                            />
                        </div>
                        <InputError :message="form.errors['files.0']" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <Link :href="route('reports.registry.announcements')">
                            <SecondaryButton type="button">ยกเลิก</SecondaryButton>
                        </Link>
                        <PrimaryButton :disabled="form.processing">บันทึกออกเลข</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
