<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    document: { type: Object, required: true },
    myRouteId: { type: Number, default: null },
    isDirector: { type: Boolean, default: false },
    approverOptions: { type: Array, default: () => [] }, // [{role,label,users:[{id,name}]}]
    canFile: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: false },
    canSignActing: { type: Boolean, default: false }, // รองผอ. ลงนามปฏิบัติราชการแทน ผอ.
    actingDirector: { type: Object, default: null }, // ผู้รักษาการในตำแหน่ง ผอ. (สำหรับ "เสนอรักษาการ")
    sarabanUsers: { type: Array, default: () => [] }, // เจ้าหน้าที่สารบรรณกลาง (โหมด "นำส่งสารบรรณกลาง")
    handedToSaraban: { type: Object, default: null }, // ผู้ที่ถูกนำส่งให้ออกเลขส่งแล้ว
});

const SCHOOL = 'โรงเรียนเศรษฐบุตรบำเพ็ญ';
const flash = computed(() => usePage().props.flash ?? {});

// หัวกระดาษตามหมวดเอกสาร
const HEADINGS = {
    memo: 'บันทึกข้อความ',
    incoming: 'หนังสือรับ',
    outgoing: 'หนังสือส่ง',
    internal_out: 'หนังสือภายใน',
    internal_in: 'หนังสือภายใน',
    general_out: 'เอกสารทั่วไป',
    general_in: 'เอกสารทั่วไป',
    report: 'รายงานโครงการ',
    order: 'คำสั่ง',
};
const heading = computed(() => HEADINGS[props.document.category] ?? 'บันทึกข้อความ');

// หนังสือ 'rejected' แสดงคำว่า "ตีกลับ" → ใช้สถานะกลาง returned
const docBadgeStatus = computed(() => (props.document.status === 'rejected' ? 'returned' : props.document.status));

const fileUrl = (path) => `/storage/${path}`;

// ขั้นที่ดำเนินการแล้ว (แสดงคำสั่งการ + ลายเซ็นต่อท้ายบันทึก)
const actedRoutes = computed(() => props.document.routes.filter((r) => r.status === 'approved' || r.status === 'rejected'));

const printDoc = () => window.print();

// ===== แถบผู้บริหารดำเนินการต่อ =====
const ACTIONS = ['ทราบ', 'ชอบ', 'แจ้ง', 'มอบ', 'ลงนามแล้ว', 'ลงนัด', 'อนุญาต', 'อนุมัติ', 'ไม่อนุมัติ', 'ขอพบ'];
const selected = ref([]);
const note = ref('');
const processing = ref(false);
const submitAction = () => {
    if (!selected.value.length) return; // ต้องเลือกคำสั่งการอย่างน้อย 1 อย่าง (กันส่งเปล่า)
    const isReject = selected.value.includes('ไม่อนุมัติ');
    if (isReject && !note.value.trim()) return; // ไม่อนุมัติต้องระบุเหตุผล
    const comment = ('[' + selected.value.join(', ') + '] ' + note.value).trim();
    processing.value = true;
    router.post(
        route(isReject ? 'saraban.routes.reject' : 'saraban.routes.approve', props.myRouteId),
        { comment },
        { onFinish: () => (processing.value = false) },
    );
};

// ===== แถบเสนอต่อ (หัวหน้ากลุ่ม/รองผอ. — เลือกผู้รับเสนอต่อ) =====
const selectedRole = ref(props.approverOptions[0]?.role ?? null);
const currentGroup = computed(() => props.approverOptions.find((g) => g.role === selectedRole.value) ?? null);
const forwardApproverId = ref(props.approverOptions[0]?.users[0]?.id ?? null);
const forwardNote = ref('');
// ความเห็นมาตรฐาน (เลือก 1) — เติมนำหน้าความคิดเห็นเวลาเสนอต่อ (เหมือนระบบเดิม)
const OPINIONS = ['เห็นควรดำเนินการตามเสนอ', 'เพื่อโปรดพิจารณา', 'เห็นควรอนุมัติ', 'เห็นควรอนุญาต'];
const forwardOpinion = ref('');
watch(selectedRole, () => {
    forwardApproverId.value = currentGroup.value?.users[0]?.id ?? null;
});
const submitForward = () => {
    if (!forwardApproverId.value) return;
    processing.value = true;
    const comment = ((forwardOpinion.value ? '[' + forwardOpinion.value + '] ' : '') + forwardNote.value).trim();
    router.post(
        route('saraban.routes.forward', props.myRouteId),
        { approver_id: forwardApproverId.value, comment },
        { onFinish: () => (processing.value = false) },
    );
};
const submitForwardReject = () => {
    if (!forwardNote.value.trim()) return;
    processing.value = true;
    router.post(
        route('saraban.routes.reject', props.myRouteId),
        { comment: forwardNote.value },
        { onFinish: () => (processing.value = false) },
    );
};
// ลงนาม(ป) — รองผอ.ลงนามปิดเรื่องในนาม "ปฏิบัติราชการแทน ผอ." (ไม่ต้องเสนอต่อ ผอ.)
const submitSignActing = () => {
    if (!confirm('ลงนามปิดเรื่องในนาม "ปฏิบัติราชการแทนผู้อำนวยการ" ?')) return;
    processing.value = true;
    const comment = ('[ลงนาม ปฏิบัติราชการแทน ผอ.] ' + forwardNote.value).trim();
    router.post(
        route('saraban.routes.approve', props.myRouteId),
        { comment },
        { onFinish: () => (processing.value = false) },
    );
};

// เสนอรักษาการ — เสนอต่อผู้รักษาการในตำแหน่ง ผอ. (ผู้ที่ถูกกำหนดไว้)
const submitForwardActing = () => {
    if (!props.actingDirector) return;
    processing.value = true;
    const comment = ((forwardOpinion.value ? '[' + forwardOpinion.value + '] ' : '') + forwardNote.value).trim();
    router.post(
        route('saraban.routes.forward', props.myRouteId),
        { approver_id: props.actingDirector.id, comment },
        { onFinish: () => (processing.value = false) },
    );
};

// แบบ AMSS: เลือกดำเนินการ 1 อย่าง แล้วกด "บันทึกเอกสาร" ปุ่มเดียว
const action = ref(''); // 'forward' | 'acting' | 'sign' | 'reject'
const topApproverLabel = computed(() => (props.approverOptions.length === 1 ? props.approverOptions[0].label : 'ผู้บังคับบัญชา'));
const submitPanel = () => {
    if (action.value === 'forward') submitForward();
    else if (action.value === 'acting') submitForwardActing();
    else if (action.value === 'sign') submitSignActing();
    else if (action.value === 'reject') submitForwardReject();
};

// ===== จัดการเอกสาร (ตามคู่มือ MyOffice หน้า 15) — 2 โหมด =====
//  ① จัดเก็บเอกสาร (กรณีส่งด้วยตนเอง)  ② นำส่งสารบรรณกลาง (กรณีให้ส่งหนังสือ)
const fileMode = ref(props.handedToSaraban ? 'saraban' : 'self');
const FILING_OPTIONS = ['จัดเก็บเข้าแฟ้ม', 'ส่งโรงเรียน', 'ส่งหนังสือเวียน', 'ส่งหน่วยงานอื่นๆ', 'ส่ง เขต/สพฐ.'];
const filingForm = useForm({ filing: ['จัดเก็บเข้าแฟ้ม', 'ส่งโรงเรียน', 'ส่งหนังสือเวียน', 'ส่งหน่วยงานอื่นๆ', 'ส่ง เขต/สพฐ.'].includes(props.document.filing) ? props.document.filing : 'จัดเก็บเข้าแฟ้ม' });
const submitFiling = () => filingForm.post(route('saraban.documents.file', props.document.id), { preserveScroll: true });

const handForm = useForm({ saraban_id: props.handedToSaraban?.id ?? null });
const submitHand = () => handForm.post(route('saraban.documents.hand-to-saraban', props.document.id), { preserveScroll: true });
</script>

<template>
    <Head :title="document.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">รายละเอียดเอกสาร</h2>
                <div class="flex items-center gap-3 print:hidden">
                    <Link v-if="canEdit" :href="route('saraban.documents.edit', document.id)" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">แก้ไข &amp; เสนอใหม่</Link>
                    <SecondaryButton @click="printDoc">พิมพ์เอกสาร</SecondaryButton>
                    <Link :href="route('saraban.documents.index')" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">&larr; กลับแฟ้มเอกสาร</Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 print:hidden">
                    {{ flash.success }}
                </div>

                <!-- ป้ายสถานะ + ความเร่งด่วน -->
                <div class="flex flex-wrap items-center gap-2 print:hidden">
                    <StatusBadge :status="docBadgeStatus" />
                    <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="document.priority_classes">{{ document.priority_label }}</span>
                    <span v-if="document.document_number" class="rounded-full bg-gray-100 px-3 py-1 font-mono text-xs font-medium text-gray-600">เลขทะเบียน {{ document.document_number }}</span>
                </div>

                <!-- กระดาษบันทึกข้อความ -->
                <div id="memo-paper" class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
                    <!-- สำเนาหนังสือเวียน/เอกสารทั่วไป — แบบเรียบ (ไม่มีตราครุฑ/หัวกระดาษราชการ) -->
                    <template v-if="document.is_copy">
                        <h1 class="text-xl font-bold text-gray-900">เรื่อง : {{ document.title }}</h1>
                        <p class="mt-1 text-[15px] text-gray-700"><span class="font-semibold">จาก</span> {{ document.source_name ?? document.creator }} · {{ document.created_thai }}</p>
                        <div class="mt-4">
                            <p class="text-sm font-semibold text-gray-700">รายละเอียด :</p>
                            <div class="rich-content mt-2 leading-relaxed text-gray-700" v-html="document.content || '—'" />
                        </div>
                    </template>

                    <!-- บันทึก/หนังสือราชการเต็มรูปแบบ -->
                    <template v-else>
                        <div class="flex items-center justify-between gap-4">
                            <img src="/images/garuda.png" alt="ตราครุฑ" class="h-20 w-20 shrink-0 object-contain" @error="$event.target.style.visibility = 'hidden'" />
                            <h1 class="text-2xl font-bold text-gray-900">{{ heading }}</h1>
                            <div class="h-20 w-20 shrink-0" />
                        </div>

                        <div class="mt-6 space-y-2 text-[15px] text-gray-800">
                            <p><span class="font-semibold">ส่วนราชการ</span> {{ SCHOOL }}<span v-if="document.division"> &nbsp; {{ document.division }}</span></p>
                            <p>
                                <span class="font-semibold">{{ document.is_received ? 'เลขรับที่' : 'ที่' }}</span> {{ document.document_number || '—' }}
                                <span class="ml-6 font-semibold">วันที่</span> {{ document.created_thai }}
                            </p>
                            <!-- หนังสือรับจากภายนอก: เลขที่หนังสือต้นเรื่อง + ผู้ส่ง -->
                            <p v-if="document.is_received && document.source_number">
                                <span class="font-semibold">เลขที่หนังสือ</span> {{ document.source_number }}
                                <span v-if="document.source_date_thai"> &nbsp; <span class="font-semibold">ลงวันที่</span> {{ document.source_date_thai }}</span>
                            </p>
                            <p v-if="document.is_received && document.source_name"><span class="font-semibold">จาก</span> {{ document.source_name }}</p>
                            <p><span class="font-semibold">เรื่อง</span> {{ document.title }}</p>
                            <p><span class="font-semibold">เรียน</span> ผู้อำนวยการ{{ SCHOOL }}</p>
                        </div>

                        <div class="rich-content mt-5 leading-relaxed text-gray-700" v-html="document.content" />
                    </template>

                    <div v-if="document.attachments?.length" class="mt-5 flex flex-wrap gap-2 print:hidden">
                        <a v-for="(a, i) in document.attachments" :key="a.url ?? i" :href="a.url" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-1.5 text-sm font-medium text-indigo-700 hover:bg-indigo-100">{{ a.name }}</a>
                    </div>

                    <!-- ลายเซ็นผู้เสนอ (ไม่แสดงสำหรับสำเนาหนังสือเวียน/เอกสารทั่วไป) -->
                    <div v-if="!document.is_copy" class="mt-8 text-center">
                        <img v-if="document.creator_signature" :src="document.creator_signature" alt="ลายเซ็น" class="mx-auto h-16 object-contain" @error="$event.target.style.display = 'none'" />
                        <div v-else class="mx-auto h-16 w-40 border-b border-dashed border-gray-200" />
                        <p class="mt-1 text-sm font-medium text-gray-700">{{ document.creator }}</p>
                        <p class="text-sm text-gray-500">{{ SCHOOL }}</p>
                    </div>

                    <!-- คำสั่งการ + ลายเซ็นผู้อนุมัติแต่ละขั้น -->
                    <div v-for="step in actedRoutes" :key="step.step_order" class="mt-8 text-center">
                        <p v-if="step.note" class="text-gray-700">{{ step.note }}</p>
                        <p v-if="step.actions.length" class="font-medium" :class="step.status === 'rejected' ? 'text-rose-600' : 'text-gray-800'">{{ step.actions.join(' · ') }}</p>
                        <p v-else-if="step.status === 'rejected'" class="font-medium text-rose-600">ไม่อนุมัติ</p>
                        <img v-if="step.signature_url" :src="step.signature_url" alt="ลายเซ็น" class="mx-auto mt-1 h-16 object-contain" @error="$event.target.style.display = 'none'" />
                        <p class="mt-1 text-sm font-medium text-gray-700">{{ step.approver }}</p>
                        <p class="text-sm text-gray-500">{{ SCHOOL }}</p>
                        <p v-if="step.acted_thai" class="text-sm text-indigo-600">{{ step.acted_thai }}</p>
                    </div>
                </div>

                <!-- แถบเสนอต่อ (หัวหน้ากลุ่ม/รองผอ.) — เลือกผู้รับเสนอต่อตามคู่มือ -->
                <div v-if="myRouteId && !isDirector" class="overflow-hidden rounded-2xl bg-yellow-50 shadow-sm ring-1 ring-yellow-200 print:hidden">
                    <div class="border-b border-yellow-200 px-6 py-3 text-center text-base font-semibold text-gray-700">เลือกดำเนินการ 1 รายการ</div>
                    <div class="px-6 py-5">
                        <!-- เลือกการดำเนินการ (radio — เลือก 1) -->
                        <div class="flex flex-wrap justify-center gap-x-6 gap-y-2">
                            <label class="inline-flex cursor-pointer items-center gap-1.5 text-[15px]">
                                <input v-model="action" type="radio" value="forward" class="text-indigo-600 focus:ring-indigo-500" />
                                <span class="font-medium text-gray-700">เสนอ{{ topApproverLabel }}</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 text-[15px]" :class="actingDirector ? 'cursor-pointer' : 'cursor-not-allowed opacity-40'">
                                <input v-model="action" type="radio" value="acting" :disabled="!actingDirector" class="text-amber-600 focus:ring-amber-500" />
                                <span class="font-medium text-gray-700">เสนอรักษาการ</span>
                            </label>
                            <label v-if="canSignActing" class="inline-flex cursor-pointer items-center gap-1.5 text-[15px]">
                                <input v-model="action" type="radio" value="sign" class="text-violet-600 focus:ring-violet-500" />
                                <span class="font-medium text-violet-700">ลงนาม(ป)</span>
                            </label>
                            <label class="inline-flex cursor-pointer items-center gap-1.5 text-[15px]">
                                <input v-model="action" type="radio" value="reject" class="text-rose-600 focus:ring-rose-500" />
                                <span class="font-medium text-rose-600">กลับไปแก้ไข</span>
                            </label>
                        </div>

                        <!-- เสนอผู้บังคับบัญชา: เลือกผู้รับ -->
                        <div v-if="action === 'forward'" class="mt-5">
                            <div v-if="approverOptions.length > 1" class="flex flex-wrap justify-center gap-x-5 gap-y-2">
                                <label v-for="g in approverOptions" :key="g.role" class="inline-flex cursor-pointer items-center gap-1.5 text-[15px]">
                                    <input v-model="selectedRole" type="radio" :value="g.role" class="text-indigo-600 focus:ring-indigo-500" />
                                    <span class="text-gray-700">{{ g.label }}</span>
                                </label>
                            </div>
                            <div v-if="currentGroup && currentGroup.users.length > 1" class="mx-auto mt-3 max-w-md space-y-2">
                                <label v-for="u in currentGroup.users" :key="u.id" class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2 text-[15px] transition" :class="forwardApproverId === u.id ? 'border-indigo-400 bg-indigo-50 text-indigo-800' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'">
                                    <input v-model="forwardApproverId" type="radio" :value="u.id" class="text-indigo-600 focus:ring-indigo-500" />
                                    <span>{{ u.name }}</span>
                                </label>
                            </div>
                            <p v-if="approverOptions.length <= 1 && currentGroup && currentGroup.users.length <= 1" class="text-center text-[15px] text-gray-700">เสนอถึง <span class="font-semibold">{{ currentGroup.users[0]?.name }}</span></p>
                        </div>

                        <p v-else-if="action === 'acting'" class="mt-5 text-center text-[15px] text-gray-700">เสนอถึงผู้รักษาการ <span class="font-semibold">{{ actingDirector?.name }}</span></p>
                        <p v-else-if="action === 'sign'" class="mt-5 rounded-lg bg-violet-50 px-4 py-3 text-center text-sm text-violet-700">ลงนามปิดเรื่องในนาม "ปฏิบัติราชการแทนผู้อำนวยการ" — ไม่ต้องเสนอต่อ</p>
                        <p v-else-if="action === 'reject'" class="mt-5 rounded-lg bg-rose-50 px-4 py-3 text-center text-sm text-rose-600">ส่งกลับให้เจ้าของเรื่องแก้ไข (ต้องระบุเหตุผล)</p>

                        <!-- ความเห็น (เฉพาะตอนเสนอต่อ) -->
                        <div v-if="action === 'forward' || action === 'acting'" class="mt-3">
                            <p class="mb-1 text-center text-sm font-medium text-gray-600">ความเห็น</p>
                            <div class="flex flex-wrap justify-center gap-x-4 gap-y-1">
                                <label v-for="c in OPINIONS" :key="c" class="inline-flex cursor-pointer items-center gap-1.5 text-sm text-gray-700">
                                    <input v-model="forwardOpinion" type="radio" :value="c" class="text-indigo-600 focus:ring-indigo-500" />
                                    {{ c }}
                                </label>
                            </div>
                        </div>

                        <p class="mt-4 text-center font-semibold text-gray-600">ความคิดเห็นเพิ่มเติม</p>
                        <textarea v-model="forwardNote" rows="3" class="mx-auto mt-1 block w-full max-w-xl rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :placeholder="action === 'reject' ? 'ระบุเหตุผลที่ตีกลับ (จำเป็น)' : 'ความคิดเห็นเพิ่มเติม (ถ้ามี)'" />

                        <div class="mt-5 flex justify-center">
                            <button
                                type="button"
                                :disabled="processing || !action || (action === 'reject' && !forwardNote.trim()) || (action === 'forward' && !forwardApproverId)"
                                class="rounded-md px-8 py-2 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-50"
                                :class="action === 'reject' ? 'bg-rose-600 hover:bg-rose-700' : action === 'sign' ? 'bg-violet-600 hover:bg-violet-700' : 'bg-emerald-600 hover:bg-emerald-700'"
                                @click="submitPanel"
                            >
                                บันทึกเอกสาร
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ผู้บริหารดำเนินการต่อ (ผอ. = ผู้ลงนามขั้นสุดท้าย) -->
                <div v-if="myRouteId && isDirector" class="overflow-hidden rounded-2xl bg-yellow-50 shadow-sm ring-1 ring-yellow-200 print:hidden">
                    <div class="border-b border-yellow-200 px-6 py-3 text-center text-base font-semibold text-gray-700">ผู้บริหารดำเนินการต่อ</div>
                    <div class="px-6 py-5">
                        <p class="mb-2 text-center text-sm text-gray-500">เลือกคำสั่งการ (เลือกได้หลายอย่าง)</p>
                        <div class="flex flex-wrap justify-center gap-x-5 gap-y-2">
                            <label v-for="a in ACTIONS" :key="a" class="inline-flex cursor-pointer items-center gap-1.5 text-[15px]">
                                <input v-model="selected" type="checkbox" :value="a" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <span :class="a === 'ไม่อนุมัติ' ? 'text-rose-600' : 'text-gray-700'">{{ a }}</span>
                            </label>
                        </div>
                        <p class="mt-6 text-center font-semibold text-rose-600">ความคิดเห็น</p>
                        <textarea v-model="note" rows="4" class="mx-auto mt-1 block w-full max-w-xl rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :placeholder="selected.includes('ไม่อนุมัติ') ? 'ระบุเหตุผลที่ไม่อนุมัติ (จำเป็น)' : 'ความคิดเห็น (ถ้ามี)'" />
                        <div class="mt-4 flex justify-center">
                            <button
                                type="button"
                                :disabled="processing || !selected.length || (selected.includes('ไม่อนุมัติ') && !note.trim())"
                                class="rounded-md px-6 py-2 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-50"
                                :class="selected.includes('ไม่อนุมัติ') ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700'"
                                @click="submitAction"
                            >
                                {{ !selected.length ? 'เลือกคำสั่งการก่อน' : selected.includes('ไม่อนุมัติ') ? 'ยืนยันตีกลับ (ไม่อนุมัติ)' : 'บันทึกคำสั่งการ' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- จัดการเอกสาร — ผู้สร้างหลังอนุมัติครบ (ตามคู่มือ MyOffice หน้า 15: จัดเก็บเอง / นำส่งสารบรรณกลาง) -->
                <div v-if="canFile && !document.is_copy" class="overflow-hidden rounded-2xl bg-yellow-50 shadow-sm ring-1 ring-yellow-200 print:hidden">
                    <div class="border-b border-yellow-200 px-6 py-3 text-center text-base font-semibold text-gray-700">จัดการเอกสาร</div>
                    <div class="px-6 py-5">
                        <!-- เลือกโหมด -->
                        <div class="flex flex-wrap justify-center gap-x-8 gap-y-2">
                            <label class="inline-flex cursor-pointer items-center gap-1.5 text-[15px]">
                                <input v-model="fileMode" type="radio" value="self" class="text-emerald-600 focus:ring-emerald-500" />
                                <span class="font-semibold text-emerald-700">จัดเก็บเอกสาร <span class="font-normal text-gray-500">(กรณีส่งด้วยตนเอง)</span></span>
                            </label>
                            <label v-if="sarabanUsers.length" class="inline-flex cursor-pointer items-center gap-1.5 text-[15px]">
                                <input v-model="fileMode" type="radio" value="saraban" class="text-indigo-600 focus:ring-indigo-500" />
                                <span class="font-semibold text-indigo-700">นำส่งสารบรรณกลาง <span class="font-normal text-gray-500">(กรณีให้ส่งหนังสือ)</span></span>
                            </label>
                        </div>

                        <!-- โหมด ① จัดเก็บด้วยตนเอง -->
                        <div v-if="fileMode === 'self'" class="mt-5 border-t border-yellow-200 pt-5">
                            <div class="flex flex-wrap justify-center gap-x-5 gap-y-2">
                                <label v-for="o in FILING_OPTIONS" :key="o" class="inline-flex cursor-pointer items-center gap-1.5 text-[15px]">
                                    <input v-model="filingForm.filing" type="radio" :value="o" class="text-emerald-600 focus:ring-emerald-500" />
                                    <span class="text-gray-700">{{ o }}</span>
                                </label>
                            </div>
                            <div class="mt-4 flex justify-center">
                                <button :disabled="filingForm.processing || !filingForm.filing" class="rounded-lg bg-emerald-600 px-6 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50" @click="submitFiling">บันทึกจัดเก็บ</button>
                            </div>
                        </div>

                        <!-- โหมด ② นำส่งสารบรรณกลาง -->
                        <div v-else-if="fileMode === 'saraban'" class="mt-5 border-t border-yellow-200 pt-5">
                            <p class="mb-3 text-center text-sm text-gray-600">เลือกเจ้าหน้าที่สารบรรณกลางเพื่อออกเลขส่ง &amp; ส่งหน่วยงานปลายทาง</p>
                            <div class="mx-auto max-w-md space-y-2">
                                <label
                                    v-for="u in sarabanUsers"
                                    :key="u.id"
                                    class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2 text-[15px] transition"
                                    :class="handForm.saraban_id === u.id ? 'border-indigo-400 bg-indigo-50 text-indigo-800' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                                >
                                    <input v-model="handForm.saraban_id" type="radio" :value="u.id" class="text-indigo-600 focus:ring-indigo-500" />
                                    <span>{{ u.name }}</span>
                                </label>
                            </div>
                            <p v-if="handForm.errors.saraban_id" class="mt-2 text-center text-sm text-rose-600">{{ handForm.errors.saraban_id }}</p>
                            <div class="mt-4 flex justify-center">
                                <button :disabled="handForm.processing || !handForm.saraban_id" class="rounded-lg bg-indigo-600 px-6 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50" @click="submitHand">นำส่งสารบรรณกลาง</button>
                            </div>
                        </div>

                        <p v-if="handedToSaraban" class="mt-4 text-center text-sm text-indigo-600">นำส่งสารบรรณกลางแล้ว: {{ handedToSaraban.name }} (รอออกเลขส่ง)</p>
                        <p v-else-if="document.filing" class="mt-4 text-center text-sm text-emerald-600">จัดเก็บแล้ว: {{ document.filing }}</p>
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
    #memo-paper,
    #memo-paper * {
        visibility: visible;
    }
    #memo-paper {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none !important;
    }
}
</style>
