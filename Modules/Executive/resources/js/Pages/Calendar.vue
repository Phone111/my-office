<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    events: { type: Array, default: () => [] },
    audienceOptions: { type: Array, default: () => [] },
    executives: { type: Array, default: () => [] },
    lastEntry: { type: Object, default: () => ({}) },
    canManage: { type: Boolean, default: false },
});

const flash = computed(() => usePage().props.flash ?? {});

const pad = (n) => String(n).padStart(2, '0');
const iso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const todayIso = iso(new Date());

const weekdays = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];

// เดือนที่กำลังแสดง
const current = ref(new Date());
const monthLabel = computed(() =>
    current.value.toLocaleDateString('th-TH', { month: 'long', year: 'numeric' }),
);
const prevMonth = () => (current.value = new Date(current.value.getFullYear(), current.value.getMonth() - 1, 1));
const nextMonth = () => (current.value = new Date(current.value.getFullYear(), current.value.getMonth() + 1, 1));
const goToday = () => (current.value = new Date());

// วันสิ้นสุดของกิจกรรม (สำหรับกิจกรรมข้ามวัน)
const endIso = (e) => (e.end_at ? e.end_at.slice(0, 10) : e.start_date);
const eventsOn = (day) => props.events.filter((e) => e.start_date <= day && endIso(e) >= day);

// ช่องในปฏิทิน (รวมช่องว่างต้นเดือน)
const cells = computed(() => {
    const y = current.value.getFullYear();
    const m = current.value.getMonth();
    const startOffset = new Date(y, m, 1).getDay();
    const daysInMonth = new Date(y, m + 1, 0).getDate();
    const list = [];
    for (let i = 0; i < startOffset; i++) list.push({ blank: true });
    for (let d = 1; d <= daysInMonth; d++) {
        const day = `${y}-${pad(m + 1)}-${pad(d)}`;
        list.push({ day: d, iso: day, isToday: day === todayIso, events: eventsOn(day) });
    }
    while (list.length % 7 !== 0) list.push({ blank: true });
    return list;
});

// วาระในเดือนนี้ (รายการอ่านง่ายใต้ปฏิทิน)
const monthEvents = computed(() => {
    const y = current.value.getFullYear();
    const m = current.value.getMonth();
    const start = `${y}-${pad(m + 1)}-01`;
    const end = `${y}-${pad(m + 1)}-${pad(new Date(y, m + 1, 0).getDate())}`;
    return props.events
        .filter((e) => e.start_date <= end && endIso(e) >= start)
        .sort((a, b) => (a.start_at > b.start_at ? 1 : -1));
});

const timeLabel = (e) => e.time_text || 'ทั้งวัน';
const dayLabel = (d) => new Date(d).toLocaleDateString('th-TH', { weekday: 'short', day: 'numeric', month: 'short' });

/* ---------- รายละเอียด ---------- */
const detail = ref(null);

/* ---------- เพิ่ม/แก้ไข ---------- */
const showForm = ref(false);
const editingId = ref(null);
const form = useForm({ executive_id: '', title: '', location: '', description: '', date: '', end_date: '', start_time: '', end_time: '', all_day: false, time_text: '', days: 1, audience: [] });

// วันที่สิ้นสุด (ตอนแก้ไข) = วันเริ่ม + (จำนวนวัน-1)
const endDateOf = (startIso, days) => {
    const d = new Date(startIso);
    d.setDate(d.getDate() + (Math.max(days, 1) - 1));
    return iso(d);
};
// จำนวนวันจากช่วงวันที่ (ส่งให้ backend)
const computeDays = () => {
    if (!form.date || !form.end_date) return form.days || 1;
    const diff = Math.round((new Date(form.end_date) - new Date(form.date)) / 86400000) + 1;
    return diff >= 1 ? diff : 1;
};

// แปลงเวลาเริ่ม-สิ้นสุด → ข้อความเวลา (เก็บในช่อง time_text เดิม)
const composeTime = () => {
    if (form.all_day || !form.start_time) return '';
    return form.end_time ? `${form.start_time} - ${form.end_time} น.` : `${form.start_time} น.`;
};
// แยกข้อความเวลาเดิม → เวลาเริ่ม/สิ้นสุด (ตอนแก้ไข)
const parseTime = (text) => {
    const m = (text || '').match(/(\d{1,2}[:.]\d{2})(?:\s*-\s*(\d{1,2}[:.]\d{2}))?/);
    if (!m) return { start: '', end: '', allDay: !(text && text.trim()) };
    const norm = (t) => t.replace('.', ':').padStart(5, '0');
    return { start: norm(m[1]), end: m[2] ? norm(m[2]) : '', allDay: false };
};

const openCreate = (day = null) => {
    form.reset();
    form.clearErrors();
    form.date = day ?? todayIso;
    form.end_date = form.date;
    editingId.value = null;
    showForm.value = true;
};
const openEdit = (e) => {
    form.clearErrors();
    editingId.value = e.id;
    form.executive_id = e.executive_id ?? '';
    form.title = e.title;
    form.location = e.location ?? '';
    form.description = e.description ?? '';
    form.date = e.start_date;
    form.end_date = endDateOf(e.start_date, e.days ?? 1);
    const t = parseTime(e.time_text);
    form.start_time = t.start;
    form.end_time = t.end;
    form.all_day = t.allDay;
    form.days = e.days ?? 1;
    form.audience = Array.isArray(e.audience) ? [...e.audience] : [];
    detail.value = null;
    showForm.value = true;
};
const submit = () => {
    const opts = { onSuccess: () => (showForm.value = false), preserveScroll: true };
    form.transform((d) => ({ ...d, time_text: composeTime(), days: computeDays() }));
    if (editingId.value) form.put(route('executive.calendar.update', editingId.value), opts);
    else form.post(route('executive.calendar.store'), opts);
};
const remove = (e) => {
    if (confirm(`ลบวาระ "${e.title}" ?`)) {
        router.delete(route('executive.calendar.destroy', e.id), { preserveScroll: true, onSuccess: () => (detail.value = null) });
    }
};
</script>

<template>
    <Head title="ปฏิทินผู้บริหาร" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ปฏิทินปฏิบัติงานของผู้บริหาร</h2>
                <PrimaryButton v-if="canManage" @click="openCreate()">+ เพิ่มวาระ</PrimaryButton>
                <span v-else class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-500">ดูอย่างเดียว</span>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ flash.success }}
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- ปฏิทินเดือน -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 lg:col-span-2">
                    <!-- แถบเดือน -->
                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                        <button class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" @click="prevMonth">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                        </button>
                        <div class="flex items-center gap-3">
                            <span class="text-base font-bold text-gray-800">{{ monthLabel }}</span>
                            <button class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-100" @click="goToday">วันนี้</button>
                        </div>
                        <button class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" @click="nextMonth">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                        </button>
                    </div>

                    <!-- หัววัน -->
                    <div class="grid grid-cols-7 border-b border-gray-100 bg-gray-50 text-center text-xs font-semibold text-gray-500">
                        <div v-for="(w, i) in weekdays" :key="w" class="py-2" :class="i === 0 ? 'text-rose-500' : i === 6 ? 'text-sky-500' : ''">{{ w }}</div>
                    </div>

                    <!-- ช่องวัน -->
                    <div class="grid grid-cols-7">
                        <div
                            v-for="(c, i) in cells"
                            :key="i"
                            class="min-h-[92px] border-b border-r border-gray-50 p-1.5"
                            :class="[c.blank ? 'bg-gray-50/40' : 'transition hover:bg-indigo-50/30', canManage && !c.blank ? 'cursor-pointer' : '']"
                            @click="canManage && !c.blank ? openCreate(c.iso) : null"
                        >
                            <template v-if="!c.blank">
                                <div class="mb-1 flex justify-end">
                                    <span
                                        class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold"
                                        :class="c.isToday ? 'bg-indigo-600 text-white' : 'text-gray-500'"
                                    >{{ c.day }}</span>
                                </div>
                                <div class="space-y-1">
                                    <button
                                        v-for="e in c.events.slice(0, 3)"
                                        :key="e.id"
                                        class="block w-full truncate rounded bg-indigo-100 px-1.5 py-0.5 text-left text-[11px] font-medium text-indigo-700 hover:bg-indigo-200"
                                        @click.stop="detail = e"
                                    >
                                        {{ e.title }}
                                    </button>
                                    <p v-if="c.events.length > 3" class="px-1.5 text-[10px] text-gray-400">+{{ c.events.length - 3 }} เพิ่มเติม</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- รายการวาระในเดือนนี้ (อ่านง่าย) -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 lg:col-span-1">
                    <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">วาระในเดือน {{ monthLabel }} ({{ monthEvents.length }})</div>
                    <ul class="max-h-[640px] divide-y divide-gray-50 overflow-y-auto">
                        <li v-if="monthEvents.length === 0" class="px-6 py-8 text-center text-sm text-gray-400">ไม่มีวาระในเดือนนี้</li>
                        <li v-for="e in monthEvents" :key="e.id" class="flex items-start gap-4 px-6 py-3.5 hover:bg-gray-50">
                            <div class="w-24 shrink-0 text-sm">
                                <p class="font-semibold text-gray-700">{{ dayLabel(e.start_date) }}</p>
                                <p class="text-xs text-indigo-600">{{ timeLabel(e) }}</p>
                            </div>
                            <button class="min-w-0 flex-1 text-left" @click="detail = e">
                                <p class="font-medium text-gray-900">{{ e.title }}</p>
                                <p v-if="e.executive" class="text-xs text-violet-600">{{ e.executive }}</p>
                                <p v-else-if="e.audience && e.audience.length" class="text-xs text-violet-600">{{ e.audience.join(' · ') }}</p>
                                <p v-if="e.location" class="text-sm text-gray-500">{{ e.location }}</p>
                            </button>
                            <div v-if="canManage" class="flex shrink-0 items-center gap-3 text-sm">
                                <button class="font-medium text-indigo-600 hover:text-indigo-800" @click="openEdit(e)">แก้ไข</button>
                                <button class="font-medium text-red-600 hover:text-red-800" @click="remove(e)">ลบ</button>
                            </div>
                        </li>
                    </ul>
                </div>
                </div>
            </div>
        </div>

        <!-- รายละเอียดวาระ -->
        <Modal :show="detail !== null" max-width="lg" @close="detail = null">
            <div v-if="detail">
                <!-- หัวโมดัล -->
                <div class="flex items-start gap-3 border-b border-gray-100 px-6 py-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-base font-bold text-gray-900">{{ detail.title }}</h2>
                        <p v-if="detail.executive" class="text-xs font-medium text-violet-600">{{ detail.executive }}</p>
                        <p v-else-if="detail.audience && detail.audience.length" class="text-xs font-medium text-violet-600">{{ detail.audience.join(' · ') }}</p>
                    </div>
                </div>

                <!-- เนื้อหา -->
                <div class="space-y-2.5 px-6 py-5 text-sm text-gray-600">
                    <p class="flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l3.75 2.25M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        <span>{{ dayLabel(detail.start_date) }}<span v-if="detail.days > 1"> – {{ detail.days }} วัน</span> · {{ timeLabel(detail) }}</span>
                    </p>
                    <p v-if="detail.location" class="flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                        <span>{{ detail.location }}</span>
                    </p>
                    <p v-if="detail.description" class="rounded-lg bg-gray-50 px-3 py-2 text-gray-500">หมายเหตุ: {{ detail.description }}</p>
                </div>

                <!-- ปุ่ม -->
                <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-3">
                    <template v-if="canManage">
                        <DangerButton @click="remove(detail)">ลบ</DangerButton>
                        <PrimaryButton @click="openEdit(detail)">แก้ไข</PrimaryButton>
                    </template>
                    <SecondaryButton @click="detail = null">ปิด</SecondaryButton>
                </div>
            </div>
        </Modal>

        <!-- เพิ่ม/แก้ไขวาระ -->
        <Modal :show="showForm" max-width="lg" @close="showForm = false">
            <form @submit.prevent="submit">
                <!-- หัวโมดัล -->
                <div class="flex items-center gap-3 border-b border-gray-100 px-6 py-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-900">{{ editingId ? 'แก้ไขวาระผู้บริหาร' : 'เพิ่มวาระผู้บริหาร' }}</h2>
                        <p class="text-xs text-gray-400">บันทึกภารกิจลงปฏิทินปฏิบัติงาน</p>
                    </div>
                </div>

                <!-- เนื้อหา -->
                <div class="max-h-[70vh] space-y-4 overflow-y-auto px-6 py-5">
                    <!-- ผู้ปฏิบัติ -->
                    <div>
                        <InputLabel for="exec" value="ผู้ปฏิบัติ" />
                        <select id="exec" v-model="form.executive_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— เลือกผู้บริหาร —</option>
                            <option v-for="e in executives" :key="e.id" :value="e.id">{{ e.name }}</option>
                        </select>
                        <InputError :message="form.errors.executive_id" class="mt-1" />
                    </div>

                    <!-- เรื่องภารกิจ -->
                    <div>
                        <div class="flex items-center justify-between">
                            <InputLabel for="title" value="เรื่องภารกิจ" />
                            <button v-if="lastEntry?.title" type="button" class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-500 transition hover:bg-indigo-50 hover:text-indigo-600" @click="form.title = lastEntry.title">↻ ล่าสุด</button>
                        </div>
                        <textarea id="title" v-model="form.title" rows="2" placeholder="เช่น ประชุมคณะกรรมการสถานศึกษา" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" autofocus />
                        <InputError :message="form.errors.title" class="mt-1" />
                    </div>

                    <!-- วันและเวลา (จัดกลุ่ม) -->
                    <div class="rounded-xl bg-gray-50 p-3 ring-1 ring-gray-100">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-xs font-semibold text-gray-500">วันและเวลา</p>
                            <label class="flex cursor-pointer items-center gap-1.5 text-xs font-medium text-gray-600">
                                <input v-model="form.all_day" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                ทั้งวัน
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel for="date" value="วันที่เริ่ม" />
                                <TextInput id="date" v-model="form.date" type="date" class="mt-1 block w-full" />
                                <InputError :message="form.errors.date" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="end_date" value="ถึงวันที่" />
                                <TextInput id="end_date" v-model="form.end_date" type="date" :min="form.date" class="mt-1 block w-full" />
                                <InputError :message="form.errors.days" class="mt-1" />
                            </div>
                        </div>
                        <div v-if="!form.all_day" class="mt-3 grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel for="start_time" value="เวลาเริ่ม" />
                                <TextInput id="start_time" v-model="form.start_time" type="time" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel for="end_time" value="ถึงเวลา (ถ้ามี)" />
                                <TextInput id="end_time" v-model="form.end_time" type="time" class="mt-1 block w-full" />
                            </div>
                        </div>
                        <p v-else class="mt-2 text-xs text-gray-400">📅 กิจกรรมตลอดทั้งวัน</p>
                    </div>

                    <!-- สถานที่ -->
                    <div>
                        <div class="flex items-center justify-between">
                            <InputLabel for="location" value="สถานที่" />
                            <button v-if="lastEntry?.location" type="button" class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-500 transition hover:bg-indigo-50 hover:text-indigo-600" @click="form.location = lastEntry.location">↻ ล่าสุด</button>
                        </div>
                        <TextInput id="location" v-model="form.location" type="text" placeholder="เช่น ห้องประชุมสำนักงานเขต" class="mt-1 block w-full" />
                        <InputError :message="form.errors.location" class="mt-1" />
                    </div>

                    <!-- หมายเหตุ -->
                    <div>
                        <InputLabel for="description" value="หมายเหตุ" />
                        <TextInput id="description" v-model="form.description" type="text" placeholder="(ถ้ามี)" class="mt-1 block w-full" />
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>
                </div>

                <!-- ปุ่ม -->
                <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-3">
                    <SecondaryButton type="button" @click="showForm = false">ยกเลิก</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">{{ editingId ? 'บันทึกการแก้ไข' : 'เพิ่มวาระ' }}</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
