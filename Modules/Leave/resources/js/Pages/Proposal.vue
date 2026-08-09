<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    leave: { type: Object, required: true },
    fiscalYear: { type: Number, default: 0 },
    fiscalYears: { type: Array, default: () => [] },
    stats: { type: Array, default: () => [] },
    acted: { type: Array, default: () => [] },
    canSubmit: { type: Boolean, default: false },
    myRole: { type: String, default: null },
    myRouteId: { type: Number, default: null },
    approverOptions: { type: Array, default: () => [] },
    back: { type: Object, default: null },
});

const SCHOOL = 'โรงเรียนเศรษฐบุตรบำเพ็ญ';
const flash = computed(() => usePage().props.flash ?? {});

const typeShort = computed(() => (props.leave.type?.startsWith('ลา') ? props.leave.type.slice(2) : props.leave.type));

// ผู้ยื่นเสนอแฟ้ม
const submitForm = useForm({});
const submit = () => submitForm.post(route('leave.requests.submit', props.leave.id));

// เจ้าหน้าที่วันลา เสนอผู้อนุญาต
const showApprovers = ref(false);
const fy = ref(props.fiscalYears[0] ?? props.fiscalYear);
const forwardForm = useForm({ approver_id: null });
const doForward = () => forwardForm.post(route('leave.requests.forward', props.leave.id));

// ผู้อนุญาต สั่งการ
const ACTIONS = ['อนุญาต', 'ไม่อนุญาต', 'ขอพบ'];
const decision = ref(''); // เลือกได้ครั้งละ 1 คำสั่ง (กันอนุมัติเงียบ/เลือกขัดกัน)
const note = ref('');
const deciding = ref(false);
const decide = () => {
    if (!decision.value) return; // ต้องเลือกคำสั่งก่อน
    const isReject = decision.value === 'ไม่อนุญาต';
    if (isReject && !note.value.trim()) return; // ไม่อนุญาตต้องระบุเหตุผล
    const comment = ('[' + decision.value + '] ' + note.value).trim();
    deciding.value = true;
    router.post(route(isReject ? 'leave.requests.reject' : 'leave.requests.approve', props.myRouteId), { comment }, { onFinish: () => (deciding.value = false) });
};

const print = () => window.print();
</script>

<template>
    <Head :title="leave.subject" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between print:hidden">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ leave.subject }}</h2>
                <div class="flex gap-2">
                    <button class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200" @click="print">พิมพ์</button>
                    <Link :href="back ? back.url : (myRole ? route('leave.requests.inbox') : route('leave.requests.folder'))" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                        {{ back ? back.label : (myRole ? 'กลับแฟ้มตรวจสอบ' : 'กลับแฟ้มการลา') }}
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 print:hidden">{{ flash.error }}</div>

                <!-- กระดาษบันทึกขออนุญาตลา -->
                <div id="leave-paper" class="rounded-2xl bg-white p-10 shadow-sm ring-1 ring-gray-100 print:rounded-none print:shadow-none print:ring-0">
                    <div class="flex justify-end gap-10 text-sm text-gray-700">
                        <div>ที่ ................................</div>
                        <div>เขียนที่ {{ leave.written_at ?? SCHOOL }}</div>
                    </div>

                    <p class="mt-4 text-center text-gray-800">วันที่ {{ leave.written_thai }}</p>

                    <div class="mt-6 space-y-1 text-gray-800">
                        <p><span class="font-semibold">เรื่อง</span>&nbsp;&nbsp;{{ leave.subject }}</p>
                        <p><span class="font-semibold">เรียน</span>&nbsp;&nbsp;ผู้อำนวยการ{{ SCHOOL }}</p>
                    </div>

                    <p class="mt-4 indent-12 leading-9 text-gray-800">
                        ข้าพเจ้า {{ leave.requester }} ตำแหน่ง {{ leave.requester_position }} สังกัด {{ SCHOOL }}
                        ขอลา{{ typeShort }} เนื่องจาก {{ leave.reason }}
                        ตั้งแต่วันที่ {{ leave.start_thai }} ถึงวันที่ {{ leave.end_thai }}
                        มีกำหนด {{ leave.total_days }} วัน
                        <template v-if="leave.contact_address"> ในระหว่างลาติดต่อข้าพเจ้าได้ที่ {{ leave.contact_address }}</template>
                        <template v-if="leave.phone"> โทรศัพท์ {{ leave.phone }}</template>
                    </p>

                    <!-- ลงชื่อผู้ขอ -->
                    <div class="mt-8 text-center text-gray-800">
                        <p>ขอแสดงความนับถือ</p>
                        <div class="mt-1 flex h-14 items-center justify-center">
                            <img v-if="leave.signature_url" :src="leave.signature_url" alt="ลายเซ็น" class="h-14 object-contain" />
                        </div>
                        <p>( {{ leave.requester }} )</p>
                        <p>{{ leave.requester_position }} {{ SCHOOL }}</p>
                    </div>

                    <!-- ผู้ตรวจสอบ / ผู้อนุญาตที่ดำเนินการแล้ว -->
                    <div v-for="(a, i) in acted" :key="i" class="mt-8 text-center text-gray-800">
                        <p v-if="a.actions.length" class="font-medium" :class="a.status === 'rejected' ? 'text-rose-600' : 'text-gray-800'">{{ a.actions.join(' · ') }}</p>
                        <p v-else-if="a.status === 'rejected'" class="font-medium text-rose-600">ไม่อนุญาต</p>
                        <p v-if="a.note" class="text-gray-600">{{ a.note }}</p>
                        <div class="mt-1 flex h-12 items-center justify-center">
                            <img v-if="a.signature_url" :src="a.signature_url" alt="ลายเซ็น" class="h-12 object-contain" />
                        </div>
                        <p>( {{ a.approver }} )</p>
                        <p class="text-sm text-gray-500">{{ a.role_label }}</p>
                        <p v-if="a.acted_thai" class="text-sm text-indigo-600">{{ a.acted_thai }}</p>
                    </div>

                    <!-- สถิติ + แผงดำเนินการ -->
                    <div class="mt-10 grid gap-6 md:grid-cols-2">
                        <div>
                            <p class="mb-2 text-sm font-semibold text-gray-700">สถิติการลาในปีงบประมาณ {{ fiscalYear }}</p>
                            <table class="w-full border border-gray-200 text-center text-xs">
                                <thead class="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th class="border border-gray-200 px-2 py-1 text-left">ประเภทการลา</th>
                                        <th class="border border-gray-200 px-2 py-1">ลามาแล้ว<br />(วันทำการ)</th>
                                        <th class="border border-gray-200 px-2 py-1">ลาครั้งนี้<br />(วันทำการ)</th>
                                        <th class="border border-gray-200 px-2 py-1">รวมเป็น<br />(วันทำการ)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="s in stats" :key="s.name" class="text-gray-700">
                                        <td class="border border-gray-200 px-2 py-1 text-left">{{ s.name }}</td>
                                        <td class="border border-gray-200 px-2 py-1">{{ s.taken }}</td>
                                        <td class="border border-gray-200 px-2 py-1">{{ s.this_time }}</td>
                                        <td class="border border-gray-200 px-2 py-1">{{ s.total }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="print:hidden">
                            <!-- ผู้ยื่น: เสนอแฟ้ม -->
                            <template v-if="canSubmit">
                                <p class="mb-2 text-sm font-semibold text-gray-700">ดำเนินการเสนอแฟ้ม</p>
                                <div class="rounded-xl bg-emerald-50 p-4 ring-1 ring-emerald-100">
                                    <p class="text-sm text-gray-600">ส่งใบลาให้เจ้าหน้าที่งานวันลาตรวจสอบ</p>
                                    <PrimaryButton class="mt-4 w-full justify-center" :disabled="submitForm.processing" @click="submit">ส่งให้เจ้าหน้าที่ตรวจสอบ</PrimaryButton>
                                </div>
                            </template>

                            <!-- เจ้าหน้าที่วันลา: เสนอผู้อนุญาต -->
                            <template v-else-if="myRole === 'officer'">
                                <p class="mb-2 text-sm font-semibold text-emerald-700">สำหรับเจ้าหน้าที่วันลา</p>
                                <div class="rounded-xl bg-emerald-50 p-4 ring-1 ring-emerald-100">
                                    <label class="block text-sm text-gray-700">ยื่นใบลา ปีงบประมาณ</label>
                                    <select v-model="fy" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option v-for="y in fiscalYears" :key="y" :value="y">{{ y }}</option>
                                    </select>
                                    <label class="mt-3 flex items-center gap-2 text-sm text-gray-700">
                                        <input v-model="showApprovers" type="checkbox" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                                        เสนอผู้อนุญาตการลา
                                    </label>
                                    <div v-if="showApprovers" class="mt-2 space-y-1">
                                        <label v-for="o in approverOptions" :key="o.id" class="flex items-center gap-2 text-sm text-gray-700">
                                            <input v-model="forwardForm.approver_id" type="radio" :value="o.id" class="text-indigo-600 focus:ring-indigo-500" />
                                            {{ o.name }}
                                        </label>
                                    </div>
                                    <PrimaryButton class="mt-4 w-full justify-center" :disabled="forwardForm.processing || !forwardForm.approver_id" @click="doForward">เสนอผู้อนุญาต</PrimaryButton>
                                </div>
                            </template>

                            <!-- ผู้อนุญาต: คำสั่ง -->
                            <template v-else-if="myRole === 'approver'">
                                <p class="mb-2 text-sm font-semibold text-rose-600">ความเห็นผู้บังคับบัญชา / คำสั่ง</p>
                                <div class="rounded-xl bg-yellow-50 p-4 ring-1 ring-yellow-200">
                                    <p class="mb-2 text-sm font-medium text-gray-700">เลือกคำสั่ง (เลือก 1 อย่าง)</p>
                                    <div class="flex flex-wrap gap-x-4 gap-y-2">
                                        <label v-for="a in ACTIONS" :key="a" class="inline-flex items-center gap-1.5 text-sm">
                                            <input v-model="decision" type="radio" :value="a" class="border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                            <span :class="a === 'ไม่อนุญาต' ? 'text-rose-600' : a === 'อนุญาต' ? 'text-emerald-700' : 'text-gray-700'">{{ a }}</span>
                                        </label>
                                    </div>
                                    <textarea v-model="note" rows="3" :placeholder="decision === 'ไม่อนุญาต' ? 'ระบุเหตุผลที่ไม่อนุญาต (จำเป็น)' : 'ความคิดเห็นเพิ่มเติม (ถ้ามี)'" class="mt-3 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    <button
                                        type="button"
                                        :disabled="!decision || deciding || (decision === 'ไม่อนุญาต' && !note.trim())"
                                        class="mt-3 w-full rounded-md px-4 py-2 text-center text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-50"
                                        :class="decision === 'ไม่อนุญาต' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700'"
                                        @click="decide"
                                    >
                                        {{ !decision ? 'เลือกคำสั่งก่อน' : decision === 'อนุญาต' ? 'อนุมัติใบลา' : decision === 'ไม่อนุญาต' ? 'ยืนยันไม่อนุญาต' : 'บันทึก (ขอพบ)' }}
                                    </button>
                                </div>
                            </template>

                            <!-- สถานะอื่น -->
                            <template v-else>
                                <p class="mb-2 text-sm font-semibold text-gray-700">สถานะ</p>
                                <div class="rounded-xl bg-gray-50 p-4 text-sm text-gray-500 ring-1 ring-gray-100">อยู่ระหว่างการพิจารณา/ดำเนินการ</div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #leave-paper,
    #leave-paper * {
        visibility: visible;
    }
    #leave-paper {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>
