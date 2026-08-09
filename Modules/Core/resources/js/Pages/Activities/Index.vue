<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    events: { type: Array, default: () => [] },
    month: { type: Number, default: 1 },
    year: { type: Number, default: 2026 },
    yearThai: { type: Number, default: 2569 },
    monthName: { type: String, default: '' },
    visibilities: { type: Object, default: () => ({}) },
    groups: { type: Array, default: () => [] },
    canSeeExec: { type: Boolean, default: false },
});

const weekdays = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];
const pad = (n) => String(n).padStart(2, '0');

// ชั้นข้อมูล (เปิด/ปิดได้)
const TYPES = {
    activity: { label: 'กิจกรรม', dot: 'bg-indigo-500', chip: 'bg-indigo-100 text-indigo-700' },
    room: { label: 'จองห้องประชุม', dot: 'bg-emerald-500', chip: 'bg-emerald-100 text-emerald-700' },
    vehicle: { label: 'จองรถ', dot: 'bg-orange-500', chip: 'bg-orange-100 text-orange-700' },
    exec: { label: 'วาระผู้บริหาร', dot: 'bg-violet-500', chip: 'bg-violet-100 text-violet-700' },
};
const layers = ref({ activity: true, room: true, vehicle: true, exec: true });

// คลิกวัน → ป๊อปอัปเลือกว่าจะเพิ่มอะไร
const dayChoose = ref(null);
const dayThai = (iso) => (iso ? new Date(iso + 'T00:00:00').toLocaleDateString('th-TH', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '');
const goTo = (name, params) => {
    dayChoose.value = null;
    router.visit(route(name, params));
};

// ซ่อนชั้น "วาระผู้บริหาร" ถ้าไม่มีสิทธิ์
const availableTypes = computed(() => {
    const out = { ...TYPES };
    if (!props.canSeeExec) delete out.exec;
    return out;
});

const shown = computed(() => props.events.filter((e) => layers.value[e.type]));
const eventsOn = (day) => shown.value.filter((e) => e.date <= day && e.end_date >= day);

// ช่องปฏิทิน
const cells = computed(() => {
    const m = props.month - 1;
    const startOffset = new Date(props.year, m, 1).getDay();
    const days = new Date(props.year, m + 1, 0).getDate();
    const list = [];
    for (let i = 0; i < startOffset; i++) list.push({ blank: true });
    const todayIso = new Date().toISOString().slice(0, 10);
    for (let d = 1; d <= days; d++) {
        const iso = `${props.year}-${pad(props.month)}-${pad(d)}`;
        list.push({ day: d, iso, isToday: iso === todayIso, events: eventsOn(iso) });
    }
    while (list.length % 7 !== 0) list.push({ blank: true });
    return list;
});

// นำทางเดือน
const go = (mo, yr) => router.get(route('activities.index'), { month: mo, year: yr }, { preserveScroll: true });
const prev = () => (props.month === 1 ? go(12, props.year - 1) : go(props.month - 1, props.year));
const next = () => (props.month === 12 ? go(1, props.year + 1) : go(props.month + 1, props.year));

/* ---------- รายละเอียด ---------- */
const detail = ref(null);

/* ---------- เพิ่ม/แก้ไขกิจกรรม ---------- */
const showForm = ref(false);
const editingId = ref(null);
const form = useForm({
    title: '', location: '', event_date: '', all_day: false,
    start_time: '09:00', end_time: '', detail: '', visibility: 'all', group_id: '',
});
const openCreate = (day = null) => {
    form.reset();
    form.clearErrors();
    form.event_date = day ?? new Date().toISOString().slice(0, 10);
    editingId.value = null;
    showForm.value = true;
};
const openEdit = (e) => {
    detail.value = null;
    form.clearErrors();
    editingId.value = e.id;
    form.title = e.title;
    form.location = e.location ?? '';
    form.event_date = e.event_date;
    form.all_day = e.all_day;
    form.start_time = e.start_time || '09:00';
    form.end_time = e.end_time ?? '';
    form.detail = e.detail ?? '';
    form.visibility = e.visibility ?? 'all';
    form.group_id = e.group_id ?? '';
    showForm.value = true;
};
const submit = () => {
    const opts = { onSuccess: () => (showForm.value = false), preserveScroll: true };
    if (editingId.value) form.put(route('activities.update', editingId.value), opts);
    else form.post(route('activities.store'), opts);
};
const remove = (e) => {
    if (confirm(`ลบกิจกรรม "${e.title}" ?`)) router.delete(route('activities.destroy', e.id), { preserveScroll: true, onSuccess: () => (detail.value = null) });
};
</script>

<template>
    <Head title="ปฏิทินภาพรวม" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">ปฏิทินภาพรวม</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
                <!-- แถบเดือน + ชั้น -->
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center gap-2">
                        <button type="button" class="rounded-md border border-gray-200 px-2 py-1 text-gray-500 hover:bg-gray-50" @click="prev">‹</button>
                        <span class="min-w-[9rem] text-center text-base font-bold text-gray-800">{{ monthName }} {{ yearThai }}</span>
                        <button type="button" class="rounded-md border border-gray-200 px-2 py-1 text-gray-500 hover:bg-gray-50" @click="next">›</button>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <label v-for="(t, key) in availableTypes" :key="key" class="flex cursor-pointer items-center gap-1.5 text-xs font-medium text-gray-600">
                            <input v-model="layers[key]" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            <span class="h-2.5 w-2.5 rounded-full" :class="t.dot"></span>{{ t.label }}
                        </label>
                    </div>
                </div>

                <!-- ปฏิทินเดือน -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="grid grid-cols-7 border-b border-gray-100 bg-gray-50 text-center text-xs font-semibold text-gray-500">
                        <div v-for="(w, i) in weekdays" :key="w" class="py-2" :class="i === 0 ? 'text-rose-500' : i === 6 ? 'text-sky-500' : ''">{{ w }}</div>
                    </div>
                    <div class="grid grid-cols-7">
                        <div
                            v-for="(c, i) in cells"
                            :key="i"
                            class="min-h-[96px] border-b border-r border-gray-50 p-1.5"
                            :class="c.blank ? 'bg-gray-50/40' : 'cursor-pointer transition hover:bg-indigo-50/30'"
                            @click="!c.blank ? (dayChoose = c.iso) : null"
                        >
                            <template v-if="!c.blank">
                                <div class="mb-1 flex justify-end">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold" :class="c.isToday ? 'bg-indigo-600 text-white' : 'text-gray-500'">{{ c.day }}</span>
                                </div>
                                <div class="space-y-1">
                                    <button
                                        v-for="e in c.events.slice(0, 3)"
                                        :key="e.key"
                                        type="button"
                                        class="flex w-full items-center gap-1 truncate rounded px-1.5 py-0.5 text-left text-[11px] font-medium"
                                        :class="TYPES[e.type].chip"
                                        @click.stop="detail = e"
                                    >
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full" :class="TYPES[e.type].dot"></span>
                                        <span class="truncate">{{ e.title }}</span>
                                    </button>
                                    <p v-if="c.events.length > 3" class="px-1.5 text-[10px] text-gray-400">+{{ c.events.length - 3 }} เพิ่มเติม</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <p class="text-center text-xs text-gray-400">คลิกวันเพื่อเพิ่มรายการ · คลิกกิจกรรมเพื่อดูรายละเอียด</p>
            </div>
        </div>

        <!-- รายละเอียด -->
        <Modal :show="detail !== null" max-width="lg" @close="detail = null">
            <div v-if="detail">
                <div class="flex items-start gap-3 border-b border-gray-100 px-6 py-4">
                    <span class="mt-1 h-3 w-3 shrink-0 rounded-full" :class="TYPES[detail.type].dot"></span>
                    <div class="min-w-0">
                        <h2 class="text-base font-bold text-gray-900">{{ detail.title }}</h2>
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="TYPES[detail.type].chip">{{ detail.type_label }}</span>
                    </div>
                </div>
                <div class="space-y-2 px-6 py-5 text-sm text-gray-600">
                    <p>🕒 {{ detail.time_label }}</p>
                    <p v-if="detail.location">📍 {{ detail.location }}</p>
                    <p v-if="detail.owner">👤 {{ detail.owner }}<span v-if="detail.group"> · {{ detail.group }}</span></p>
                    <p v-if="detail.detail" class="rounded-lg bg-gray-50 px-3 py-2 text-gray-500">{{ detail.detail }}</p>
                </div>
                <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-3">
                    <SecondaryButton @click="detail = null">ปิด</SecondaryButton>
                </div>
            </div>
        </Modal>

        <!-- เลือกเพิ่มรายการในวันที่กด -->
        <Modal :show="dayChoose !== null" max-width="sm" @close="dayChoose = null">
            <div class="p-6">
                <h3 class="text-base font-bold text-gray-900">เพิ่มรายการ</h3>
                <p class="mt-0.5 text-sm text-gray-500">{{ dayThai(dayChoose) }}</p>
                <div class="mt-4 space-y-2">
                    <button type="button" class="flex w-full items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100" @click="goTo('booking.index', { date: dayChoose, kind: 'room' })">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> จองห้องประชุม
                    </button>
                    <button type="button" class="flex w-full items-center gap-2 rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm font-semibold text-orange-700 transition hover:bg-orange-100" @click="goTo('booking.index', { date: dayChoose, kind: 'vehicle' })">
                        <span class="h-2.5 w-2.5 rounded-full bg-orange-500"></span> ขอใช้รถยนต์
                    </button>
                    <button v-if="canSeeExec" type="button" class="flex w-full items-center gap-2 rounded-xl border border-violet-200 bg-violet-50 px-4 py-3 text-sm font-semibold text-violet-700 transition hover:bg-violet-100" @click="goTo('executive.calendar.index', { date: dayChoose })">
                        <span class="h-2.5 w-2.5 rounded-full bg-violet-500"></span> เพิ่มวาระผู้บริหาร
                    </button>
                </div>
                <div class="mt-4 flex justify-end">
                    <SecondaryButton @click="dayChoose = null">ปิด</SecondaryButton>
                </div>
            </div>
        </Modal>

        <!-- เพิ่ม/แก้ไขกิจกรรม -->
        <Modal :show="showForm" max-width="lg" @close="showForm = false">
            <form @submit.prevent="submit">
                <div class="flex items-center gap-3 border-b border-gray-100 px-6 py-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                    </div>
                    <h2 class="text-base font-bold text-gray-900">{{ editingId ? 'แก้ไขกิจกรรม' : 'เพิ่มกิจกรรม' }}</h2>
                </div>

                <div class="max-h-[70vh] space-y-4 overflow-y-auto px-6 py-5">
                    <div>
                        <InputLabel for="title" value="เรื่อง/กิจกรรม" />
                        <TextInput id="title" v-model="form.title" type="text" placeholder="เช่น ประชุมกลุ่มบริหารวิชาการ" class="mt-1 block w-full" autofocus />
                        <InputError :message="form.errors.title" class="mt-1" />
                    </div>

                    <div class="rounded-xl bg-gray-50 p-3 ring-1 ring-gray-100">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-xs font-semibold text-gray-500">วันและเวลา</p>
                            <label class="flex cursor-pointer items-center gap-1.5 text-xs font-medium text-gray-600">
                                <input v-model="form.all_day" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" /> ทั้งวัน
                            </label>
                        </div>
                        <div>
                            <InputLabel for="date" value="วันที่" />
                            <TextInput id="date" v-model="form.event_date" type="date" class="mt-1 block w-full" />
                            <InputError :message="form.errors.event_date" class="mt-1" />
                        </div>
                        <div v-if="!form.all_day" class="mt-3 grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel for="st" value="เวลาเริ่ม" />
                                <TextInput id="st" v-model="form.start_time" type="time" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <InputLabel for="et" value="ถึงเวลา (ถ้ามี)" />
                                <TextInput id="et" v-model="form.end_time" type="time" class="mt-1 block w-full" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <InputLabel for="loc" value="สถานที่ (ถ้ามี)" />
                        <TextInput id="loc" v-model="form.location" type="text" class="mt-1 block w-full" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel for="vis" value="ใครเห็นได้" />
                            <select id="vis" v-model="form.visibility" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="(label, code) in visibilities" :key="code" :value="code">{{ label }}</option>
                            </select>
                        </div>
                        <div v-if="form.visibility === 'group'">
                            <InputLabel for="grp" value="เลือกกลุ่ม" />
                            <select id="grp" v-model="form.group_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">— เลือกกลุ่ม —</option>
                                <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
                            </select>
                            <InputError :message="form.errors.group_id" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="detail" value="รายละเอียด (ถ้ามี)" />
                        <textarea id="detail" v-model="form.detail" rows="2" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-3">
                    <SecondaryButton type="button" @click="showForm = false">ยกเลิก</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">{{ editingId ? 'บันทึกการแก้ไข' : 'เพิ่มกิจกรรม' }}</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
