<script setup>
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    divisions: { type: Array, default: () => [] },
    numberHint: { type: String, default: '' },
    justIssued: { type: Object, default: null },
});

const issuerName = computed(() => usePage().props.auth?.user?.name ?? '');

const form = useForm({
    title: '',
    effective_date: '',
    source_date: '',
    division: '',
});

const submit = () => form.post(route('saraban.orders.store'));
</script>

<template>
    <Head title="ออกเลขคำสั่ง" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ระบบออกเลขคำสั่ง</h2>
                <Link :href="route('reports.registry.orders')" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">ทะเบียนคำสั่ง &rarr;</Link>
            </div>
        </template>

        <div class="py-10">
            <div v-if="justIssued" class="mx-auto mb-6 max-w-2xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-6 py-5 text-center">
                    <p class="text-sm font-medium text-emerald-700">ออกเลขคำสั่งเรียบร้อย</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-800">เลขทะเบียนคำสั่ง : {{ justIssued.number }}</p>
                    <p class="mt-1 text-sm text-emerald-600">{{ justIssued.title }}</p>
                    <Link :href="route('saraban.orders.pending')" class="mt-3 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        ไปแฟ้มรอแนบไฟล์คำสั่ง &rarr;
                    </Link>
                </div>
            </div>

            <form class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8" @submit.prevent="submit">
                <div class="space-y-5 rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
                    <!-- คำสั่งที่ (ตัวอย่าง) -->
                    <div class="flex items-center gap-3">
                        <span class="w-32 shrink-0 text-right text-[15px] font-semibold text-gray-800">คำสั่งที่ :</span>
                        <span class="font-mono text-[15px] text-gray-500">{{ numberHint }} <span class="text-xs text-gray-400">(ระบบออกเลขอัตโนมัติเมื่อบันทึก)</span></span>
                    </div>

                    <!-- เรื่อง -->
                    <div class="flex items-center gap-3">
                        <label class="w-32 shrink-0 text-right text-[15px] font-semibold text-gray-800">เรื่อง :</label>
                        <input v-model="form.title" type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" autofocus />
                    </div>
                    <InputError :message="form.errors.title" class="ml-32 pl-3" />

                    <!-- ทั้งนี้ ตั้งแต่วันที่ -->
                    <div class="flex items-center gap-3">
                        <label class="w-32 shrink-0 text-right text-[15px] font-semibold text-gray-800">ทั้งนี้ตั้งแต่วันที่ :</label>
                        <input v-model="form.effective_date" type="date" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <!-- สั่ง ณ วันที่ -->
                    <div class="flex items-center gap-3">
                        <label class="w-32 shrink-0 text-right text-[15px] font-semibold text-gray-800">สั่ง ณ วันที่ :</label>
                        <input v-model="form.source_date" type="date" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <!-- ผู้ออกเลขคำสั่ง -->
                    <div class="flex items-center gap-3">
                        <span class="w-32 shrink-0 text-right text-[15px] font-semibold text-gray-800">ผู้ออกเลขคำสั่ง :</span>
                        <span class="text-[15px] text-gray-600">{{ issuerName }}</span>
                    </div>

                    <!-- กลุ่ม -->
                    <div class="flex items-center gap-3">
                        <label class="w-32 shrink-0 text-right text-[15px] font-semibold text-gray-800">กลุ่ม :</label>
                        <select v-model="form.division" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— เลือกกลุ่ม —</option>
                            <option v-for="d in divisions" :key="d" :value="d">{{ d }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-center pt-4">
                    <PrimaryButton :disabled="form.processing">บันทึกออกเลข</PrimaryButton>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
