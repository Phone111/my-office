<script setup>
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    item: { type: Object, required: true },
    standardResults: { type: Array, default: () => [] },
    isArea: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: false },
    canAck: { type: Boolean, default: false },
    aspects: { type: Array, default: () => [] },
    ratings: { type: Array, default: () => [] },
    standards: { type: Array, default: () => [] },
    rounds: { type: Array, default: () => [] },
    qualityOptions: { type: Array, default: () => [] },
});
const flash = computed(() => usePage().props.flash?.success);
const statusLabel = { planned: 'วางแผน/รอนิเทศ', completed: 'นิเทศแล้ว (รอรับทราบ)', acknowledged: 'รับทราบแล้ว' };

const editing = ref(false);
// คะแนนเริ่มต้นจากผลที่บันทึกไว้
const initialScores = {};
props.standards.forEach((s) =>
    s.indicators.forEach((i) => {
        const found = props.standardResults.flatMap((r) => r.indicators).find((x) => x.id === i.id);
        initialScores[i.id] = { practiced: found?.practiced ?? false, quality: found?.quality ?? '' };
    }),
);
const edit = useForm({
    round_id: props.item.round_id ?? '',
    visit_date: props.item.visit_date_iso ?? '',
    aspect: props.item.aspect_key,
    topic: props.item.topic,
    objective: props.item.objective ?? '',
    findings: props.item.findings ?? '',
    recommendations: props.item.recommendations ?? '',
    rating: props.item.rating_key ?? '',
    scores: initialScores,
});
const saveEdit = () => edit.put(route('supervisions.update', props.item.id), { preserveScroll: true, onSuccess: () => (editing.value = false) });

const ack = useForm({ school_response: '' });
const submitAck = () => ack.post(route('supervisions.acknowledge', props.item.id), { preserveScroll: true });

const remove = () => {
    if (confirm('ลบรายการนิเทศนี้?')) router.delete(route('supervisions.destroy', props.item.id));
};
</script>

<template>
    <Head title="รายละเอียดการนิเทศ" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">รายละเอียดการนิเทศการศึกษา</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <!-- มุมมองรายละเอียด -->
                <div v-if="!editing" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-lg font-semibold text-gray-900">{{ item.topic }}</p>
                            <p class="text-sm text-gray-500">{{ item.school }} · {{ item.aspect }}<span v-if="item.round"> · {{ item.round }}</span></p>
                        </div>
                        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600">{{ statusLabel[item.status] ?? item.status }}</span>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                        <div><dt class="text-gray-400">วันที่นิเทศ</dt><dd class="text-gray-800">{{ item.visit_date_thai }}</dd></div>
                        <div><dt class="text-gray-400">ผู้นิเทศ</dt><dd class="text-gray-800">{{ item.supervisor ?? '—' }}</dd></div>
                        <div><dt class="text-gray-400">คะแนนคุณภาพเฉลี่ย</dt><dd class="text-gray-800">{{ item.quality_avg ?? '—' }}<span v-if="item.quality_avg"> / 5</span></dd></div>
                        <div v-if="item.rating"><dt class="text-gray-400">ระดับผลรวม</dt><dd class="text-gray-800">{{ item.rating }}</dd></div>
                    </dl>

                    <!-- ผลรายมาตรฐาน → ตัวชี้วัด -->
                    <div v-if="standardResults.length" class="mt-4">
                        <p class="mb-2 text-sm font-medium text-gray-700">ผลการประเมินตามมาตรฐาน</p>
                        <div v-for="std in standardResults" :key="std.id" class="mb-3 overflow-hidden rounded-lg ring-1 ring-gray-100">
                            <div class="flex items-center justify-between bg-indigo-50 px-3 py-1.5 text-sm font-semibold text-indigo-700">
                                <span>{{ std.name }}</span>
                                <span v-if="std.avg" class="text-xs">เฉลี่ย {{ std.avg }}/5</span>
                            </div>
                            <div v-for="ind in std.indicators" :key="ind.id" class="flex items-center justify-between gap-2 border-b border-gray-50 px-3 py-1.5 text-sm">
                                <span class="flex-1 text-gray-700"><span :class="ind.practiced ? 'text-emerald-600' : 'text-gray-300'">{{ ind.practiced ? '✔' : '—' }}</span> {{ ind.name }}</span>
                                <span class="shrink-0 text-xs text-gray-500">{{ ind.quality_label ? `${ind.quality}/5 (${ind.quality_label})` : '—' }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="item.objective" class="mt-4"><p class="text-sm font-medium text-gray-700">วัตถุประสงค์</p><p class="mt-1 rounded-lg bg-gray-50 px-4 py-2 text-sm text-gray-700 whitespace-pre-line">{{ item.objective }}</p></div>
                    <div v-if="item.findings" class="mt-3"><p class="text-sm font-medium text-gray-700">สภาพที่พบ / สรุปผลการนิเทศ</p><p class="mt-1 rounded-lg bg-gray-50 px-4 py-2 text-sm text-gray-700 whitespace-pre-line">{{ item.findings }}</p></div>
                    <div v-if="item.recommendations" class="mt-3"><p class="text-sm font-medium text-gray-700">ข้อเสนอแนะ</p><p class="mt-1 rounded-lg bg-amber-50 px-4 py-2 text-sm text-amber-800 whitespace-pre-line">{{ item.recommendations }}</p></div>

                    <div v-if="item.files && item.files.length" class="mt-4">
                        <p class="mb-1 text-sm font-medium text-gray-700">ไฟล์แนบ</p>
                        <ul class="space-y-1">
                            <li v-for="(f, i) in item.files" :key="i"><a :href="f.url" target="_blank" class="text-sm text-indigo-600 hover:underline">{{ f.name }}</a></li>
                        </ul>
                    </div>

                    <div v-if="item.school_response" class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 ring-1 ring-emerald-100">
                        <p class="text-sm font-medium text-emerald-800">การตอบรับ/ดำเนินการของโรงเรียน</p>
                        <p class="mt-1 text-sm text-emerald-700 whitespace-pre-line">{{ item.school_response }}</p>
                        <p v-if="item.acknowledger" class="mt-1 text-xs text-emerald-500">โดย {{ item.acknowledger }} · {{ item.acknowledged_thai }}</p>
                    </div>

                    <div v-if="isArea" class="mt-5 flex gap-2">
                        <button v-if="canEdit" class="rounded-md bg-indigo-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500" @click="editing = true">แก้ไข/สรุปผล</button>
                        <button class="rounded-md bg-rose-50 px-4 py-1.5 text-sm font-semibold text-rose-600 hover:bg-rose-100" @click="remove">ลบ</button>
                    </div>
                </div>

                <!-- ฟอร์มแก้ไข (ฝั่งเขต) -->
                <div v-else class="space-y-3 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-indigo-100">
                    <p class="text-sm font-semibold text-gray-700">แก้ไข / สรุปผลการนิเทศ</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="mb-1 block text-sm text-gray-600">วันที่นิเทศ</label><input v-model="edit.visit_date" type="date" class="w-full rounded-md border-gray-300 text-sm" /></div>
                        <div><label class="mb-1 block text-sm text-gray-600">รอบ</label><select v-model="edit.round_id" class="w-full rounded-md border-gray-300 text-sm"><option value="">— ไม่ระบุ —</option><option v-for="r in rounds" :key="r.id" :value="r.id">{{ r.name }}</option></select></div>
                        <div><label class="mb-1 block text-sm text-gray-600">ด้าน</label><select v-model="edit.aspect" class="w-full rounded-md border-gray-300 text-sm"><option v-for="a in aspects" :key="a.key" :value="a.key">{{ a.label }}</option></select></div>
                        <div><label class="mb-1 block text-sm text-gray-600">ประเด็น</label><input v-model="edit.topic" type="text" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    </div>

                    <div v-if="standards.length" class="rounded-lg ring-1 ring-gray-100 p-3">
                        <p class="mb-2 text-sm font-medium text-gray-700">ประเมินรายตัวชี้วัด</p>
                        <div v-for="std in standards" :key="std.id" class="mb-2">
                            <p class="mb-1 rounded bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700">{{ std.name }}</p>
                            <div v-for="ind in std.indicators" :key="ind.id" class="flex items-center gap-2 border-b border-gray-50 px-1 py-1.5 text-sm">
                                <label class="flex flex-1 items-center gap-2 text-gray-700"><input v-model="edit.scores[ind.id].practiced" type="checkbox" class="rounded border-gray-300 text-indigo-600" /><span>{{ ind.name }}</span></label>
                                <select v-model="edit.scores[ind.id].quality" class="w-32 shrink-0 rounded-md border-gray-300 text-xs"><option value="">— คุณภาพ —</option><option v-for="q in qualityOptions" :key="q.key" :value="q.key">{{ q.key }} - {{ q.label }}</option></select>
                            </div>
                        </div>
                    </div>

                    <div><label class="mb-1 block text-sm text-gray-600">วัตถุประสงค์</label><textarea v-model="edit.objective" rows="2" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="mb-1 block text-sm text-gray-600">สภาพที่พบ / สรุปผล</label><textarea v-model="edit.findings" rows="3" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div><label class="mb-1 block text-sm text-gray-600">ข้อเสนอแนะ</label><textarea v-model="edit.recommendations" rows="3" class="w-full rounded-md border-gray-300 text-sm" /></div>
                    <div class="flex justify-end gap-2">
                        <SecondaryButton type="button" @click="editing = false">ยกเลิก</SecondaryButton>
                        <button :disabled="edit.processing" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60" @click="saveEdit">บันทึก</button>
                    </div>
                </div>

                <!-- โรงเรียนรับทราบ -->
                <div v-if="canAck" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-emerald-100">
                    <p class="mb-2 text-sm font-semibold text-gray-700">รับทราบผลการนิเทศ (ฝั่งโรงเรียน)</p>
                    <label class="mb-1 block text-sm text-gray-600">การตอบรับ/แนวทางดำเนินการ (ถ้ามี)</label>
                    <textarea v-model="ack.school_response" rows="3" class="w-full rounded-md border-gray-300 text-sm" />
                    <div class="mt-3 flex justify-end">
                        <button :disabled="ack.processing" class="rounded-md bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60" @click="submitAck">รับทราบผลการนิเทศ</button>
                    </div>
                </div>

                <SecondaryButton @click="router.visit(route('supervisions.index'))">← กลับทะเบียนนิเทศ</SecondaryButton>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
