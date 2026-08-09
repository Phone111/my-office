<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    awards: { type: Array, default: () => [] },
    trainings: { type: Array, default: () => [] },
    levels: { type: Array, default: () => [] },
    subjectGroups: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});

const modal = ref(null); // 'award' | 'training' | null
const closeModal = () => (modal.value = null);

// ดูรายละเอียด: { type: 'award' | 'training', item }
const detail = ref(null);
const openDetail = (type, item) => (detail.value = { type, item });
const closeDetail = () => (detail.value = null);

const awardForm = useForm({ award_name: '', level: '', awarded_date: '', file: null });
const trainingForm = useForm({
    course_name: '',
    subject_group: '',
    hours: null,
    organizer: '',
    start_date: '',
    budget_source: '',
    file: null,
});

const openAward = () => {
    awardForm.reset();
    awardForm.clearErrors();
    modal.value = 'award';
};
const openTraining = () => {
    trainingForm.reset();
    trainingForm.clearErrors();
    modal.value = 'training';
};
const submitAward = () =>
    awardForm.post(route('id-plan.awards.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => closeModal(),
    });
const submitTraining = () =>
    trainingForm.post(route('id-plan.trainings.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => closeModal(),
    });
</script>

<template>
    <Head title="แผนพัฒนาตนเอง (ID Plan)" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">แผนพัฒนาตนเอง (ID Plan) ของฉัน</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div
                    v-if="flash.success"
                    class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
                >
                    {{ flash.success }}
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- ===== ข้อมูลรางวัล ===== -->
                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <h3 class="font-semibold text-gray-800">
                                ข้อมูลรางวัล
                                <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">{{ awards.length }}</span>
                            </h3>
                            <PrimaryButton @click="openAward">+ เพิ่มข้อมูล</PrimaryButton>
                        </div>
                        <p v-if="awards.length === 0" class="px-6 py-8 text-center text-sm text-gray-400">ยังไม่มีข้อมูลรางวัล</p>
                        <table v-else class="min-w-full divide-y divide-gray-100 text-[15px]">
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-6 py-3 text-left">ชื่อรางวัล</th>
                                    <th class="px-6 py-3 text-left">ระดับ</th>
                                    <th class="px-6 py-3 text-left">วันที่ได้รับ</th>
                                    <th class="px-6 py-3 text-center">หลักฐาน</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="a in awards" :key="a.id" class="cursor-pointer hover:bg-indigo-50/50" @click="openDetail('award', a)">
                                    <td class="px-6 py-3 font-medium text-gray-900">{{ a.award_name }}</td>
                                    <td class="px-6 py-3 text-gray-600">{{ a.level || '—' }}</td>
                                    <td class="px-6 py-3 text-gray-600">{{ a.awarded_date }}</td>
                                    <td class="px-6 py-3 text-center">
                                        <a v-if="a.file_url" :href="a.file_url" target="_blank" class="text-indigo-600 hover:text-indigo-800" @click.stop>ดูไฟล์</a>
                                        <span v-else class="text-gray-300">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- ===== ข้อมูลการอบรม / พัฒนา ===== -->
                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <h3 class="font-semibold text-gray-800">
                                ข้อมูลการอบรม / พัฒนา
                                <span class="ml-1 rounded-full bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-700">{{ trainings.length }}</span>
                            </h3>
                            <PrimaryButton @click="openTraining">+ เพิ่มข้อมูล</PrimaryButton>
                        </div>
                        <p v-if="trainings.length === 0" class="px-6 py-8 text-center text-sm text-gray-400">ยังไม่มีข้อมูลการอบรม</p>
                        <table v-else class="min-w-full divide-y divide-gray-100 text-[15px]">
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-6 py-3 text-left">หลักสูตร</th>
                                    <th class="px-6 py-3 text-left">กลุ่มสาระ</th>
                                    <th class="px-6 py-3 text-center">ชั่วโมง</th>
                                    <th class="px-6 py-3 text-left">หน่วยงาน</th>
                                    <th class="px-6 py-3 text-left">วันที่</th>
                                    <th class="px-6 py-3 text-center">หลักฐาน</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="t in trainings" :key="t.id" class="cursor-pointer hover:bg-indigo-50/50" @click="openDetail('training', t)">
                                    <td class="px-6 py-3 font-medium text-gray-900">{{ t.course_name }}</td>
                                    <td class="px-6 py-3 text-gray-600">{{ t.subject_group || '—' }}</td>
                                    <td class="px-6 py-3 text-center text-gray-600">{{ t.hours }}</td>
                                    <td class="px-6 py-3 text-gray-600">
                                        {{ t.organizer || '—' }}
                                        <span v-if="t.budget_source" class="block text-xs text-gray-400">งบ: {{ t.budget_source }}</span>
                                    </td>
                                    <td class="px-6 py-3 text-gray-600">{{ t.start_date }}</td>
                                    <td class="px-6 py-3 text-center">
                                        <a v-if="t.file_url" :href="t.file_url" target="_blank" class="text-indigo-600 hover:text-indigo-800" @click.stop>ดูไฟล์</a>
                                        <span v-else class="text-gray-300">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: เพิ่มรางวัล -->
        <Modal :show="modal === 'award'" @close="closeModal">
            <form class="space-y-4 p-6" @submit.prevent="submitAward">
                <h2 class="text-lg font-semibold text-gray-900">แบบบันทึกข้อมูลรางวัลที่ได้รับ</h2>
                <div>
                    <InputLabel for="aname" value="ชื่อรางวัล" />
                    <TextInput id="aname" v-model="awardForm.award_name" type="text" class="mt-1 block w-full" />
                    <InputError :message="awardForm.errors.award_name" class="mt-2" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <InputLabel for="alevel" value="ระดับ" />
                        <select id="alevel" v-model="awardForm.level" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— เลือก —</option>
                            <option v-for="l in levels" :key="l" :value="l">{{ l }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="adate" value="วัน เดือน ปีที่ได้รับ" />
                        <TextInput id="adate" v-model="awardForm.awarded_date" type="date" class="mt-1 block w-full" />
                        <InputError :message="awardForm.errors.awarded_date" class="mt-2" />
                    </div>
                </div>
                <div>
                    <InputLabel for="afile" value="หลักฐาน" />
                    <input id="afile" type="file" class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100" @input="awardForm.file = $event.target.files[0]" />
                    <InputError :message="awardForm.errors.file" class="mt-2" />
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="closeModal">ยกเลิก</SecondaryButton>
                    <PrimaryButton :disabled="awardForm.processing">บันทึกข้อมูล</PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Modal: เพิ่มการอบรม/พัฒนา -->
        <Modal :show="modal === 'training'" @close="closeModal">
            <form class="space-y-4 p-6" @submit.prevent="submitTraining">
                <h2 class="text-lg font-semibold text-gray-900">แบบบันทึกข้อมูลการพัฒนา</h2>
                <div>
                    <InputLabel for="tname" value="หลักสูตร" />
                    <TextInput id="tname" v-model="trainingForm.course_name" type="text" class="mt-1 block w-full" />
                    <InputError :message="trainingForm.errors.course_name" class="mt-2" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <InputLabel for="tgroup" value="กลุ่มสาระ" />
                        <select id="tgroup" v-model="trainingForm.subject_group" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— เลือก —</option>
                            <option v-for="g in subjectGroups" :key="g" :value="g">{{ g }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="thours" value="จำนวน (ชั่วโมง)" />
                        <TextInput id="thours" v-model="trainingForm.hours" type="number" min="0" class="mt-1 block w-full" />
                        <InputError :message="trainingForm.errors.hours" class="mt-2" />
                    </div>
                </div>
                <div>
                    <InputLabel for="torg" value="หน่วยงานผู้จัด" />
                    <TextInput id="torg" v-model="trainingForm.organizer" type="text" class="mt-1 block w-full" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <InputLabel for="tdate" value="วัน เดือน ปี" />
                        <TextInput id="tdate" v-model="trainingForm.start_date" type="date" class="mt-1 block w-full" />
                        <InputError :message="trainingForm.errors.start_date" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="tbudget" value="งบประมาณจาก" />
                        <TextInput id="tbudget" v-model="trainingForm.budget_source" type="text" class="mt-1 block w-full" />
                    </div>
                </div>
                <div>
                    <InputLabel for="tfile" value="หลักฐานอ้างอิง" />
                    <input id="tfile" type="file" class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100" @input="trainingForm.file = $event.target.files[0]" />
                    <InputError :message="trainingForm.errors.file" class="mt-2" />
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="closeModal">ยกเลิก</SecondaryButton>
                    <PrimaryButton :disabled="trainingForm.processing">บันทึกข้อมูล</PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Modal: รายละเอียด -->
        <Modal :show="detail !== null" @close="closeDetail">
            <div v-if="detail" class="p-6">
                <!-- รายละเอียดรางวัล -->
                <template v-if="detail.type === 'award'">
                    <h2 class="text-lg font-semibold text-gray-900">{{ detail.item.award_name }}</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex gap-3">
                            <dt class="w-32 shrink-0 text-gray-400">ระดับ</dt>
                            <dd class="text-gray-700">{{ detail.item.level || '—' }}</dd>
                        </div>
                        <div class="flex gap-3">
                            <dt class="w-32 shrink-0 text-gray-400">วันที่ได้รับ</dt>
                            <dd class="text-gray-700">{{ detail.item.awarded_date }}</dd>
                        </div>
                        <div v-if="detail.item.awarded_by" class="flex gap-3">
                            <dt class="w-32 shrink-0 text-gray-400">หน่วยงานที่มอบ</dt>
                            <dd class="text-gray-700">{{ detail.item.awarded_by }}</dd>
                        </div>
                        <div v-if="detail.item.note" class="flex gap-3">
                            <dt class="w-32 shrink-0 text-gray-400">หมายเหตุ</dt>
                            <dd class="whitespace-pre-line text-gray-700">{{ detail.item.note }}</dd>
                        </div>
                        <div class="flex gap-3">
                            <dt class="w-32 shrink-0 text-gray-400">หลักฐาน</dt>
                            <dd>
                                <a v-if="detail.item.file_url" :href="detail.item.file_url" target="_blank" class="font-medium text-indigo-600 hover:text-indigo-800">เปิดไฟล์</a>
                                <span v-else class="text-gray-400">— ไม่มีไฟล์แนบ</span>
                            </dd>
                        </div>
                    </dl>
                </template>

                <!-- รายละเอียดการอบรม -->
                <template v-else>
                    <h2 class="text-lg font-semibold text-gray-900">{{ detail.item.course_name }}</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex gap-3">
                            <dt class="w-32 shrink-0 text-gray-400">กลุ่มสาระ</dt>
                            <dd class="text-gray-700">{{ detail.item.subject_group || '—' }}</dd>
                        </div>
                        <div class="flex gap-3">
                            <dt class="w-32 shrink-0 text-gray-400">จำนวนชั่วโมง</dt>
                            <dd class="text-gray-700">{{ detail.item.hours }} ชม.</dd>
                        </div>
                        <div v-if="detail.item.organizer" class="flex gap-3">
                            <dt class="w-32 shrink-0 text-gray-400">หน่วยงานผู้จัด</dt>
                            <dd class="text-gray-700">{{ detail.item.organizer }}</dd>
                        </div>
                        <div class="flex gap-3">
                            <dt class="w-32 shrink-0 text-gray-400">วันที่</dt>
                            <dd class="text-gray-700">{{ detail.item.start_date }}<span v-if="detail.item.end_date"> – {{ detail.item.end_date }}</span></dd>
                        </div>
                        <div v-if="detail.item.location" class="flex gap-3">
                            <dt class="w-32 shrink-0 text-gray-400">สถานที่</dt>
                            <dd class="text-gray-700">{{ detail.item.location }}</dd>
                        </div>
                        <div v-if="detail.item.budget_source" class="flex gap-3">
                            <dt class="w-32 shrink-0 text-gray-400">งบประมาณจาก</dt>
                            <dd class="text-gray-700">{{ detail.item.budget_source }}</dd>
                        </div>
                        <div v-if="detail.item.note" class="flex gap-3">
                            <dt class="w-32 shrink-0 text-gray-400">หมายเหตุ</dt>
                            <dd class="whitespace-pre-line text-gray-700">{{ detail.item.note }}</dd>
                        </div>
                        <div class="flex gap-3">
                            <dt class="w-32 shrink-0 text-gray-400">หลักฐานอ้างอิง</dt>
                            <dd>
                                <a v-if="detail.item.file_url" :href="detail.item.file_url" target="_blank" class="font-medium text-indigo-600 hover:text-indigo-800">เปิดไฟล์</a>
                                <span v-else class="text-gray-400">— ไม่มีไฟล์แนบ</span>
                            </dd>
                        </div>
                    </dl>
                </template>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeDetail">ปิด</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
