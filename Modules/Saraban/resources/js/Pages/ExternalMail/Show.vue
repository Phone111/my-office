<script setup>
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    mail: { type: Object, required: true },
    groups: { type: Array, default: () => [] },
    people: { type: Array, default: () => [] },
});
const flash = computed(() => usePage().props.flash?.success);

const statusLabel = { received: 'รอมอบกลุ่มงาน', assigned: 'มอบแล้ว' };

const assign = useForm({ assigned_group_id: props.mail.assigned_group_id ?? '', assigned_to: '', note: '' });
const submitAssign = () => assign.post(route('saraban.external-mail.assign', props.mail.id), { preserveScroll: true });
</script>

<template>
    <Head title="หนังสือรับจากภายนอก" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">รายละเอียดหนังสือรับ (จากภายนอก)</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-lg font-semibold text-gray-900">{{ mail.subject }}<span v-if="mail.confidential" class="ml-1 text-sm text-rose-500">[ลับ]</span></p>
                            <p class="text-sm text-gray-500">จาก: {{ mail.source }}<span v-if="mail.source_name"> · {{ mail.source_name }}</span></p>
                        </div>
                        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600">{{ statusLabel[mail.status] ?? mail.status }}</span>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                        <div><dt class="text-gray-400">เลขทะเบียนรับ</dt><dd class="font-mono font-semibold text-indigo-700">{{ mail.receive_label }}</dd></div>
                        <div><dt class="text-gray-400">วันที่รับ</dt><dd class="text-gray-800">{{ mail.received_thai }}</dd></div>
                        <div><dt class="text-gray-400">เลขที่หนังสือ (ต้นทาง)</dt><dd class="text-gray-800">{{ mail.number ?? '—' }}</dd></div>
                        <div><dt class="text-gray-400">ลงวันที่</dt><dd class="text-gray-800">{{ mail.doc_date_thai ?? '—' }}</dd></div>
                        <div><dt class="text-gray-400">ความสำคัญ</dt><dd class="text-gray-800">{{ mail.priority }}</dd></div>
                        <div><dt class="text-gray-400">ผู้ลงทะเบียนรับ</dt><dd class="text-gray-800">{{ mail.receiver ?? '—' }}</dd></div>
                        <div v-if="mail.group"><dt class="text-gray-400">มอบกลุ่มงาน</dt><dd class="text-gray-800">{{ mail.group }}</dd></div>
                        <div v-if="mail.assignee"><dt class="text-gray-400">มอบบุคคล</dt><dd class="text-gray-800">{{ mail.assignee }}</dd></div>
                        <div v-if="mail.note" class="col-span-2"><dt class="text-gray-400">บันทึก/สั่งการ</dt><dd class="text-gray-800">{{ mail.note }}</dd></div>
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

                <!-- มอบกลุ่มงาน/บุคคล -->
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-indigo-100">
                    <p class="mb-3 text-sm font-semibold text-gray-700">มอบหมายหนังสือ</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">มอบกลุ่มงาน</label>
                            <select v-model="assign.assigned_group_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">— ไม่ระบุ —</option>
                                <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">มอบบุคคล (ถ้ามี)</label>
                            <select v-model="assign.assigned_to" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">— ไม่ระบุ —</option>
                                <option v-for="p in people" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="mb-1 block text-sm font-medium text-gray-700">บันทึก/สั่งการ</label>
                        <input v-model="assign.note" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button :disabled="assign.processing" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50" @click="submitAssign">บันทึกการมอบหมาย</button>
                    </div>
                </div>

                <SecondaryButton @click="router.visit(route('saraban.external-mail.index'))">← กลับทะเบียนรับ</SecondaryButton>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
