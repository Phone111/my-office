<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    leaveType: { type: Object, required: true },
    colleagues: { type: Array, default: () => [] },
});

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    leave_type_id: props.leaveType.id,
    written_at: 'โรงเรียนเศรษฐบุตรบำเพ็ญ',
    written_date: today,
    reason: '',
    start_date: '',
    end_date: '',
    contact_address: '',
    phone: '',
    handover_to: '',
    file: null,
});

// จำนวนวัน (นับรวมวันเริ่ม–สิ้นสุด)
const totalDays = computed(() => {
    if (!form.start_date || !form.end_date) return 0;
    const s = new Date(form.start_date);
    const e = new Date(form.end_date);
    if (e < s) return 0;
    return Math.round((e - s) / 86400000) + 1;
});

const submit = () => form.post(route('leave.requests.store'));
</script>

<template>
    <Head :title="'เขียนขออนุญาต' + leaveType.name" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">เขียนขออนุญาต{{ leaveType.name }}</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form class="space-y-5 rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100" @submit.prevent="submit">
                    <p class="text-center text-lg font-semibold text-rose-600">บันทึก{{ leaveType.name }}</p>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel for="written_at" value="เขียนที่" />
                            <TextInput id="written_at" v-model="form.written_at" type="text" class="mt-1 block w-full" />
                            <InputError :message="form.errors.written_at" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="written_date" value="วันที่เขียน" />
                            <TextInput id="written_date" v-model="form.written_date" type="date" class="mt-1 block w-full" />
                            <InputError :message="form.errors.written_date" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="reason" :value="'ขอ' + leaveType.name + ' เนื่องจาก'" />
                        <textarea
                            id="reason"
                            v-model="form.reason"
                            rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <InputError :message="form.errors.reason" class="mt-2" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-3">
                        <div>
                            <InputLabel for="start_date" value="ตั้งแต่วันที่" />
                            <TextInput id="start_date" v-model="form.start_date" type="date" class="mt-1 block w-full" />
                            <InputError :message="form.errors.start_date" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="end_date" value="ถึงวันที่" />
                            <TextInput id="end_date" v-model="form.end_date" type="date" class="mt-1 block w-full" />
                            <InputError :message="form.errors.end_date" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel value="มีกำหนด" />
                            <div class="mt-1 flex h-[42px] items-center rounded-md bg-gray-50 px-3 text-gray-700 ring-1 ring-gray-200">
                                <span class="font-semibold text-gray-900">{{ totalDays }}</span>
                                <span class="ml-1 text-sm text-gray-500">วัน</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <InputLabel for="contact_address" value="ในระหว่างลาติดต่อข้าพเจ้าได้ที่" />
                        <TextInput id="contact_address" v-model="form.contact_address" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.contact_address" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="phone" value="โทรศัพท์" />
                        <TextInput id="phone" v-model="form.phone" type="text" class="mt-1 block w-full sm:max-w-xs" />
                        <InputError :message="form.errors.phone" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="handover_to" value="มอบหมายงานให้ผู้ปฏิบัติหน้าที่แทน (ไม่บังคับ)" />
                        <select id="handover_to" v-model="form.handover_to" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-md">
                            <option value="">— ไม่มอบหมาย —</option>
                            <option v-for="c in colleagues" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-400">ผู้รับมอบงานจะได้รับแจ้งเตือนให้ยืนยัน "รับมอบงาน" เมื่อเสนอแฟ้ม</p>
                        <InputError :message="form.errors.handover_to" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="file" :value="leaveType.code === 'sick' ? 'ใบรับรองแพทย์ (ไม่บังคับ)' : 'แนบเอกสาร (ไม่บังคับ)'" />
                        <input
                            id="file"
                            type="file"
                            class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                            @input="form.file = $event.target.files[0]"
                        />
                        <InputError :message="form.errors.file" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                        <Link :href="route('leave.requests.index')">
                            <SecondaryButton type="button">ยกเลิก</SecondaryButton>
                        </Link>
                        <PrimaryButton :disabled="form.processing">บันทึก</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
