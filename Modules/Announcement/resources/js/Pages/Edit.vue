<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    news: { type: Object, required: true },
});

// ใช้ _method=put + post เพื่อให้รองรับการอัปโหลดไฟล์ (FormData)
const form = useForm({
    _method: 'put',
    title: props.news.title,
    content: props.news.content,
    file: null,
});

const submit = () => form.post(route('announcements.update', props.news.id));

const fileUrl = (path) => `/storage/${path}`;
</script>

<template>
    <Head title="แก้ไขข่าวสาร" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                แก้ไขข่าวสาร
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <form
                    class="space-y-6 rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100"
                    @submit.prevent="submit"
                >
                    <div>
                        <InputLabel for="title" value="หัวข้อข่าว" />
                        <TextInput
                            id="title"
                            v-model="form.title"
                            type="text"
                            class="mt-1 block w-full"
                            autofocus
                        />
                        <InputError :message="form.errors.title" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="content" value="เนื้อหา" />
                        <textarea
                            id="content"
                            v-model="form.content"
                            rows="6"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <InputError :message="form.errors.content" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="file" value="ไฟล์แนบ" />
                        <p v-if="news.file_path" class="mt-1 text-sm text-gray-500">
                            ไฟล์ปัจจุบัน:
                            <a
                                :href="fileUrl(news.file_path)"
                                target="_blank"
                                class="text-indigo-600 hover:underline"
                            >
                                ดาวน์โหลด
                            </a>
                        </p>
                        <input
                            id="file"
                            type="file"
                            class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                            @input="form.file = $event.target.files[0]"
                        />
                        <p class="mt-1 text-xs text-gray-400">
                            อัปโหลดไฟล์ใหม่เพื่อแทนที่ไฟล์เดิม (เว้นว่างหากไม่ต้องการเปลี่ยน)
                        </p>
                        <progress
                            v-if="form.progress"
                            :value="form.progress.percentage"
                            max="100"
                            class="mt-2 w-full"
                        >
                            {{ form.progress.percentage }}%
                        </progress>
                        <InputError :message="form.errors.file" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <Link :href="route('announcements.index')">
                            <SecondaryButton type="button">ยกเลิก</SecondaryButton>
                        </Link>
                        <PrimaryButton :disabled="form.processing">
                            บันทึกการแก้ไข
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
