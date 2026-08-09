<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    trip: { type: Object, required: true },
    myRouteId: { type: Number, default: null },
    acted: { type: Array, default: () => [] },
    canPrint: { type: Boolean, default: false },
});

const SCHOOL = 'โรงเรียนเศรษฐบุตรบำเพ็ญ';
const flash = computed(() => usePage().props.flash ?? {});

const comment = ref('');
const processing = ref(false);
const act = (approve) => {
    if (!approve && !comment.value.trim()) return;
    processing.value = true;
    router.post(
        route(approve ? 'official-trips.approve' : 'official-trips.reject', props.myRouteId),
        { comment: comment.value },
        { onFinish: () => (processing.value = false) },
    );
};

const vehicleText = computed(() => {
    let v = props.trip.vehicle;
    if (props.trip.vehicle_plate) v += ' ทะเบียน ' + props.trip.vehicle_plate;
    if (props.trip.vehicle_other) v += ' (' + props.trip.vehicle_other + ')';
    return v;
});

const print = () => window.print();
</script>

<template>
    <Head title="ขอไปราชการ" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between print:hidden">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">บันทึกขออนุมัติไปราชการ</h2>
                <div class="flex gap-2">
                    <button class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200" @click="print">
                        {{ canPrint ? 'พิมพ์คำสั่งไปราชการ' : 'พิมพ์' }}
                    </button>
                    <Link :href="myRouteId ? route('official-trips.inbox') : route('official-trips.index')" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                        กลับ
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 print:hidden">{{ flash.error }}</div>

                <div class="flex flex-wrap items-center gap-2 print:hidden">
                    <StatusBadge :status="trip.status" />
                </div>

                <!-- กระดาษบันทึกข้อความ -->
                <div id="trip-paper" class="rounded-2xl bg-white p-10 shadow-sm ring-1 ring-gray-100 print:rounded-none print:shadow-none print:ring-0">
                    <div class="flex items-center justify-between gap-4">
                        <img
    :src="'/images/garuda.png'"
    alt="ตราครุฑ"
    class="h-20 w-20 shrink-0 object-contain"
/>
                        <h1 class="text-2xl font-bold text-gray-900">บันทึกข้อความ</h1>
                        <div class="h-20 w-20 shrink-0" />
                    </div>

                    <div class="mt-6 space-y-1 text-[15px] text-gray-800">
                        <p><span class="font-semibold">ส่วนราชการ</span> {{ SCHOOL }}</p>
                        <p><span class="font-semibold">ที่</span> {{ trip.document_number || '—' }} <span class="ml-6 font-semibold">วันที่</span> {{ trip.created_thai }}</p>
                        <p><span class="font-semibold">เรื่อง</span> {{ trip.title }}</p>
                        <p><span class="font-semibold">เรียน</span> ผู้อำนวยการ{{ SCHOOL }}</p>
                    </div>

                    <p class="mt-4 indent-12 leading-9 text-gray-800">
                        ด้วยข้าพเจ้า {{ trip.requester }} ตำแหน่ง {{ trip.requester_position }} สังกัด {{ SCHOOL }}
                        <template v-if="trip.companions"> พร้อมด้วย {{ trip.companions }}</template>
                        มีความประสงค์ขออนุมัติไปราชการเพื่อ {{ trip.purpose }}
                        <template v-if="trip.reference"> ตามคำสั่ง/หนังสือ {{ trip.reference }}</template>
                        ณ {{ trip.destination }}
                        โดยออกเดินทาง {{ trip.depart_thai }} และกลับ {{ trip.return_thai }}
                        เดินทางโดย {{ vehicleText }}
                        <template v-if="trip.budget_source"> โดยขออนุมัติเบิกค่าใช้จ่ายในการเดินทางจากเงินงบประมาณ {{ trip.budget_source }}</template>
                    </p>
                    <p class="mt-2 indent-12 text-gray-800">จึงเรียนมาเพื่อโปรดพิจารณา</p>

                    <!-- ลงชื่อผู้ขอ -->
                    <div class="mt-8 text-center text-gray-800">
                        <div class="flex h-14 items-center justify-center">
                            <img v-if="trip.signature_url" :src="trip.signature_url" alt="ลายเซ็น" class="h-14 object-contain" />
                        </div>
                        <p>( {{ trip.requester }} )</p>
                        <p class="text-sm text-gray-500">{{ trip.requester_position }} · ผู้ขออนุญาต</p>
                    </div>

                    <!-- ความเห็น/อนุมัติ -->
                    <div v-for="(a, i) in acted" :key="i" class="mt-8 text-center text-gray-800">
                        <p v-if="a.status === 'rejected'" class="font-medium text-rose-600">ไม่อนุมัติ</p>
                        <p v-else class="font-medium text-gray-800">{{ a.role_label === 'ผู้อนุมัติ' ? 'อนุมัติ' : 'เห็นควรอนุมัติ' }}</p>
                        <p v-if="a.comment" class="text-gray-600">{{ a.comment }}</p>
                        <div class="mt-1 flex h-12 items-center justify-center">
                            <img v-if="a.signature_url" :src="a.signature_url" alt="ลายเซ็น" class="h-12 object-contain" />
                        </div>
                        <p>( {{ a.approver }} )</p>
                        <p class="text-sm text-gray-500">{{ a.role_label }}</p>
                        <p v-if="a.acted_thai" class="text-sm text-indigo-600">{{ a.acted_thai }}</p>
                    </div>

                    <div v-if="trip.attachments.length" class="mt-6 border-t border-gray-100 pt-4 print:hidden">
                        <p class="mb-2 text-sm font-semibold text-gray-700">เอกสารแนบ</p>
                        <div class="flex flex-wrap gap-2">
                            <a v-for="(a, i) in trip.attachments" :key="a.url ?? i" :href="a.url" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-1.5 text-sm font-medium text-indigo-700 hover:bg-indigo-100">{{ a.name }}</a>
                        </div>
                    </div>
                </div>

                <!-- แผงดำเนินการ (ผู้อนุมัติ) -->
                <div v-if="myRouteId" class="overflow-hidden rounded-2xl bg-yellow-50 shadow-sm ring-1 ring-yellow-200 print:hidden">
                    <div class="border-b border-yellow-200 px-6 py-3 text-center text-base font-semibold text-gray-700">ความเห็น / คำสั่ง</div>
                    <div class="px-6 py-5">
                        <textarea v-model="comment" rows="3" placeholder="ความคิดเห็น (จำเป็นเมื่อตีกลับ)" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <div class="mt-4 flex justify-center gap-3">
                            <button :disabled="processing" class="rounded-lg bg-rose-600 px-5 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-50" @click="act(false)">ไม่อนุมัติ / ตีกลับ</button>
                            <PrimaryButton :disabled="processing" @click="act(true)">เห็นชอบ / อนุมัติ</PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@media print {
    body * { visibility: hidden; }
    #trip-paper, #trip-paper * { visibility: visible; }
    #trip-paper { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>
