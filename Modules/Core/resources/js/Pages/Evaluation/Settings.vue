<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    criteria: { type: Array, default: () => [] },
    rounds: { type: Array, default: () => [] },
});
const flash = computed(() => usePage().props.flash?.success);

const critForm = useForm({ name: '', max_score: 100 });
const addCrit = () => critForm.post(route('evaluations.criteria.store'), { preserveScroll: true, onSuccess: () => critForm.reset() });
const delCrit = (c) => { if (confirm(`ลบ "${c.name}"?`)) router.delete(route('evaluations.criteria.destroy', c.id), { preserveScroll: true }); };

const roundForm = useForm({ name: '', fiscal_year: '', period: '' });
const addRound = () => roundForm.post(route('evaluations.rounds.store'), { preserveScroll: true, onSuccess: () => roundForm.reset() });
const setCurrent = (r) => router.post(route('evaluations.rounds.current', r.id), {}, { preserveScroll: true });
const delRound = (r) => { if (confirm(`ลบรอบ "${r.name}"?`)) router.delete(route('evaluations.rounds.destroy', r.id), { preserveScroll: true }); };

const totalMax = computed(() => props.criteria.reduce((a, c) => a + parseFloat(c.max_score), 0));
</script>

<template>
    <Head title="ตั้งค่าการประเมิน" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ตั้งค่าการประเมินผลปฏิบัติงาน</h2>
                <Link :href="route('evaluations.index')" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-200 hover:bg-indigo-50">← กลับ</Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <p class="mb-3 text-sm font-semibold text-gray-700">รอบการประเมิน</p>
                    <div class="mb-3 flex flex-wrap items-end gap-2">
                        <input v-model="roundForm.name" type="text" placeholder="เช่น ครั้งที่ 2 ปีงบประมาณ 2569" class="flex-1 rounded-md border-gray-300 text-sm" />
                        <input v-model="roundForm.fiscal_year" type="number" placeholder="ปีงบ" class="w-24 rounded-md border-gray-300 text-sm" />
                        <select v-model="roundForm.period" class="w-20 rounded-md border-gray-300 text-sm"><option value="">ครั้ง</option><option value="1">1</option><option value="2">2</option></select>
                        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" @click="addRound">เพิ่ม</button>
                    </div>
                    <ul class="divide-y divide-gray-50">
                        <li v-for="r in rounds" :key="r.id" class="flex items-center justify-between py-2 text-sm">
                            <span>{{ r.name }} <span v-if="r.is_current" class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">ปัจจุบัน</span></span>
                            <span class="flex gap-3"><button v-if="!r.is_current" class="text-indigo-600 hover:underline" @click="setCurrent(r)">ตั้งปัจจุบัน</button><button class="text-rose-600 hover:underline" @click="delRound(r)">ลบ</button></span>
                        </li>
                        <li v-if="rounds.length === 0" class="py-3 text-center text-sm text-gray-400">ยังไม่มีรอบ</li>
                    </ul>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <p class="mb-1 text-sm font-semibold text-gray-700">องค์ประกอบการประเมิน <span class="text-xs font-normal text-gray-400">(รวม {{ totalMax }} คะแนน)</span></p>
                    <div class="my-3 flex flex-wrap items-end gap-2">
                        <input v-model="critForm.name" type="text" placeholder="ชื่อองค์ประกอบ" class="flex-1 rounded-md border-gray-300 text-sm" />
                        <input v-model="critForm.max_score" type="number" placeholder="คะแนนเต็ม" class="w-28 rounded-md border-gray-300 text-sm" />
                        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" @click="addCrit">เพิ่ม</button>
                    </div>
                    <ul class="divide-y divide-gray-50">
                        <li v-for="c in criteria" :key="c.id" class="flex items-center justify-between py-2 text-sm">
                            <span class="text-gray-800">{{ c.name }} <span class="text-xs text-gray-400">(เต็ม {{ c.max_score }})</span></span>
                            <button class="text-rose-600 hover:underline" @click="delCrit(c)">ลบ</button>
                        </li>
                        <li v-if="criteria.length === 0" class="py-3 text-center text-sm text-gray-400">ยังไม่มีองค์ประกอบ</li>
                    </ul>
                    <p class="mt-2 text-xs text-gray-400">เกณฑ์ระดับ: ดีเด่น ≥90 · ดีมาก ≥80 · ดี ≥70 · พอใช้ ≥60 · ต่ำกว่า = ต้องปรับปรุง (คิดเป็นร้อยละจากคะแนนเต็มรวม)</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
