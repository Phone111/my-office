<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    statuses: { type: Object, default: () => ({}) },
    date: { type: String, default: '' },
    dateThai: { type: String, default: '' },
    isToday: { type: Boolean, default: true },
});

const statusList = computed(() => Object.entries(props.statuses).map(([code, label]) => ({ code, label })));

const form = useForm({
    work_date: props.date,
    records: props.rows.map((r) => ({ user_id: r.user_id, status: r.status, note: r.note ?? '' })),
});

// นำทางไปวันอื่น (เปลี่ยนวันที่ → โหลดใหม่)
const pickDate = ref(props.date);
const goDate = () => router.get(route('leave.attendance.entry'), { date: pickDate.value }, { preserveScroll: true });

const search = ref('');
const visibleIdx = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.rows
        .map((r, i) => ({ r, i }))
        .filter(({ r }) => !q || (r.name ?? '').toLowerCase().includes(q) || (r.group ?? '').toLowerCase().includes(q));
});

// ตั้งสถานะให้ทุกคนที่แสดงอยู่ (ยืนยันก่อน — กันเผลอเปลี่ยนทั้งตาราง)
const setAllVisible = (code) => {
    if (!confirm(`ตั้งสถานะ "${statuses[code]}" ให้ทุกคนที่แสดงอยู่ (${visibleIdx.value.length} คน)?`)) return;
    visibleIdx.value.forEach(({ i }) => (form.records[i].status = code));
};

const tone = (code) =>
    ({
        present: 'text-emerald-700',
        trip: 'text-indigo-700',
        late: 'text-amber-700',
        absent: 'text-rose-700',
    })[code] ?? 'text-gray-700';

const save = () => form.post(route('leave.attendance.store'), { preserveScroll: true });
</script>

<template>
    <Head title="ลงเวลาปฏิบัติราชการ" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800">บันทึกการปฏิบัติราชการ</h2>
                <div class="flex items-center gap-2 text-sm">
                    <Link :href="route('leave.attendance.daily', { date })" class="text-gray-500 hover:text-gray-700">รายงานรายวัน</Link>
                    <span class="text-gray-300">·</span>
                    <Link :href="route('leave.attendance.monthly')" class="text-gray-500 hover:text-gray-700">รายงานรอบเดือน</Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
                <!-- เลือกวัน + สรุปวันที่ -->
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-600">วันที่บันทึก</label>
                        <input v-model="pickDate" type="date" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="goDate" />
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="isToday ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">{{ isToday ? 'วันนี้' : 'ย้อนหลัง' }}</span>
                    </div>
                    <p class="text-sm text-gray-500">{{ dateThai }}</p>
                </div>

                <!-- เครื่องมือ: ค้นหา + ตั้งค่าหมู่ -->
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <input v-model="search" type="text" placeholder="ค้นหาชื่อ / กลุ่มงาน" class="w-64 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <span>ตั้งทุกคนที่แสดง:</span>
                        <button v-for="s in ['present', 'trip', 'absent']" :key="s" type="button" class="rounded-md border border-gray-200 px-2 py-1 font-medium hover:bg-gray-50" @click="setAllVisible(s)">{{ statuses[s] }}</button>
                    </div>
                </div>

                <!-- ตาราง -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    <th class="px-4 py-3 text-center">ที่</th>
                                    <th class="px-4 py-3">ชื่อ-สกุล</th>
                                    <th class="px-4 py-3">กลุ่มงาน</th>
                                    <th class="px-4 py-3 w-44">การปฏิบัติราชการ</th>
                                    <th class="px-4 py-3">หมายเหตุ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-if="visibleIdx.length === 0"><td colspan="5" class="px-6 py-12 text-center text-sm text-gray-400">ไม่พบรายการ</td></tr>
                                <tr v-for="({ r, i }, n) in visibleIdx" :key="r.user_id" class="text-sm hover:bg-gray-50">
                                    <td class="px-4 py-2 text-center text-gray-400">{{ n + 1 }}</td>
                                    <td class="px-4 py-2 font-medium text-gray-900">
                                        {{ r.name }}
                                        <span v-if="r.saved" class="ml-1 text-[10px] text-emerald-500">●</span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-500">{{ r.group ?? '—' }}</td>
                                    <td class="px-4 py-2">
                                        <select v-model="form.records[i].status" class="w-full rounded-md border-gray-300 text-sm font-medium shadow-sm focus:border-indigo-500 focus:ring-indigo-500" :class="tone(form.records[i].status)">
                                            <option v-for="s in statusList" :key="s.code" :value="s.code">{{ s.label }}</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input v-model="form.records[i].note" type="text" placeholder="—" class="w-full rounded-md border-gray-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-400">● = บันทึกไว้แล้ว · ค่าเริ่มต้นดึงจากใบลา/ไปราชการที่อนุมัติ</p>
                    <button type="button" :disabled="form.processing" class="rounded-md bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60" @click="save">
                        บันทึกการปฏิบัติราชการ
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
