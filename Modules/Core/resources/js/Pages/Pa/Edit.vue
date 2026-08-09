<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    fiscalYear: { type: Number, default: 0 },
    aspects: { type: Object, default: () => ({}) },
    agreement: { type: Object, default: () => ({}) },
    tasksByAspect: { type: Object, default: () => ({}) },
    editable: { type: Boolean, default: true },
});

const form = useForm({
    fiscal_year: props.fiscalYear,
    challenge_issue: props.agreement.challenge_issue ?? '',
    challenge_problem: props.agreement.challenge_problem ?? '',
    challenge_method: props.agreement.challenge_method ?? '',
    challenge_outcome_quant: props.agreement.challenge_outcome_quant ?? '',
    challenge_outcome_qual: props.agreement.challenge_outcome_qual ?? '',
    tasksByAspect: JSON.parse(JSON.stringify(props.tasksByAspect)),
    submit: false,
});

const addRow = (aspect) => form.tasksByAspect[aspect].push({ task: '', expected_outcome: '' });
const removeRow = (aspect, i) => form.tasksByAspect[aspect].splice(i, 1);

const submit = (isSubmit) => {
    form.submit = isSubmit;
    form
        .transform((d) => ({
            ...d,
            tasks: Object.entries(d.tasksByAspect).flatMap(([aspect, rows]) =>
                rows.map((r) => ({ aspect: Number(aspect), task: r.task, expected_outcome: r.expected_outcome })),
            ),
        }))
        .post(route('pa.store'));
};

const field = 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
const label = 'block text-sm font-medium text-gray-700';
</script>

<template>
    <Head title="จัดทำ ว.PA" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">จัดทำข้อตกลง ว.PA · ปีงบประมาณ {{ fiscalYear }}</h2>
                <Link :href="route('pa.index')" class="text-sm text-gray-500 hover:text-gray-700">← ว.PA ของฉัน</Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-4xl space-y-5 px-4 sm:px-6 lg:px-8">
                <p v-if="!editable" class="rounded-xl bg-amber-50 p-4 text-sm text-amber-700">
                    ข้อตกลงนี้เสนอแล้ว แก้ไขไม่ได้ —
                    <Link v-if="agreement.id" :href="route('pa.show', agreement.id)" class="font-medium underline">ดูข้อตกลง</Link>
                </p>

                <template v-else>
                    <!-- ส่วนที่ 1 -->
                    <div class="space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        <h3 class="text-base font-bold text-gray-800">ส่วนที่ 1 · ข้อตกลงการพัฒนางานตามมาตรฐานตำแหน่ง</h3>
                        <div v-for="(name, a) in aspects" :key="a">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-sm font-semibold text-indigo-700">{{ a }}. {{ name }}</p>
                                <button type="button" class="text-xs font-medium text-indigo-600 hover:underline" @click="addRow(a)">+ เพิ่มงาน</button>
                            </div>
                            <div v-for="(row, i) in form.tasksByAspect[a]" :key="i" class="mb-2 grid grid-cols-1 gap-2 rounded-lg border border-gray-100 bg-gray-50 p-3 sm:grid-cols-2">
                                <div>
                                    <label class="text-xs text-gray-500">งาน (Tasks) ที่จะดำเนินการ</label>
                                    <textarea v-model="row.task" rows="2" :class="field" />
                                </div>
                                <div class="relative">
                                    <label class="text-xs text-gray-500">ผลลัพธ์ (Outcomes) ที่คาดหวัง</label>
                                    <textarea v-model="row.expected_outcome" rows="2" :class="field" />
                                    <button v-if="form.tasksByAspect[a].length > 1" type="button" class="absolute -top-1 right-0 text-xs text-rose-500 hover:underline" @click="removeRow(a, i)">ลบ</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ส่วนที่ 2 -->
                    <div class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        <h3 class="text-base font-bold text-gray-800">ส่วนที่ 2 · ประเด็นท้าทาย</h3>
                        <div>
                            <label :class="label">ประเด็นท้าทาย</label>
                            <input v-model="form.challenge_issue" type="text" :class="field" />
                        </div>
                        <div>
                            <label :class="label">สภาพปัญหา</label>
                            <textarea v-model="form.challenge_problem" rows="2" :class="field" />
                        </div>
                        <div>
                            <label :class="label">วิธีการดำเนินการ</label>
                            <textarea v-model="form.challenge_method" rows="3" :class="field" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label :class="label">ผลลัพธ์เชิงปริมาณ</label>
                                <textarea v-model="form.challenge_outcome_quant" rows="2" :class="field" />
                            </div>
                            <div>
                                <label :class="label">ผลลัพธ์เชิงคุณภาพ</label>
                                <textarea v-model="form.challenge_outcome_qual" rows="2" :class="field" />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" :disabled="form.processing" class="rounded-md border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-60" @click="submit(false)">บันทึกร่าง</button>
                        <button type="button" :disabled="form.processing" class="rounded-md bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60" @click="submit(true)">เสนอ ผอ.</button>
                    </div>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
