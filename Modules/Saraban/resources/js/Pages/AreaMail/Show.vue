<script setup>
import SecondaryButton from '@/Components/SecondaryButton.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    mail: { type: Object, required: true },
    people: { type: Array, default: () => [] },
    groups: { type: Array, default: () => [] },
});
const flash = computed(() => usePage().props.flash?.success);

const statusLabel = { sent: 'รอลงทะเบียนรับ', received: 'ลงทะเบียนรับแล้ว', assigned_group: 'มอบกลุ่มแล้ว รอมอบบุคคล', forwarded: 'มอบในหน่วยงานแล้ว' };

const register = () => {
    if (confirm('ลงทะเบียนรับหนังสือฉบับนี้?')) router.post(route('saraban.area-mail.receive', props.mail.id), {}, { preserveScroll: true });
};
const grp = useForm({ to_group_id: '' });
const assignGroup = () => grp.post(route('saraban.area-mail.assign-group', props.mail.id), { preserveScroll: true });
const fwd = useForm({ assigned_to: '' });
const forward = () => fwd.post(route('saraban.area-mail.forward', props.mail.id), { preserveScroll: true });
</script>

<template>
    <Head title="หนังสือราชการระหว่างหน่วยงาน" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">รายละเอียดหนังสือราชการ</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-lg font-semibold text-gray-900">{{ mail.subject }}</p>
                            <p class="text-sm text-gray-500">{{ mail.from }} → {{ mail.to }}</p>
                            <p v-if="mail.tracking_no" class="mt-0.5 font-mono text-xs font-bold text-indigo-600">เลขติดตาม: {{ mail.tracking_no }}</p>
                        </div>
                        <StatusBadge :status="mail.status" :label="statusLabel[mail.status]" />
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                        <div><dt class="text-gray-400">เลขที่หนังสือ</dt><dd class="text-gray-800">{{ mail.number ?? '—' }}</dd></div>
                        <div><dt class="text-gray-400">ลงวันที่</dt><dd class="text-gray-800">{{ mail.doc_date_thai }}</dd></div>
                        <div v-if="mail.reference" class="col-span-2"><dt class="text-gray-400">อ้างถึง</dt><dd class="text-gray-800">{{ mail.reference }}</dd></div>
                        <div><dt class="text-gray-400">ความสำคัญ</dt><dd class="text-gray-800">{{ mail.priority }}<span v-if="mail.confidential" class="ml-1 text-rose-500">· ลับ</span></dd></div>
                        <div><dt class="text-gray-400">ผู้ส่ง</dt><dd class="text-gray-800">{{ mail.sender ?? '—' }}</dd></div>
                        <div v-if="mail.receive_number"><dt class="text-gray-400">เลขทะเบียนรับ</dt><dd class="font-mono font-semibold text-indigo-700">{{ mail.receive_number }}</dd></div>
                        <div v-if="mail.received_thai"><dt class="text-gray-400">วันที่รับ</dt><dd class="text-gray-800">{{ mail.received_thai }}</dd></div>
                        <div v-if="mail.to_group"><dt class="text-gray-400">มอบกลุ่มงาน</dt><dd class="text-gray-800">{{ mail.to_group }}</dd></div>
                        <div v-if="mail.assignee"><dt class="text-gray-400">มอบให้</dt><dd class="text-gray-800">{{ mail.assignee }}</dd></div>
                    </dl>

                    <div v-if="mail.detail" class="mt-4 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-700 whitespace-pre-line">{{ mail.detail }}</div>

                    <div v-if="mail.files && mail.files.length" class="mt-4">
                        <p class="mb-1 text-sm font-medium text-gray-700">ไฟล์แนบ</p>
                        <ul class="space-y-1">
                            <li v-for="(f, i) in mail.files" :key="i">
                                <a :href="f.url" target="_blank" class="text-sm text-indigo-600 hover:underline">{{ f.name }}</a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- ไทม์ไลน์สถานะ -->
                <div v-if="mail.timeline && mail.timeline.length" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <p class="mb-3 text-sm font-semibold text-gray-700">สถานะการเดินทางของหนังสือ</p>
                    <ol class="space-y-0 border-l-2 border-gray-100">
                        <li v-for="(ev, i) in mail.timeline" :key="i" class="relative pb-4 pl-6 last:pb-0">
                            <span class="absolute -left-[7px] top-1 h-3 w-3 rounded-full ring-2 ring-white" :class="ev.key === 'sent' ? 'bg-indigo-500' : ev.key === 'received' ? 'bg-emerald-500' : ev.key === 'assigned_group' ? 'bg-sky-500' : 'bg-amber-500'" />
                            <p class="text-sm font-semibold text-gray-800">{{ ev.label }}</p>
                            <p class="text-sm text-gray-500">{{ ev.at }} <span v-if="ev.time">เวลา {{ ev.time }} น.</span></p>
                            <p v-if="ev.who || ev.where" class="text-sm text-gray-400"><span v-if="ev.who">โดย {{ ev.who }}</span><span v-if="ev.who && ev.where"> · </span><span v-if="ev.where">{{ ev.where }}</span></p>
                        </li>
                    </ol>
                </div>

                <!-- การดำเนินการของผู้รับ (เส้นทาง AMSS: สารบรรณกลาง → กลุ่ม → บุคคล) -->
                <div v-if="mail.can_receive || mail.can_assign_group || mail.can_forward" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-indigo-100">
                    <p class="mb-3 text-sm font-semibold text-gray-700">การดำเนินการ (หน่วยงานผู้รับ)</p>

                    <!-- ขั้นที่ 1: สารบรรณกลางลงทะเบียนรับ -->
                    <button v-if="mail.can_receive" class="rounded-md bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700" @click="register">ลงทะเบียนรับ</button>

                    <!-- ขั้นที่ 2 (เขต): สารบรรณกลางมอบกลุ่มงาน -->
                    <div v-if="mail.can_assign_group" class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">มอบกลุ่มงานรับไปดำเนินการ</label>
                            <select v-model="grp.to_group_id" class="w-64 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">— เลือกกลุ่มงาน —</option>
                                <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
                            </select>
                            <p v-if="grp.errors.to_group_id" class="mt-1 text-xs text-rose-500">{{ grp.errors.to_group_id }}</p>
                        </div>
                        <button :disabled="!grp.to_group_id || grp.processing" class="rounded-md bg-sky-600 px-5 py-2 text-sm font-semibold text-white hover:bg-sky-500 disabled:opacity-50" @click="assignGroup">มอบกลุ่ม</button>
                    </div>

                    <!-- ขั้นที่ 3: มอบบุคคล (สารบรรณกลุ่ม/สารบรรณสถานศึกษา) -->
                    <div v-if="mail.can_forward" class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">มอบหนังสือให้บุคลากร</label>
                            <select v-model="fwd.assigned_to" class="w-64 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">— เลือกบุคลากร —</option>
                                <option v-for="p in people" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                            <p v-if="fwd.errors.assigned_to" class="mt-1 text-xs text-rose-500">{{ fwd.errors.assigned_to }}</p>
                        </div>
                        <button :disabled="!fwd.assigned_to || fwd.processing" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50" @click="forward">มอบหนังสือ</button>
                    </div>
                </div>

                <SecondaryButton @click="router.visit(document.referrer || route('saraban.area-mail.inbox'))">← กลับ</SecondaryButton>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
