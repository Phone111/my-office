<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import TimeSelect from '@/Components/TimeSelect.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { thaiDate } from '@/utils/format';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    vehicles: { type: Array, default: () => [] },
    rooms: { type: Array, default: () => [] },
    divisions: { type: Array, default: () => [] },
    upcoming: { type: Array, default: () => [] },
    myBookings: { type: Array, default: () => [] },
    calendarBookings: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});
const userName = computed(() => usePage().props.auth?.user?.name ?? '');

const fuelOptions = [
    { value: 'central', label: 'ส่วนกลาง' },
    { value: 'project', label: 'โครงการ' },
    { value: 'user', label: 'ผู้ใช้' },
];

const form = useForm({
    kind: 'vehicle',
    resource_id: null,
    date: '',
    end_date: '',
    start_time: '',
    end_time: '',
    purpose: '',
    division: '',
    written_date: new Date().toISOString().slice(0, 10),
    companions: '',
    destination: '',
    passengers: null,
    fuel_source: 'central',
    attendees: null,
    file: null,
});

// รับค่าจาก URL (?kind=room&date=YYYY-MM-DD) เมื่อเด้งมาจากปฏิทิน
const urlParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
if (urlParams?.get('kind') === 'room' || urlParams?.get('kind') === 'vehicle') {
    form.kind = urlParams.get('kind');
}
if (urlParams?.get('date')) {
    form.date = urlParams.get('date');
}

// จำนวนวัน (คำนวณจากช่วงวันที่)
const dayCount = computed(() => {
    if (!form.date) return 0;
    const end = form.end_date || form.date;
    const diff = Math.floor((new Date(end) - new Date(form.date)) / 86400000);
    return diff >= 0 ? diff + 1 : 0;
});

// รายการทรัพยากรตามชนิดที่เลือก
const resources = computed(() => (form.kind === 'vehicle' ? props.vehicles : props.rooms));

// ตรวจการจองซ้ำแบบทันที: รถ/ห้องเดียวกัน + ช่วงเวลาทับกัน
const toDate = (s) => new Date(String(s).replace(' ', 'T'));
const conflict = computed(() => {
    if (!form.resource_id || !form.date || !form.start_time || !form.end_time) return null;
    const newStart = toDate(`${form.date} ${form.start_time}`);
    const newEnd = toDate(`${form.end_date || form.date} ${form.end_time}`);
    if (!(newEnd > newStart)) return null;
    return (
        props.calendarBookings.find(
            (b) =>
                b.kind === form.kind &&
                b.bookable_id === form.resource_id &&
                toDate(b.start_at) < newEnd &&
                toDate(b.end_at) > newStart,
        ) ?? null
    );
});

// เมื่อเปลี่ยนชนิด ให้รีเซ็ตทรัพยากรที่เลือก
watch(
    () => form.kind,
    () => (form.resource_id = null),
);

/* ---------- ปฏิทินการจอง (ตามประเภท/ทรัพยากรที่เลือก) ---------- */
const pad = (n) => String(n).padStart(2, '0');
const isoOf = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const todayIso = isoOf(new Date());
const weekdays = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];

const current = ref(form.date ? new Date(form.date) : new Date());
const monthLabel = computed(() => current.value.toLocaleDateString('th-TH', { month: 'long', year: 'numeric' }));
const prevMonth = () => (current.value = new Date(current.value.getFullYear(), current.value.getMonth() - 1, 1));
const nextMonth = () => (current.value = new Date(current.value.getFullYear(), current.value.getMonth() + 1, 1));
const goToday = () => (current.value = new Date());

// กรองตามประเภทที่เลือก + ถ้าเลือกทรัพยากรเจาะจงก็กรองอีกชั้น
const calItems = computed(() =>
    props.calendarBookings.filter(
        (b) => b.kind === form.kind && (!form.resource_id || b.bookable_id === form.resource_id),
    ),
);
const endIso = (b) => (b.end_at ? b.end_at.slice(0, 10) : b.start_date);
const itemsOn = (day) => calItems.value.filter((b) => b.start_date <= day && endIso(b) >= day);

const cells = computed(() => {
    const y = current.value.getFullYear();
    const m = current.value.getMonth();
    const startOffset = new Date(y, m, 1).getDay();
    const daysInMonth = new Date(y, m + 1, 0).getDate();
    const list = [];
    for (let i = 0; i < startOffset; i++) list.push({ blank: true });
    for (let d = 1; d <= daysInMonth; d++) {
        const day = `${y}-${pad(m + 1)}-${pad(d)}`;
        list.push({ day: d, iso: day, isToday: day === todayIso, isPast: day < todayIso, items: itemsOn(day) });
    }
    while (list.length % 7 !== 0) list.push({ blank: true });
    return list;
});

const detail = ref(null);
// กดวันบนปฏิทิน → เลือกวันมาจอง — ห้ามเลือกวันที่ผ่านมาแล้ว
// จองรถ: คลิกครั้งแรก = วันเริ่ม, คลิกครั้งถัดไป = วันสิ้นสุด (เลือกเป็นช่วง)
// จองห้อง: เลือกวันเดียว
const pickDay = (iso) => {
    if (iso < todayIso) return;

    if (form.kind !== 'vehicle') {
        form.date = iso;
        form.end_date = '';
        return;
    }

    // เริ่มช่วงใหม่: ถ้ายังไม่มีวันเริ่ม / มีช่วงครบแล้ว / คลิกก่อนวันเริ่ม
    if (!form.date || form.end_date || iso < form.date) {
        form.date = iso;
        form.end_date = '';
    } else {
        // ตั้งวันสิ้นสุด (คลิกวันเดียวกัน = วันเดียว)
        form.end_date = iso === form.date ? '' : iso;
    }
};

// คลาสไฮไลต์ช่วงวันที่เลือกบนปฏิทิน
const dayClass = (iso) => {
    if (!form.date) return '';
    if (iso === form.date || iso === form.end_date) return 'bg-indigo-100 ring-2 ring-inset ring-indigo-400';
    if (form.end_date && iso > form.date && iso < form.end_date) return 'bg-indigo-50';
    return '';
};
const timeLabel = (b) => b.start_at.slice(11, 16) + (b.end_at ? '–' + b.end_at.slice(11, 16) : '') + ' น.';
const dayLabel = (d) => new Date(d).toLocaleDateString('th-TH', { weekday: 'short', day: 'numeric', month: 'short' });

const submit = () =>
    form.post(route('booking.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => form.reset('date', 'end_date', 'start_time', 'end_time', 'purpose', 'companions', 'destination', 'passengers', 'attendees', 'file'),
    });

const cancel = (b) => {
    if (confirm('ยกเลิกการจองนี้?')) {
        router.delete(route('booking.cancel', b.id), { preserveScroll: true });
    }
};

const statusMeta = (status) =>
    ({
        pending: { label: 'รอเสนอแฟ้ม', classes: 'bg-gray-100 text-gray-600' },
        submitted: { label: 'เจ้าหน้าที่ตรวจสอบรถ', classes: 'bg-amber-100 text-amber-700' },
        assigned: { label: 'รอผู้บริหารอนุมัติ', classes: 'bg-sky-100 text-sky-700' },
        booked: { label: 'จองแล้ว', classes: 'bg-green-100 text-green-700' },
        rejected: { label: 'ไม่อนุมัติ', classes: 'bg-rose-100 text-rose-700' },
        cancelled: { label: 'ยกเลิก', classes: 'bg-gray-100 text-gray-500' },
    })[status] ?? { label: status, classes: 'bg-gray-100 text-gray-600' };
</script>

<template>
    <Head title="จองทรัพยากร" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">จองทรัพยากร</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div
                    v-if="flash.success"
                    class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
                >
                    {{ flash.success }}
                </div>

                <!-- สวิตช์หลัก: เลือกประเภทการจอง -->
                <div class="rounded-2xl bg-white p-2 shadow-sm ring-1 ring-gray-100">
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-xl px-4 py-3 text-base font-semibold transition"
                            :class="form.kind === 'vehicle' ? 'bg-indigo-600 text-white shadow' : 'text-gray-600 hover:bg-gray-50'"
                            @click="form.kind = 'vehicle'"
                        >
                            จองรถยนต์
                        </button>
                        <button
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-xl px-4 py-3 text-base font-semibold transition"
                            :class="form.kind === 'room' ? 'bg-indigo-600 text-white shadow' : 'text-gray-600 hover:bg-gray-50'"
                            @click="form.kind = 'room'"
                        >
                            จองห้องประชุม
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- ฟอร์มจอง -->
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100 lg:col-span-1">
                        <h3 v-if="form.kind === 'vehicle'" class="mb-4 text-center text-base font-semibold text-rose-600">
                            บันทึกขอจองรถ ของ {{ userName }}
                        </h3>
                        <h3 v-else class="mb-4 flex items-center gap-2 font-semibold text-gray-800">
                            จองห้องประชุม
                        </h3>
                        <form class="space-y-4" @submit.prevent="submit">

                            <!-- เลือกทรัพยากร -->
                            <div>
                                <InputLabel for="resource" value="เลือกทรัพยากร" />
                                <select
                                    id="resource"
                                    v-model="form.resource_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option :value="null" disabled>— เลือก —</option>
                                    <option v-for="r in resources" :key="r.id" :value="r.id">
                                        {{ r.name }}
                                        <template v-if="form.kind === 'vehicle'">({{ r.license_plate }})</template>
                                        <template v-else>({{ r.capacity }} ที่นั่ง)</template>
                                    </option>
                                </select>
                                <InputError :message="form.errors.resource_id" class="mt-2" />
                            </div>

                            <!-- ===== จองรถ (แบบละเอียด) ===== -->
                            <template v-if="form.kind === 'vehicle'">
                                <div>
                                    <InputLabel for="division" value="ส่วนราชการ" />
                                    <select id="division" v-model="form.division" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">— เลือก —</option>
                                        <option v-for="d in divisions" :key="d.id" :value="d.name">{{ d.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel for="wdate" value="วันที่เขียน" />
                                    <TextInput id="wdate" v-model="form.written_date" type="date" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <InputLabel for="companions" value="ข้าพเจ้าพร้อมด้วย" />
                                    <textarea id="companions" v-model="form.companions" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="รายชื่อผู้ร่วมเดินทาง (ถ้ามี)" />
                                </div>
                                <div>
                                    <InputLabel for="purpose" value="ขออนุญาตใช้รถเพื่อ" />
                                    <TextInput id="purpose" v-model="form.purpose" type="text" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.purpose" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="destination" value="ณ (สถานที่ปลายทาง)" />
                                    <TextInput id="destination" v-model="form.destination" type="text" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <InputLabel for="passengers" value="มีคนนั่งจำนวน (คน)" />
                                    <TextInput id="passengers" v-model="form.passengers" type="number" min="0" class="mt-1 block w-full" />
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <InputLabel for="vdate" value="ในวันที่" />
                                        <TextInput id="vdate" v-model="form.date" type="date" :min="todayIso" class="mt-1 block w-full" />
                                        <InputError :message="form.errors.date" class="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel value="เวลา" />
                                        <TimeSelect v-model="form.start_time" />
                                        <InputError :message="form.errors.start_time" class="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel for="vend_date" value="ถึงวันที่" />
                                        <TextInput id="vend_date" v-model="form.end_date" type="date" :min="form.date || todayIso" class="mt-1 block w-full" />
                                        <InputError :message="form.errors.end_date" class="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel value="เวลา" />
                                        <TimeSelect v-model="form.end_time" />
                                        <InputError :message="form.errors.end_time" class="mt-2" />
                                    </div>
                                </div>
                                <p class="text-sm text-gray-500">รวม <span class="font-semibold text-indigo-600">{{ dayCount }}</span> วัน</p>
                                <div>
                                    <InputLabel value="ใช้น้ำมันเชื้อเพลิงจาก" />
                                    <div class="mt-1 flex flex-wrap gap-4">
                                        <label v-for="f in fuelOptions" :key="f.value" class="inline-flex items-center gap-2 text-sm text-gray-700">
                                            <input v-model="form.fuel_source" type="radio" :value="f.value" class="text-indigo-600 focus:ring-indigo-500" />
                                            {{ f.label }}
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <InputLabel for="vfile" value="แนบเอกสาร" />
                                    <input id="vfile" type="file" class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100" @input="form.file = $event.target.files[0]" />
                                    <InputError :message="form.errors.file" class="mt-2" />
                                </div>
                            </template>

                            <!-- ===== จองห้องประชุม ===== -->
                            <template v-else>
                                <div>
                                    <InputLabel for="date" value="เลือกวันที่" />
                                    <TextInput id="date" v-model="form.date" type="date" :min="todayIso" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.date" class="mt-2" />
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <InputLabel value="เวลา" />
                                        <TimeSelect v-model="form.start_time" />
                                        <InputError :message="form.errors.start_time" class="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel value="ถึง" />
                                        <TimeSelect v-model="form.end_time" />
                                        <InputError :message="form.errors.end_time" class="mt-2" />
                                    </div>
                                </div>
                                <div>
                                    <InputLabel for="rpurpose" value="เรื่อง" />
                                    <textarea id="rpurpose" v-model="form.purpose" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="หัวข้อ/วัตถุประสงค์การประชุม" />
                                    <InputError :message="form.errors.purpose" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="attendees" value="จำนวนผู้เข้าประชุม (คน)" />
                                    <TextInput id="attendees" v-model="form.attendees" type="number" min="0" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.attendees" class="mt-2" />
                                </div>
                            </template>

                            <!-- เตือนการจองซ้ำ -->
                            <div v-if="conflict" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                                {{ form.kind === 'vehicle' ? 'รถคันนี้' : 'ห้องนี้' }}ถูกจองในช่วงเวลาที่ทับกันแล้ว
                                ({{ conflict.start_at.slice(11, 16) }}–{{ conflict.end_at.slice(11, 16) }} น. · โดย {{ conflict.user }})
                                กรุณาเลือกเวลาหรือวันอื่น
                            </div>

                            <PrimaryButton class="w-full justify-center" :disabled="form.processing || !form.resource_id || !!conflict">
                                {{ form.kind === 'vehicle' ? 'บันทึกคำขอจองรถ' : 'บันทึกจองห้อง' }}
                            </PrimaryButton>
                        </form>
                    </div>

                    <!-- ปฏิทินการจอง (ตามประเภทที่เลือก) -->
                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 lg:col-span-2">
                        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                            <button class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" @click="prevMonth">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                            </button>
                            <div class="flex items-center gap-2 text-center">
                                <span class="text-sm font-bold text-gray-800">ปฏิทิน{{ form.kind === 'vehicle' ? 'รถยนต์' : 'ห้องประชุม' }} · {{ monthLabel }}</span>
                                <button class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-600 hover:bg-indigo-100" @click="goToday">วันนี้</button>
                            </div>
                            <button class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" @click="nextMonth">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                            </button>
                        </div>
                        <div class="grid grid-cols-7 border-b border-gray-100 bg-gray-50 text-center text-xs font-semibold text-gray-500">
                            <div v-for="(w, i) in weekdays" :key="w" class="py-1.5" :class="i === 0 ? 'text-rose-500' : i === 6 ? 'text-sky-500' : ''">{{ w }}</div>
                        </div>
                        <div class="grid grid-cols-7">
                            <div
                                v-for="(c, i) in cells"
                                :key="i"
                                class="min-h-[80px] border-b border-r border-gray-50 p-1 transition"
                                :class="[
                                    c.blank ? 'bg-gray-50/40' : '',
                                    !c.blank && c.isPast ? 'cursor-not-allowed bg-gray-50/60' : '',
                                    !c.blank && !c.isPast ? 'cursor-pointer hover:bg-indigo-50/50' : '',
                                    !c.blank ? dayClass(c.iso) : '',
                                ]"
                                @click="!c.blank && !c.isPast ? pickDay(c.iso) : null"
                            >
                                <template v-if="!c.blank">
                                    <div class="mb-0.5 flex justify-end">
                                        <span class="flex h-5 w-5 items-center justify-center rounded-full text-[11px] font-semibold" :class="c.isToday ? 'bg-indigo-600 text-white' : c.isPast ? 'text-gray-300' : 'text-gray-500'">{{ c.day }}</span>
                                    </div>
                                    <div class="space-y-0.5">
                                        <button
                                            v-for="b in c.items.slice(0, 2)"
                                            :key="b.id"
                                            class="block w-full truncate rounded bg-emerald-100 px-1 py-0.5 text-left text-[10px] font-medium text-emerald-700 hover:bg-emerald-200"
                                            @click.stop="detail = b"
                                        >
                                            {{ b.start_at.slice(11, 16) }} {{ b.resource_name }}
                                        </button>
                                        <p v-if="c.items.length > 2" class="px-1 text-[9px] text-gray-400">+{{ c.items.length - 2 }}</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <p class="px-4 py-2 text-xs text-gray-400">
                            <template v-if="form.kind === 'vehicle'">คลิกวันเริ่ม แล้วคลิกอีกครั้งเพื่อเลือก “ถึงวันที่” (จองรถหลายวันได้)</template>
                            <template v-else>คลิกวันบนปฏิทินเพื่อเลือกวันจอง</template>
                            · แถบเขียว = ช่วงที่ถูกจองแล้ว
                        </p>
                    </div>
                </div>

                <!-- การจองของฉัน -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h3 class="font-semibold text-gray-800">การจองของฉัน</h3>
                    </div>
                    <div v-if="myBookings.length === 0" class="px-6 py-8 text-center text-sm text-gray-400">
                        คุณยังไม่มีการจอง
                    </div>
                    <table v-else class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3">ทรัพยากร</th>
                                <th class="px-6 py-3">ช่วงเวลา</th>
                                <th class="px-6 py-3">วัตถุประสงค์</th>
                                <th class="px-6 py-3">สถานะ</th>
                                <th class="px-6 py-3 text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="b in myBookings" :key="b.id" class="text-sm text-gray-700">
                                <td class="px-6 py-4">
                                    <span class="text-xs text-gray-400">{{ b.kind_label }}</span><br />
                                    <span class="font-medium text-gray-900">{{ b.resource_name }}</span>
                                </td>
                                <td class="px-6 py-4">{{ thaiDate(b.start_at, { time: true }) }} – {{ thaiDate(b.end_at, { time: true }) }}</td>
                                <td class="px-6 py-4">{{ b.purpose }}</td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                        :class="statusMeta(b.status).classes"
                                    >
                                        {{ statusMeta(b.status).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button
                                        v-if="['booked', 'pending', 'submitted', 'assigned'].includes(b.status)"
                                        class="font-medium text-red-600 hover:text-red-800"
                                        @click="cancel(b)"
                                    >
                                        ยกเลิก
                                    </button>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- รายละเอียดการจอง -->
        <Modal :show="detail !== null" @close="detail = null">
            <div v-if="detail" class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">{{ detail.resource_name }}</h2>
                <div class="mt-3 space-y-2 text-sm text-gray-600">
                    <p>{{ dayLabel(detail.start_date) }} · {{ timeLabel(detail) }}</p>
                    <p>{{ detail.purpose }}</p>
                    <p>ผู้จอง: {{ detail.user }}</p>
                </div>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="detail = null">ปิด</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
