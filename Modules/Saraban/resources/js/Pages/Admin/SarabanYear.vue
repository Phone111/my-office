<script setup>
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    activeYear: { type: Number, required: true },
    systemYear: { type: Number, required: true },
    isCustom: { type: Boolean, default: false },
    byYear: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});

const form = useForm({ year: props.activeYear });
const save = () => form.post(route('saraban.year.set'), { preserveScroll: true });

const openNext = () => {
    if (!confirm(`เปิดปีสารบรรณใหม่ พ.ศ. ${props.activeYear + 1}?\nเลขทะเบียนทุกเล่มจะเริ่มนับ 1 ใหม่ในปีนี้`)) return;
    router.post(route('saraban.year.set'), { year: props.activeYear + 1 }, { preserveScroll: true });
};
const useSystem = () => router.post(route('saraban.year.system'), {}, { preserveScroll: true });
</script>

<template>
    <Head title="จัดการปีสารบรรณ" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">จัดการปีสารบรรณ</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ flash.success }}</div>

                <!-- ปีปัจจุบัน -->
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-sm text-gray-500">ปีสารบรรณที่ใช้เดินเลขขณะนี้</p>
                            <p class="mt-1 text-3xl font-bold text-indigo-700">พ.ศ. {{ activeYear }}</p>
                            <p class="mt-1 text-xs" :class="isCustom ? 'text-amber-600' : 'text-gray-400'">
                                {{ isCustom ? 'ตั้งค่าเอง · ปีระบบจริงคือ พ.ศ. ' + systemYear : 'ใช้ปีตามระบบอัตโนมัติ' }}
                            </p>
                        </div>
                        <button type="button" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" @click="openNext">
                            + เปิดปีใหม่ (พ.ศ. {{ activeYear + 1 }})
                        </button>
                    </div>

                    <div class="mt-6 border-t border-gray-100 pt-5">
                        <label class="text-sm font-semibold text-gray-700">ตั้งปีสารบรรณ (พ.ศ.)</label>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <input v-model.number="form.year" type="number" min="2500" max="2700" class="w-36 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <PrimaryButton :disabled="form.processing" @click="save">บันทึกปีสารบรรณ</PrimaryButton>
                            <button v-if="isCustom" type="button" class="text-sm font-medium text-gray-500 hover:text-gray-700" @click="useSystem">ใช้ปีตามระบบ (พ.ศ. {{ systemYear }})</button>
                        </div>
                        <InputError :message="form.errors.year" class="mt-2" />
                        <p class="mt-2 text-xs text-gray-400">เลขทะเบียนใหม่ทุกเล่ม (รับ/ส่ง/คำสั่ง/บันทึก ฯลฯ) จะเดินตามปีนี้ และเริ่มนับ 1 ใหม่เมื่อขึ้นปีใหม่</p>
                    </div>
                </div>

                <!-- เลขล่าสุดแต่ละเล่ม แยกปี -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 bg-indigo-50/60 px-6 py-3 font-semibold text-gray-700">เลขทะเบียนล่าสุดแต่ละเล่ม (แยกตามปี)</div>
                    <div v-if="byYear.length === 0" class="px-6 py-10 text-center text-sm text-gray-400">ยังไม่มีการออกเลขทะเบียน</div>
                    <div v-for="g in byYear" :key="g.year" class="border-t border-gray-50 first:border-t-0">
                        <div class="bg-gray-50 px-6 py-2 text-sm font-bold text-gray-600">พ.ศ. {{ g.year }}</div>
                        <div class="grid grid-cols-1 gap-x-8 gap-y-1 px-6 py-3 sm:grid-cols-2">
                            <div v-for="(b, i) in g.books" :key="i" class="flex items-center justify-between border-b border-dashed border-gray-100 py-1 text-sm last:border-b-0">
                                <span class="text-gray-600">{{ b.label }}</span>
                                <span class="font-mono font-semibold text-indigo-700">{{ b.last_no }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
