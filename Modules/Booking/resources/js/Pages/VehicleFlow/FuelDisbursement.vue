<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    doc: { type: Object, required: true },
    canEditFuel: { type: Boolean, default: false },
    school: { type: String, default: '' },
});
const flash = computed(() => usePage().props.flash?.success);

const form = useForm({
    fuel_station: props.doc.fuel_station ?? '',
    fuel_liters: props.doc.fuel_liters ?? '',
    fuel_amount: props.doc.fuel_amount ?? '',
    fuel_note: props.doc.fuel_note ?? '',
});
const save = () => form.post(route('booking.vehicle-flow.fuel.save', props.doc.id), { preserveScroll: true });
const printDoc = () => window.print();
</script>

<template>
    <Head title="ใบเบิกน้ำมันเชื้อเพลิง" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between print:hidden">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ใบเบิกน้ำมันเชื้อเพลิงและน้ำมันหล่อลื่น</h2>
                <button class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500" @click="printDoc">พิมพ์เอกสาร</button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100 print:hidden">{{ flash }}</div>

                <!-- แผงกรอกข้อมูลการเบิก (เจ้าหน้าที่) — ไม่พิมพ์ -->
                <div v-if="canEditFuel" class="rounded-2xl bg-amber-50 p-5 shadow-sm ring-1 ring-amber-200 print:hidden">
                    <p class="mb-3 text-sm font-semibold text-amber-800">กรอกรายละเอียดการเบิกน้ำมัน (เจ้าหน้าที่)</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700">ปั๊ม / สถานที่เติม</label>
                            <input v-model="form.fuel_station" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">จำนวน (ลิตร)</label>
                            <input v-model="form.fuel_liters" type="number" step="0.01" min="0" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">เป็นเงิน (บาท)</label>
                            <input v-model="form.fuel_amount" type="number" step="0.01" min="0" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div class="col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700">หมายเหตุ</label>
                            <input v-model="form.fuel_note" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button :disabled="form.processing" class="rounded-md bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50" @click="save">บันทึกใบเบิก</button>
                    </div>
                </div>

                <!-- เอกสารราชการ (พิมพ์) -->
                <div class="rounded-2xl bg-white p-10 shadow-sm ring-1 ring-gray-100 print:rounded-none print:p-0 print:shadow-none print:ring-0">
                    <div class="text-center">
                        <p class="text-lg font-bold">บันทึกข้อความ</p>
                        <p class="mt-1 text-sm">{{ school }}</p>
                    </div>

                    <div class="mt-6 space-y-1 text-[15px] leading-7">
                        <p><span class="font-semibold">เรื่อง</span> &nbsp; ขออนุญาตเบิกน้ำมันเชื้อเพลิงและน้ำมันหล่อลื่น</p>
                        <p><span class="font-semibold">เรียน</span> &nbsp; ผู้อำนวยการ{{ school }}</p>
                    </div>

                    <div class="mt-5 indent-12 text-[15px] leading-8">
                        ตามที่อนุญาตให้ <span class="font-semibold">{{ doc.driver_name ?? '………………' }}</span>
                        นำรถยนต์ราชการหมายเลขทะเบียน <span class="font-semibold">{{ doc.vehicle_plate ?? '………' }}</span>
                        <template v-if="doc.vehicle"> ({{ doc.vehicle }})</template>
                        เดินทางไปราชการเพื่อ <span class="font-semibold">{{ doc.purpose ?? '………………' }}</span>
                        ณ <span class="font-semibold">{{ doc.destination ?? '………………' }}</span>
                        ระหว่าง <span class="font-semibold">{{ doc.start_thai }}</span> ถึง <span class="font-semibold">{{ doc.end_thai }}</span> นั้น
                    </div>

                    <div class="mt-4 indent-12 text-[15px] leading-8">
                        จึงขอเบิกน้ำมันเชื้อเพลิง (แหล่ง: {{ doc.fuel_source_label }})
                        จากปั๊ม/สถานที่ <span class="font-semibold">{{ doc.fuel_station ?? '………………' }}</span>
                        จำนวน <span class="font-semibold">{{ doc.fuel_liters ?? '………' }}</span> ลิตร
                        เป็นเงิน <span class="font-semibold">{{ doc.fuel_amount ?? '………' }}</span> บาท
                        <template v-if="doc.fuel_note"> ({{ doc.fuel_note }})</template>
                    </div>

                    <p class="mt-4 indent-12 text-[15px]">จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ</p>

                    <!-- ลายเซ็น -->
                    <div class="mt-10 grid grid-cols-2 gap-8 text-center text-[15px]">
                        <div>
                            <p>ลงชื่อ ……………………………</p>
                            <p class="mt-1">({{ doc.officer ?? '………………………' }})</p>
                            <p class="mt-1 text-sm text-gray-600">เจ้าหน้าที่ยานพาหนะ</p>
                        </div>
                        <div>
                            <p>ลงชื่อ ……………………………</p>
                            <p class="mt-1">({{ doc.requester ?? '………………………' }})</p>
                            <p class="mt-1 text-sm text-gray-600">ผู้ขอใช้รถ</p>
                        </div>
                    </div>

                    <div class="mt-10 border-t border-gray-300 pt-5 text-[15px]">
                        <p class="font-semibold">ความเห็นผู้บังคับบัญชา / การอนุมัติ</p>
                        <p class="mt-2">{{ doc.approver_comment ?? '…………………………………………………………………………' }}</p>
                        <div class="mt-8 text-center">
                            <p>ลงชื่อ ……………………………</p>
                            <p class="mt-1">({{ doc.approver ?? '………………………' }})</p>
                            <p class="mt-1 text-sm text-gray-600">ผู้อำนวยการ{{ school }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@media print {
    :global(body) {
        background: #fff;
    }
}
</style>
