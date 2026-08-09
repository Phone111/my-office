<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    circular: { type: Object, required: true },
});

// ฟอร์มตอบรับเข้าประชุม
const form = useForm({
    status: props.circular.my_rsvp?.status ?? '',
    note: props.circular.my_rsvp?.note ?? '',
});

const respond = (status) => {
    form.status = status;
    form.post(route('saraban.circulars.respond', props.circular.id), { preserveScroll: true });
};

const rsvpColor = (s) =>
    s === 'accept' ? 'text-emerald-600' : s === 'decline' ? 'text-rose-600' : s === 'delegate' ? 'text-amber-600' : 'text-gray-400';

const showNote = ref(false);
</script>

<template>
    <Head :title="circular.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ circular.is_meeting ? 'หนังสือเชิญประชุม' : 'หนังสือเวียนภายใน' }}</h2>
                <div class="flex items-center gap-3">
                    <Link :href="route('saraban.circulars.create', { from: circular.id })" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">↪ ส่งหนังสือเวียนต่อ</Link>
                    <Link :href="route('saraban.circulars.inbox')" class="text-sm font-medium text-gray-500 hover:text-gray-700">ออกจากหน้านี้</Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- เนื้อหา -->
                <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
                    <span v-if="circular.is_meeting" class="mb-2 inline-flex items-center gap-1 rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-semibold text-violet-700">📅 หนังสือเชิญประชุม</span>
                    <h1 class="text-xl font-bold text-gray-900">เรื่อง : {{ circular.title }} ({{ circular.attachments.length }})</h1>
                    <p class="mt-1 text-sm text-gray-400">
                        จาก {{ circular.sender ?? '—' }}<span v-if="circular.sender_group"> · {{ circular.sender_group }}</span> · {{ circular.created_at }}
                    </p>

                    <!-- ข้อมูลการประชุม -->
                    <div v-if="circular.is_meeting" class="mt-4 grid gap-2 rounded-xl bg-violet-50 px-4 py-3 text-sm text-violet-900 sm:grid-cols-2">
                        <p>🗓️ <span class="font-semibold">วันเวลา:</span> {{ circular.meeting_at ?? '—' }}</p>
                        <p>📍 <span class="font-semibold">สถานที่:</span> {{ circular.meeting_place ?? '—' }}</p>
                    </div>

                    <div class="mt-5">
                        <p class="text-sm font-semibold text-gray-700">รายละเอียด :</p>
                        <div class="rich-content mt-2 leading-relaxed text-gray-700" v-html="circular.content || '—'" />
                    </div>

                    <!-- ตอบรับเข้าประชุม (ฝั่งผู้รับ) -->
                    <div v-if="circular.is_meeting && circular.can_respond" class="mt-6 rounded-xl border border-violet-200 bg-white p-4">
                        <p class="mb-2 text-sm font-semibold text-gray-800">การตอบรับของท่าน</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="(label, key) in circular.rsvp_labels" :key="key"
                                type="button"
                                :disabled="form.processing"
                                class="rounded-lg px-4 py-2 text-sm font-semibold ring-1 transition disabled:opacity-50"
                                :class="form.status === key
                                    ? (key === 'accept' ? 'bg-emerald-600 text-white ring-emerald-600' : key === 'decline' ? 'bg-rose-600 text-white ring-rose-600' : 'bg-amber-500 text-white ring-amber-500')
                                    : 'bg-gray-50 text-gray-600 ring-gray-200 hover:bg-gray-100'"
                                @click="showNote ? respond(key) : (form.status = key, showNote = true)"
                            >{{ label }}</button>
                        </div>
                        <div v-if="showNote || circular.my_rsvp" class="mt-3">
                            <input v-model="form.note" type="text" maxlength="255" placeholder="หมายเหตุ (เช่น ชื่อผู้แทน / เหตุผล)" class="w-full rounded-lg border-gray-300 text-sm focus:border-violet-400 focus:ring-violet-400" />
                            <button type="button" :disabled="!form.status || form.processing" class="mt-2 rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700 disabled:opacity-50" @click="respond(form.status)">บันทึกการตอบรับ</button>
                        </div>
                        <p v-if="circular.my_rsvp" class="mt-2 text-sm" :class="rsvpColor(circular.my_rsvp.status)">
                            ✓ ท่านตอบ: <span class="font-semibold">{{ circular.rsvp_labels[circular.my_rsvp.status] }}</span>
                            <span v-if="circular.my_rsvp.note" class="text-gray-500"> · {{ circular.my_rsvp.note }}</span>
                        </p>
                    </div>

                    <!-- สรุปผลการตอบรับ -->
                    <div v-if="circular.is_meeting" class="mt-4 flex flex-wrap gap-3 text-sm">
                        <span class="rounded-lg bg-emerald-50 px-3 py-1 font-medium text-emerald-700">เข้าร่วม {{ circular.rsvp_counts.accept }}</span>
                        <span class="rounded-lg bg-rose-50 px-3 py-1 font-medium text-rose-700">ไม่เข้าร่วม {{ circular.rsvp_counts.decline }}</span>
                        <span class="rounded-lg bg-amber-50 px-3 py-1 font-medium text-amber-700">มอบผู้แทน {{ circular.rsvp_counts.delegate }}</span>
                        <span class="rounded-lg bg-gray-50 px-3 py-1 font-medium text-gray-500">ยังไม่ตอบ {{ circular.no_reply_count }}</span>
                    </div>

                    <!-- บุคลากรที่รับหนังสือ -->
                    <div class="mt-6 border-t border-gray-100 pt-4">
                        <p class="text-sm font-semibold text-gray-700">บุคลากรที่รับหนังสือ</p>
                        <ol class="mt-2 space-y-1 text-[15px]">
                            <li v-for="(r, i) in circular.recipients" :key="i" class="flex flex-wrap items-center gap-2">
                                <span class="text-gray-700">{{ i + 1 }}. {{ r.name }}</span>
                                <span v-if="circular.is_meeting && r.rsvp" class="text-sm font-medium" :class="rsvpColor(r.rsvp)">
                                    [{{ circular.rsvp_labels[r.rsvp] }}<span v-if="r.rsvp_note"> · {{ r.rsvp_note }}</span>]
                                </span>
                                <span v-else :class="r.is_read ? 'text-emerald-600' : 'text-amber-600'" class="text-sm font-medium">
                                    {{ r.is_read ? 'รับแล้ว' : 'ยังไม่อ่าน' }}
                                </span>
                            </li>
                        </ol>
                        <p
                            class="mt-3 text-[15px] font-semibold"
                            :class="circular.read_count === circular.total ? 'text-emerald-600' : 'text-amber-600'"
                        >
                            {{ circular.read_count === circular.total ? 'บุคลากรรับหนังสือครบแล้ว' : `รับแล้ว ${circular.read_count} / ${circular.total} คน` }}
                        </p>
                    </div>

                    <div v-if="circular.attachments.length" class="mt-6 border-t border-gray-100 pt-4">
                        <p class="mb-2 text-sm font-semibold text-gray-700">เอกสารแนบ</p>
                        <div class="flex flex-wrap gap-2">
                            <a v-for="(a, i) in circular.attachments" :key="a.url ?? i" :href="a.url" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-1.5 text-sm font-medium text-indigo-700 hover:bg-indigo-100">{{ a.name }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
