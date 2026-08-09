<script setup>
import { computed } from 'vue';

// ตัวเลือกเวลาแบบ 24 ชั่วโมง — dropdown เดียว เลือกช่วงเวลาสำเร็จรูป (ค่า v-model = "HH:MM")
const props = defineProps({
    modelValue: { type: String, default: '' },
    start: { type: String, default: '06:00' }, // เวลาเริ่มของช่วงให้เลือก
    end: { type: String, default: '20:00' }, // เวลาสิ้นสุดของช่วงให้เลือก
    step: { type: Number, default: 30 }, // ระยะห่างแต่ละช่วง (นาที)
});
const emit = defineEmits(['update:modelValue']);

const toMinutes = (s) => {
    const [h, m] = s.split(':').map(Number);
    return h * 60 + m;
};
const format = (mins) =>
    `${String(Math.floor(mins / 60)).padStart(2, '0')}:${String(mins % 60).padStart(2, '0')}`;

const slots = computed(() => {
    const out = [];
    for (let t = toMinutes(props.start); t <= toMinutes(props.end); t += props.step) {
        out.push(format(t));
    }
    // กันค่าปัจจุบันหาย ถ้าไม่อยู่ในลิสต์ (เช่นค่าที่ส่งมาจาก URL/ของเดิม)
    if (props.modelValue && !out.includes(props.modelValue)) {
        out.push(props.modelValue);
        out.sort();
    }
    return out;
});

const value = computed({
    get: () => props.modelValue,
    set: (v) => emit('update:modelValue', v),
});
</script>

<template>
    <select
        v-model="value"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
    >
        <option value="" disabled>— เลือกเวลา —</option>
        <option v-for="t in slots" :key="t" :value="t">{{ t }} น.</option>
    </select>
</template>
