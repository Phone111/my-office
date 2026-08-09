<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    testTypes: { type: Object, default: () => ({}) },
    subjects: { type: Object, default: () => ({}) },
    gradesForType: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    years: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    areaAvg: { type: Object, default: () => ({}) },
    trend: { type: Array, default: () => [] },
    canEdit: { type: Boolean, default: false },
    isOverseer: { type: Boolean, default: false },
});

const subjectKeys = computed(() => Object.keys(props.subjects));

const form = useForm({
    type: props.filters.type,
    year: props.filters.year,
    grade: props.filters.grade,
    results: props.rows.map((r) => ({ unit_id: r.unit_id, name: r.name, scores: { ...r.scores } })),
});

const go = (params) => router.get(route('achievement.index'), params, { preserveScroll: true });
const changeType = (t) => go({ type: t, year: props.filters.year });
const changeGrade = (e) => go({ type: props.filters.type, year: props.filters.year, grade: e.target.value });
const changeYear = (e) => go({ type: props.filters.type, year: e.target.value, grade: props.filters.grade });

const num = (v) => (v === null || v === '' || v === undefined ? null : Number(v));
const rowAvg = (scores) => {
    const v = subjectKeys.value.map((k) => num(scores[k])).filter((x) => x !== null && !isNaN(x));
    return v.length ? (v.reduce((a, b) => a + b, 0) / v.length).toFixed(2) : '—';
};

const save = () =>
    form
        .transform((d) => ({ type: d.type, year: d.year, grade: d.grade, results: d.results.map((r) => ({ unit_id: r.unit_id, scores: r.scores })) }))
        .post(route('achievement.store'), { preserveScroll: true });

// สีคะแนนเทียบ 50 (ผ่านครึ่ง)
const scoreClass = (v) => {
    const n = num(v);
    if (n === null || isNaN(n)) return 'text-gray-300';
    return n >= 50 ? 'text-emerald-600' : 'text-rose-500';
};

// นำเข้า CSV
const importForm = useForm({ file: null });
const onImportFile = (e) => (importForm.file = e.target.files[0] ?? null);
const doImport = () =>
    importForm
        .transform((d) => ({ ...d, type: props.filters.type, year: props.filters.year, grade: props.filters.grade }))
        .post(route('achievement.import'), { preserveScroll: true, onSuccess: () => importForm.reset('file') });
const templateUrl = computed(() => route('achievement.template', { type: props.filters.type }));

// กราฟเปรียบเทียบรายปี
const yearBg = ['bg-indigo-500', 'bg-emerald-500', 'bg-amber-500', 'bg-rose-500', 'bg-sky-500'];
</script>

<template>
    <Head title="ผลสัมฤทธิ์ระดับชาติ" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">ผลสัมฤทธิ์ระดับชาติ (O-NET / NT / RT)</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
                <!-- ตัวกรอง -->
                <div class="flex flex-wrap items-center gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                    <div class="flex gap-1 rounded-lg bg-gray-100 p-1">
                        <button v-for="(label, key) in testTypes" :key="key" type="button" class="rounded-md px-4 py-1.5 text-sm font-semibold transition" :class="filters.type === key ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'" @click="changeType(key)">{{ label }}</button>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-sm text-gray-500">ชั้น</span>
                        <select :value="filters.grade" class="rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="changeGrade">
                            <option v-for="g in gradesForType" :key="g" :value="g">{{ g }}</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-sm text-gray-500">ปีการศึกษา</span>
                        <select :value="filters.year" class="rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="changeYear">
                            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>
                    <p class="ml-auto text-xs text-gray-400">{{ isOverseer ? 'กรอกได้ทุกโรงเรียน' : 'กรอกเฉพาะโรงเรียนของท่าน' }}</p>
                </div>

                <!-- นำเข้า CSV -->
                <div v-if="canEdit" class="flex flex-wrap items-center gap-3 rounded-2xl bg-white p-4 text-sm shadow-sm ring-1 ring-gray-100">
                    <span class="font-semibold text-gray-700">นำเข้าจากไฟล์ CSV</span>
                    <a :href="templateUrl" class="text-indigo-600 hover:underline">↓ ดาวน์โหลดเทมเพลต</a>
                    <input type="file" accept=".csv,text/csv" class="text-sm file:mr-2 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:font-medium file:text-indigo-700" @change="onImportFile" />
                    <button type="button" :disabled="!importForm.file || importForm.processing" class="rounded-md bg-emerald-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500 disabled:opacity-50" @click="doImport">นำเข้า</button>
                    <span class="text-xs text-gray-400">คอลัมน์: รหัสโรงเรียน, แล้วคะแนนแต่ละวิชา (ตามปี/ชั้นที่เลือก)</span>
                </div>

                <!-- ตารางคะแนน -->
                <form class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100" @submit.prevent="save">
                    <p v-if="!rows.length" class="py-8 text-center text-gray-400">ยังไม่มีโรงเรียนในสิทธิ์ของคุณ</p>
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b-2 border-gray-200 text-gray-600">
                                    <th class="px-3 py-2 text-left">โรงเรียน</th>
                                    <th v-for="(label, k) in subjects" :key="k" class="px-3 py-2 text-center">{{ label }}</th>
                                    <th class="px-3 py-2 text-center">เฉลี่ย</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in form.results" :key="row.unit_id" class="border-b border-gray-50">
                                    <td class="px-3 py-1.5 font-medium text-gray-800">{{ row.name }}</td>
                                    <td v-for="k in subjectKeys" :key="k" class="px-2 py-1.5 text-center">
                                        <input
                                            v-model="row.scores[k]"
                                            type="number" min="0" max="100" step="0.01"
                                            :disabled="!canEdit"
                                            class="w-20 rounded-md border-gray-300 py-1 text-center text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-500"
                                            placeholder="—"
                                        />
                                    </td>
                                    <td class="px-3 py-1.5 text-center font-semibold text-gray-700">{{ rowAvg(row.scores) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-gray-200 bg-indigo-50/50 font-semibold">
                                    <td class="px-3 py-2 text-indigo-700">ค่าเฉลี่ยเขต</td>
                                    <td v-for="k in subjectKeys" :key="k" class="px-3 py-2 text-center" :class="scoreClass(areaAvg[k])">{{ areaAvg[k] ?? '—' }}</td>
                                    <td class="px-3 py-2 text-center text-indigo-700">{{ rowAvg(areaAvg) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div v-if="canEdit && rows.length" class="mt-4 flex items-center justify-end gap-3">
                        <span v-if="form.recentlySuccessful" class="text-sm text-emerald-600">บันทึกแล้ว ✓</span>
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60">บันทึกคะแนน</button>
                    </div>
                </form>

                <!-- กราฟเปรียบเทียบค่าเฉลี่ยเขต รายปี -->
                <div v-if="trend.length" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-base font-bold text-gray-800">กราฟเปรียบเทียบค่าเฉลี่ยเขต รายปี</h3>
                        <div class="flex flex-wrap gap-3 text-xs text-gray-600">
                            <span v-for="(t, i) in trend" :key="t.year" class="flex items-center gap-1">
                                <span class="inline-block h-2.5 w-2.5 rounded-sm" :class="yearBg[i % yearBg.length]" /> ปี {{ t.year }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-end gap-4 sm:gap-8">
                        <div v-for="k in subjectKeys" :key="k" class="flex flex-1 flex-col items-center">
                            <div class="flex h-40 items-end gap-1">
                                <div
                                    v-for="(t, i) in trend"
                                    :key="t.year"
                                    class="w-4 rounded-t transition-all sm:w-6"
                                    :class="yearBg[i % yearBg.length]"
                                    :style="{ height: (t.scores[k] || 0) + '%' }"
                                    :title="'ปี ' + t.year + ': ' + (t.scores[k] ?? '—')"
                                />
                            </div>
                            <span class="mt-1 text-center text-xs text-gray-600">{{ subjects[k] }}</span>
                        </div>
                    </div>
                    <p class="mt-2 text-center text-xs text-gray-400">มาตราส่วน 0–100 · เลื่อนเมาส์บนแท่งเพื่อดูคะแนน</p>
                </div>

                <p class="text-xs text-gray-400">* คะแนนเป็นค่าเฉลี่ยของโรงเรียนต่อวิชา (0–100) · แถวล่างคือค่าเฉลี่ยรวมระดับเขต · สีเขียว ≥ 50, แดง &lt; 50</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
