<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    req: { type: Object, required: true },
    vehicles: { type: Array, default: () => [] },
    canSubmit: { type: Boolean, default: false },
    canAssign: { type: Boolean, default: false },
    canApprove: { type: Boolean, default: false },
    canCancel: { type: Boolean, default: false },
    canFuel: { type: Boolean, default: false },
});

const SCHOOL = 'โรงเรียนเศรษฐบุตรบำเพ็ญ';
const flash = computed(() => usePage().props.flash?.success);

const submitForm = useForm({});
const assignForm = useForm({ vehicle_id: props.req.vehicle_id ?? '', driver_name: props.req.driver_name ?? '', comment: '' });
const rejectForm = useForm({ comment: '' });
// คำสั่งผู้บริหาร: อนุญาต / ไม่อนุญาต
const order = useForm({ decision: 'approve', comment: '' });

const doSubmit = () => submitForm.post(route('booking.vehicle-flow.submit', props.req.id));
const doAssign = () => assignForm.post(route('booking.vehicle-flow.assign', props.req.id), { preserveScroll: true });
const doReject = () => {
    if (!rejectForm.comment.trim()) return;
    rejectForm.post(route('booking.vehicle-flow.reject', props.req.id), { preserveScroll: true });
};
const submitOrder = () => {
    if (order.decision === 'reject') {
        if (!order.comment.trim()) return;
        order.post(route('booking.vehicle-flow.reject', props.req.id), { preserveScroll: true });
    } else {
        order.post(route('booking.vehicle-flow.approve', props.req.id), { preserveScroll: true });
    }
};
const cancel = () => {
    if (confirm('ยกเลิกคำขอนี้?')) router.delete(route('booking.cancel', props.req.id));
};
</script>

<template>
    <Head title="แบบขออนุญาตใช้รถยนต์" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">แบบขออนุญาตใช้รถยนต์</h2>
                <div class="flex items-center gap-4">
                    <Link v-if="canFuel" :href="route('booking.vehicle-flow.fuel', req.id)" class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">ใบเบิกน้ำมัน →</Link>
                    <Link :href="route('booking.vehicle-flow.index')" class="text-sm font-medium text-gray-500 hover:underline">← กลับแฟ้ม</Link>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-2xl space-y-5 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <!-- แบบ 3 -->
                <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
                    <div class="text-center font-semibold text-gray-800">แบบขออนุญาตใช้รถยนต์</div>
                    <div class="text-right text-sm text-gray-500">แบบ 3</div>
                    <div class="my-2 text-center text-sm">วันที่ {{ req.written_thai }}</div>
                    <p class="mt-3 leading-8 text-gray-700">
                        เรียน ผู้อำนวยการ{{ SCHOOL }}<br />
                        &nbsp;&nbsp;&nbsp;&nbsp;ข้าพเจ้า <b>{{ req.requester }}</b> ตำแหน่ง {{ req.requester_position ?? '—' }}
                        {{ req.division ? 'กลุ่ม ' + req.division : '' }} สังกัด {{ SCHOOL }}
                        พร้อมด้วย {{ req.companions ?? '-' }}
                        ขออนุญาตใช้รถเพื่อ <b>{{ req.purpose }}</b>
                        ณ <b>{{ req.destination }}</b>
                        มีคนนั่ง {{ req.passengers ?? '-' }} คน
                        ตั้งแต่วันที่ {{ req.start_thai }} ถึงวันที่ {{ req.end_thai }}
                        จำนวน {{ req.days }} วัน
                        โดยใช้น้ำมันเชื้อเพลิงจาก {{ req.fuel_label ?? '-' }}
                    </p>
                    <div class="mt-6 text-center text-sm text-gray-600">
                        <div>({{ req.requester }})</div>
                        <div>ผู้ขออนุญาต</div>
                    </div>
                    <div v-if="req.file" class="mt-3 text-center text-sm"><a :href="req.file" target="_blank" class="text-indigo-600 hover:underline">เอกสารแนบ</a></div>
                </div>

                <!-- ความเห็นเจ้าหน้าที่ที่ควบคุมยานพาหนะ (โชว์เมื่อจัดรถแล้ว) -->
                <div v-if="req.driver_name" class="rounded-2xl bg-white p-5 text-sm shadow-sm ring-1 ring-gray-100">
                    <div class="mb-1 font-semibold text-gray-700 underline">ความเห็นเจ้าหน้าที่ที่ควบคุมยานพาหนะ</div>
                    <p class="leading-7 text-gray-700">
                        1. ควรอนุญาตให้ใช้รถยนต์ส่วนกลาง หมายเลขทะเบียน <b>{{ req.vehicle_plate ?? '—' }}</b>
                        โดยมี <b>{{ req.driver_name }}</b> ทำหน้าที่พนักงานขับรถ
                    </p>
                    <p v-if="req.officer_comment" class="text-gray-600">2. {{ req.officer_comment }}</p>
                    <div class="mt-2 text-xs text-gray-400">ลงชื่อ {{ req.officer }} (เจ้าหน้าที่รับจองรถ)</div>
                </div>

                <!-- คำสั่ง/ผลอนุมัติ (เมื่อจบแล้ว) -->
                <div v-if="req.approver" class="rounded-2xl p-4 text-sm ring-1" :class="req.status === 'booked' ? 'bg-emerald-50 ring-emerald-100' : 'bg-rose-50 ring-rose-100'">
                    <span class="font-medium">{{ req.status === 'booked' ? 'อนุญาตโดย' : 'ไม่อนุญาตโดย' }}:</span> {{ req.approver }}
                    <span v-if="req.approver_comment"> · {{ req.approver_comment }}</span>
                </div>

                <!-- ผู้ขอ: เสนอแฟ้ม -->
                <div v-if="canSubmit" class="rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-emerald-200">
                    <div class="mb-3 text-sm font-semibold text-emerald-700">ดำเนินการเสนอแฟ้ม</div>
                    <p class="mb-3 text-sm text-gray-500">นำส่งเรื่องให้เจ้าหน้าที่จัดรถ</p>
                    <button type="button" :disabled="submitForm.processing" class="rounded-md bg-emerald-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-60" @click="doSubmit">นำส่งเจ้าหน้าที่จัดรถ</button>
                </div>

                <!-- เจ้าหน้าที่: ความเห็นเจ้าหน้าที่ควบคุมยานพาหนะ -->
                <div v-if="canAssign" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-amber-200">
                    <div class="mb-3 text-sm font-semibold text-amber-700">ความเห็นเจ้าหน้าที่ที่ควบคุมยานพาหนะ</div>
                    <div class="space-y-3 text-sm">
                        <div>
                            <label class="mb-1 block font-medium text-gray-700">ควรอนุญาตให้ใช้รถยนต์ส่วนกลาง หมายเลขทะเบียน</label>
                            <select v-model="assignForm.vehicle_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">— เลือกรถ —</option>
                                <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.name }} ({{ v.license_plate }}) · {{ v.seats }} ที่นั่ง</option>
                            </select>
                            <p v-if="assignForm.errors.vehicle_id" class="mt-1 text-xs text-rose-500">{{ assignForm.errors.vehicle_id }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block font-medium text-gray-700">โดยมี ____ ทำหน้าที่พนักงานขับรถ</label>
                            <input v-model="assignForm.driver_name" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <p v-if="assignForm.errors.driver_name" class="mt-1 text-xs text-rose-500">{{ assignForm.errors.driver_name }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block font-medium text-gray-700">หมายเหตุ (ถ้ามี)</label>
                            <input v-model="assignForm.comment" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <button type="button" :disabled="assignForm.processing" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60" @click="doAssign">จัดรถ → เสนอผู้บริหาร</button>
                    </div>
                </div>

                <!-- ผู้บริหาร: คำสั่ง (อนุญาต/ไม่อนุญาต) -->
                <div v-if="canApprove" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-sky-200">
                    <div class="mb-3 text-sm font-semibold text-sky-700 underline">ความเห็นผู้บังคับบัญชา · คำสั่ง</div>
                    <div class="flex gap-6">
                        <label class="inline-flex items-center gap-2 text-sm"><input type="radio" value="approve" v-model="order.decision" class="text-emerald-600 focus:ring-emerald-500" /> อนุญาต</label>
                        <label class="inline-flex items-center gap-2 text-sm"><input type="radio" value="reject" v-model="order.decision" class="text-rose-600 focus:ring-rose-500" /> ไม่อนุญาต</label>
                    </div>
                    <div class="mt-2 text-center text-xs text-gray-500">ความคิดเห็น</div>
                    <textarea v-model="order.comment" rows="2" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :placeholder="order.decision === 'reject' ? 'ระบุเหตุผล (จำเป็น)' : 'ความเห็น (ถ้ามี)'"></textarea>
                    <p v-if="order.errors.comment" class="mt-1 text-xs text-rose-500">{{ order.errors.comment }}</p>
                    <div class="mt-3 text-center">
                        <button type="button" :disabled="order.processing" class="rounded-md px-6 py-2 text-sm font-semibold text-white shadow-sm disabled:opacity-60" :class="order.decision === 'reject' ? 'bg-rose-600 hover:bg-rose-500' : 'bg-emerald-600 hover:bg-emerald-500'" @click="submitOrder">บันทึกเอกสาร</button>
                    </div>
                    <p v-if="order.decision === 'approve'" class="mt-2 text-center text-xs text-gray-400">อนุญาตแล้วระบบจะลงปฏิทินการใช้รถอัตโนมัติ</p>
                </div>

                <!-- เจ้าหน้าที่จัดรถ: กรณีจัดรถไม่ได้ — ส่งกลับผู้ขอ -->
                <div v-if="canAssign" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-rose-200">
                    <div class="mb-2 text-sm font-semibold text-rose-700">กรณีจัดรถไม่ได้ — ส่งกลับผู้ขอ</div>
                    <textarea v-model="rejectForm.comment" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500" placeholder="ระบุเหตุผล (จำเป็น)"></textarea>
                    <p v-if="rejectForm.errors.comment" class="mt-1 text-xs text-rose-500">{{ rejectForm.errors.comment }}</p>
                    <button type="button" :disabled="rejectForm.processing" class="mt-3 rounded-md bg-rose-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-500 disabled:opacity-60" @click="doReject">ส่งกลับผู้ขอ</button>
                </div>

                <div v-if="canCancel" class="text-center">
                    <button type="button" class="text-sm font-medium text-rose-600 hover:underline" @click="cancel">ยกเลิกคำขอนี้</button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
