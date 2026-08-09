<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    activeCount: { type: Number, default: 0 },
});

const page = usePage();
const flash = computed(() => page.props.flash?.success);

// สถานะ toggle ที่กำลังเลือกในแต่ละแถว (ก่อนกดบันทึก)
const draft = reactive(Object.fromEntries(props.rows.map((r) => [r.id, r.active])));
const saving = reactive({});

const dirty = (r) => draft[r.id] !== r.active;

function save(row) {
    saving[row.id] = true;
    router.post(
        route('executive-duty.save'),
        { user_id: row.id, active: draft[row.id] },
        {
            preserveScroll: true,
            onFinish: () => {
                saving[row.id] = false;
            },
        },
    );
}
</script>

<template>
    <Head title="การปฏิบัติราชการของผู้บริหาร" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">การปฏิบัติราชการของผู้บริหาร</h2>
                    <p class="text-xs text-gray-400">แต่งตั้งผู้บริหารที่ปฏิบัติราชการ / รักษาราชการแทน เพื่อแสดงบนหน้าหลัก</p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-100">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    แสดงอยู่ {{ activeCount }} คน
                </span>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">
                    {{ flash }}
                </div>

                <div class="space-y-3">
                    <div
                        v-for="r in rows"
                        :key="r.id"
                        class="flex flex-wrap items-center gap-4 rounded-2xl bg-white p-4 shadow-sm ring-1 transition"
                        :class="r.active ? 'ring-indigo-200' : 'ring-gray-100'"
                    >
                        <!-- avatar -->
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                            :class="r.is_director ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-500'"
                        >
                            {{ (r.name ?? '?').charAt(0) }}
                        </div>

                        <!-- ชื่อ + ตำแหน่ง -->
                        <div class="min-w-0 flex-1">
                            <div class="truncate font-medium text-gray-900">{{ r.name }}</div>
                            <div class="text-xs text-gray-400">{{ r.position }}</div>
                        </div>

                        <!-- ป้ายบทบาทบนบอร์ด -->
                        <span
                            class="rounded-full px-3 py-1 text-xs font-semibold"
                            :class="r.is_director ? 'bg-indigo-50 text-indigo-600' : 'bg-amber-50 text-amber-700'"
                        >
                            {{ r.duty_label }}
                        </span>

                        <!-- toggle แสดง/ไม่แสดง -->
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="draft[r.id]"
                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition"
                            :class="draft[r.id] ? 'bg-indigo-600' : 'bg-gray-300'"
                            @click="draft[r.id] = !draft[r.id]"
                        >
                            <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition" :class="draft[r.id] ? 'translate-x-5' : 'translate-x-0.5'"></span>
                        </button>
                        <span class="w-14 text-xs" :class="draft[r.id] ? 'text-indigo-600' : 'text-gray-400'">
                            {{ draft[r.id] ? 'แสดง' : 'ไม่แสดง' }}
                        </span>

                        <!-- บันทึก -->
                        <button
                            type="button"
                            :disabled="saving[r.id] || !dirty(r)"
                            class="rounded-md bg-indigo-600 px-4 py-1.5 text-xs font-medium text-white shadow-sm transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-400"
                            @click="save(r)"
                        >
                            {{ saving[r.id] ? 'กำลังบันทึก…' : 'บันทึก' }}
                        </button>
                    </div>

                    <div v-if="rows.length === 0" class="rounded-2xl bg-white p-12 text-center text-sm text-gray-400 shadow-sm ring-1 ring-gray-100">
                        ไม่มีผู้บริหารในระบบ
                    </div>
                </div>

                <p class="text-xs text-gray-400">
                    เปิด "แสดง" แล้วกดบันทึก รายชื่อจะปรากฏในบอร์ด "ผู้บริหารปฏิบัติราชการ" บนหน้าหลัก —
                    ผอ. แสดงเป็น "ปฏิบัติราชการ" ส่วนรองฯ/หัวหน้ากลุ่ม แสดงเป็น "รักษาราชการแทน"
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
