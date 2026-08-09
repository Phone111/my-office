<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    logs: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});

const today = new Date().toISOString().slice(0, 10);
const form = useForm({
    log_date: today,
    title: '',
    detail: '',
    location: '',
});

const submit = () =>
    form.post(route('executive.work-logs.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('title', 'detail', 'location'),
    });

const remove = (l) => {
    if (confirm(`ลบบันทึก "${l.title}" ?`)) {
        router.delete(route('executive.work-logs.destroy', l.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="บันทึกปฏิบัติงานผู้บริหาร" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">บันทึกปฏิบัติงานผู้บริหาร</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto grid max-w-6xl gap-6 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
                <!-- ฟอร์มเพิ่มบันทึก -->
                <div class="lg:col-span-1">
                    <form class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100" @submit.prevent="submit">
                        <h3 class="font-semibold text-gray-800">เพิ่มบันทึกการปฏิบัติงาน</h3>

                        <div>
                            <InputLabel for="log_date" value="วันที่" />
                            <TextInput id="log_date" v-model="form.log_date" type="date" class="mt-1 block w-full" />
                            <InputError :message="form.errors.log_date" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="title" value="หัวข้องาน / ภารกิจ" />
                            <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" />
                            <InputError :message="form.errors.title" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="location" value="สถานที่ (ไม่บังคับ)" />
                            <TextInput id="location" v-model="form.location" type="text" class="mt-1 block w-full" />
                            <InputError :message="form.errors.location" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="detail" value="รายละเอียด (ไม่บังคับ)" />
                            <textarea
                                id="detail"
                                v-model="form.detail"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <InputError :message="form.errors.detail" class="mt-2" />
                        </div>
                        <div class="flex justify-end">
                            <PrimaryButton :disabled="form.processing">บันทึก</PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- ไทม์ไลน์บันทึก -->
                <div class="space-y-4 lg:col-span-2">
                    <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ flash.success }}
                    </div>

                    <div v-if="logs.length === 0" class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                        <EmptyState
                            title="ยังไม่มีบันทึกการปฏิบัติงาน"
                            description="กรอกแบบฟอร์มด้านซ้ายเพื่อเพิ่มบันทึกภารกิจรายวัน"
                            icon="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
                        />
                    </div>

                    <div
                        v-for="l in logs"
                        :key="l.id"
                        class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <p class="text-xs font-medium text-indigo-600">{{ l.log_thai }}</p>
                                <h4 class="mt-1 font-semibold text-gray-900">{{ l.title }}</h4>
                                <p v-if="l.location" class="mt-0.5 text-sm text-gray-500">{{ l.location }}</p>
                                <p v-if="l.detail" class="mt-2 whitespace-pre-line text-sm text-gray-600">{{ l.detail }}</p>
                            </div>
                            <button class="shrink-0 text-sm font-medium text-red-500 hover:text-red-700" @click="remove(l)">
                                ลบ
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
