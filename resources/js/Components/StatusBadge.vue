<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, default: '' },
    label: { type: String, default: '' }, // ใส่เพื่อ override ข้อความ (เช่น "ตีกลับ" ของหนังสือ)
});

// ===== ชุดสถานะกลางของทั้งระบบ (คำ + สี) =====
// ใช้แทน statusMeta/statusCls ที่เคยนิยามซ้ำในแต่ละหน้า เพื่อให้คำและสีตรงกันทุกที่
const MAP = {
    // อนุมัติ/พิจารณา
    pending: { label: 'รออนุมัติ', cls: 'bg-amber-100 text-amber-700' },
    approved: { label: 'อนุมัติแล้ว', cls: 'bg-emerald-100 text-emerald-700' },
    rejected: { label: 'ไม่อนุมัติ', cls: 'bg-rose-100 text-rose-700' },
    returned: { label: 'ตีกลับ', cls: 'bg-rose-100 text-rose-700' },
    // ฉบับร่าง/เสนอ/เสร็จ
    draft: { label: 'ฉบับร่าง', cls: 'bg-gray-100 text-gray-600' },
    submitted: { label: 'เสนอแล้ว', cls: 'bg-sky-100 text-sky-700' },
    evaluated: { label: 'ประเมินแล้ว', cls: 'bg-sky-100 text-sky-700' },
    acknowledged: { label: 'รับทราบแล้ว', cls: 'bg-emerald-100 text-emerald-700' },
    finalized: { label: 'เสร็จสิ้น', cls: 'bg-emerald-100 text-emerald-700' },
    // หนังสือระหว่างหน่วยงาน
    sent: { label: 'ส่งแล้ว', cls: 'bg-sky-100 text-sky-700' },
    received: { label: 'รับแล้ว', cls: 'bg-emerald-100 text-emerald-700' },
    forwarded: { label: 'มอบหมายแล้ว', cls: 'bg-amber-100 text-amber-700' },
    // ลงเวลา/ลา
    present: { label: 'มาปกติ', cls: 'bg-emerald-100 text-emerald-700' },
    absent: { label: 'ขาด', cls: 'bg-rose-100 text-rose-700' },
    trip: { label: 'ไปราชการ', cls: 'bg-sky-100 text-sky-700' },
    leave: { label: 'ลา', cls: 'bg-amber-100 text-amber-700' },
    // ทั่วไป
    cancelled: { label: 'ยกเลิก', cls: 'bg-gray-100 text-gray-500' },
    active: { label: 'ใช้งาน', cls: 'bg-emerald-100 text-emerald-700' },
    inactive: { label: 'ปิดใช้งาน', cls: 'bg-gray-100 text-gray-500' },
};

const meta = computed(() => MAP[props.status] ?? { label: props.status || '—', cls: 'bg-gray-100 text-gray-600' });
const text = computed(() => props.label || meta.value.label);
</script>

<template>
    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="meta.cls">{{ text }}</span>
</template>
