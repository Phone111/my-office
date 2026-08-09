<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    groups: { type: Array, default: () => [] }, // dropdown กลุ่มที่ส่ง
    byGroup: { type: Array, default: () => [] }, // [{id,name,users:[{id,name}]}]
    byDepartment: { type: Array, default: () => [] },
    prefill: { type: Object, default: null }, // ส่งต่อ: {title,content,carry_from,attachments:[name]}
});

const senderName = computed(() => usePage().props.auth?.user?.name ?? '');
const isForward = computed(() => !!props.prefill?.carry_from);

// ตอนส่งต่อ เปิดตัวเลือกผู้รับให้เลย (กันลืมเลือกผู้รับ)
const showByGroup = ref(!!props.prefill?.carry_from);
const showByDepartment = ref(false);
const expanded = ref({});
const toggleExpand = (key) => (expanded.value[key] = !expanded.value[key]);

const form = useForm({
    title: props.prefill?.title ?? '',
    content: props.prefill?.content ?? '',
    sender_group_id: null,
    target_users: [],
    files: [null, null, null, null],
    carry_from: props.prefill?.carry_from ?? null,
    is_meeting: false,
    meeting_at: '',
    meeting_place: '',
});

const setFile = (i, e) => (form.files[i] = e.target.files[0] ?? null);

/* ---------- เลือกผู้รับ ---------- */
const bucketIds = (b) => b.users.map((u) => u.id);
const bucketChecked = (b) => b.users.length > 0 && b.users.every((u) => form.target_users.includes(u.id));
const bucketPartial = (b) => !bucketChecked(b) && b.users.some((u) => form.target_users.includes(u.id));
const toggleBucket = (b) => {
    const ids = bucketIds(b);
    if (bucketChecked(b)) form.target_users = form.target_users.filter((id) => !ids.includes(id));
    else form.target_users = [...new Set([...form.target_users, ...ids])];
};
const allIds = (buckets) => buckets.flatMap(bucketIds);
const selectAll = (buckets) => (form.target_users = [...new Set([...form.target_users, ...allIds(buckets)])]);
const clearAll = (buckets) => {
    const ids = allIds(buckets);
    form.target_users = form.target_users.filter((id) => !ids.includes(id));
};
const selectedCount = computed(() => form.target_users.length);

const submit = () => {
    form
        .transform((d) => ({ ...d, files: d.files.filter(Boolean) }))
        .post(route('saraban.circulars.store'), { forceFormData: true });
};
</script>

<template>
    <Head :title="isForward ? 'ส่งหนังสือเวียนต่อ' : 'ส่งหนังสือราชการภายใน'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ isForward ? 'ส่งหนังสือเวียนต่อ' : 'ส่งหนังสือราชการภายใน' }}</h2>
                <Link :href="route('saraban.circulars.index')" class="text-sm text-gray-500 hover:text-gray-700">← รายการที่ส่ง</Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form class="space-y-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100" @submit.prevent="submit">
                    <div v-if="isForward" class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-700">
                        ↪ กำลังส่งต่อหนังสือเวียน — เรื่อง/รายละเอียด/ไฟล์แนบเดิมถูกนำมาให้แล้ว เลือกผู้รับเพิ่มแล้วกดส่งได้เลย
                    </div>

                    <div>
                        <InputLabel for="title" value="เรื่อง" />
                        <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" autofocus />
                        <InputError :message="form.errors.title" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="รายละเอียด" />
                        <RichTextEditor v-model="form.content" class="mt-1" />
                        <InputError :message="form.errors.content" class="mt-2" />
                    </div>

                    <!-- หนังสือเชิญประชุม -->
                    <div class="rounded-xl border border-violet-200 bg-violet-50/50 p-4">
                        <label class="inline-flex items-center gap-2">
                            <input v-model="form.is_meeting" type="checkbox" class="rounded border-gray-300 text-violet-600 focus:ring-violet-500" />
                            <span class="text-sm font-semibold text-violet-800">📅 เป็นหนังสือเชิญประชุม (ให้ผู้รับกดตอบรับได้)</span>
                        </label>
                        <div v-if="form.is_meeting" class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="mat" value="วันเวลาประชุม" />
                                <input id="mat" v-model="form.meeting_at" type="datetime-local" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500" />
                                <InputError :message="form.errors.meeting_at" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="mpl" value="สถานที่" />
                                <TextInput id="mpl" v-model="form.meeting_place" type="text" class="mt-1 block w-full" placeholder="เช่น ห้องประชุม 1" />
                                <InputError :message="form.errors.meeting_place" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="ผู้ส่ง" />
                            <p class="mt-1 rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-700">{{ senderName }}</p>
                        </div>
                        <div>
                            <InputLabel for="sgroup" value="กลุ่มที่ส่ง" />
                            <select id="sgroup" v-model="form.sender_group_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option :value="null">— เลือกกลุ่ม —</option>
                                <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- ส่งถึง -->
                    <div>
                        <div class="flex items-center justify-between">
                            <InputLabel value="ส่งถึง" />
                            <span v-if="selectedCount" class="text-xs font-medium text-indigo-600">เลือกแล้ว {{ selectedCount }} คน</span>
                            <span v-else class="text-xs font-medium text-rose-500">ยังไม่ได้เลือกผู้รับ</span>
                        </div>
                        <InputError :message="form.errors.target_users" class="mt-1" />
                        <div class="mt-2 flex flex-wrap gap-5">
                            <label class="inline-flex items-center gap-2">
                                <input v-model="showByGroup" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-sm text-gray-700">เลือกตามกลุ่มงาน</span>
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input v-model="showByDepartment" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-sm text-gray-700">เลือกตามกลุ่มสาระ</span>
                            </label>
                        </div>

                        <!-- ตามกลุ่มงาน -->
                        <div v-if="showByGroup" class="mt-3 rounded-lg border border-gray-100 bg-gray-50 p-3">
                            <div class="mb-2 flex gap-3 text-xs">
                                <button type="button" class="font-medium text-indigo-600 hover:underline" @click="selectAll(byGroup)">เลือกทั้งหมด</button>
                                <button type="button" class="font-medium text-gray-500 hover:underline" @click="clearAll(byGroup)">เอาออกทั้งหมด</button>
                            </div>
                            <div v-for="b in byGroup" :key="'g' + b.id" class="border-t border-gray-100 py-1.5 first:border-t-0">
                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        :checked="bucketChecked(b)"
                                        :indeterminate="bucketPartial(b)"
                                        @change="toggleBucket(b)"
                                    />
                                    <button type="button" class="flex flex-1 items-center gap-1 text-left text-sm font-medium text-gray-800" @click="toggleExpand('g' + b.id)">
                                        <svg class="h-3.5 w-3.5 text-gray-400 transition" :class="expanded['g' + b.id] ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                                        {{ b.name }} <span class="text-xs font-normal text-gray-400">({{ b.users.length }})</span>
                                    </button>
                                </div>
                                <div v-if="expanded['g' + b.id]" class="ml-7 mt-1 grid grid-cols-1 gap-1 sm:grid-cols-2">
                                    <label v-for="u in b.users" :key="u.id" class="inline-flex items-center gap-2 text-sm text-gray-600">
                                        <input v-model="form.target_users" :value="u.id" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                        {{ u.name }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- ตามกลุ่มสาระ -->
                        <div v-if="showByDepartment" class="mt-3 rounded-lg border border-gray-100 bg-gray-50 p-3">
                            <div class="mb-2 flex gap-3 text-xs">
                                <button type="button" class="font-medium text-indigo-600 hover:underline" @click="selectAll(byDepartment)">เลือกทั้งหมด</button>
                                <button type="button" class="font-medium text-gray-500 hover:underline" @click="clearAll(byDepartment)">เอาออกทั้งหมด</button>
                            </div>
                            <div v-for="b in byDepartment" :key="'d' + b.id" class="border-t border-gray-100 py-1.5 first:border-t-0">
                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        :checked="bucketChecked(b)"
                                        :indeterminate="bucketPartial(b)"
                                        @change="toggleBucket(b)"
                                    />
                                    <button type="button" class="flex flex-1 items-center gap-1 text-left text-sm font-medium text-gray-800" @click="toggleExpand('d' + b.id)">
                                        <svg class="h-3.5 w-3.5 text-gray-400 transition" :class="expanded['d' + b.id] ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                                        {{ b.name }} <span class="text-xs font-normal text-gray-400">({{ b.users.length }})</span>
                                    </button>
                                </div>
                                <div v-if="expanded['d' + b.id]" class="ml-7 mt-1 grid grid-cols-1 gap-1 sm:grid-cols-2">
                                    <label v-for="u in b.users" :key="u.id" class="inline-flex items-center gap-2 text-sm text-gray-600">
                                        <input v-model="form.target_users" :value="u.id" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                        {{ u.name }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <InputLabel value="แนบเอกสาร (สูงสุด 4 ไฟล์)" />
                        <!-- ไฟล์แนบเดิมที่พกมาจากหนังสือเวียนต้นทาง -->
                        <div v-if="prefill?.attachments?.length" class="mt-2 flex flex-wrap gap-2">
                            <span v-for="(name, i) in prefill.attachments" :key="i" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700">{{ name }} <span class="text-xs text-emerald-500">(ส่งต่อด้วย)</span></span>
                        </div>
                        <p v-if="prefill?.attachments?.length" class="mt-1 text-xs text-gray-400">เพิ่มไฟล์ใหม่ด้านล่างได้ (จะส่งรวมกับไฟล์เดิม)</p>
                        <div class="mt-2 space-y-2">
                            <input
                                v-for="i in 4"
                                :key="i"
                                type="file"
                                class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                                @change="setFile(i - 1, $event)"
                            />
                        </div>
                        <InputError :message="form.errors['files.0']" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <Link :href="route('saraban.circulars.index')">
                            <SecondaryButton type="button">ยกเลิก</SecondaryButton>
                        </Link>
                        <PrimaryButton :disabled="form.processing || selectedCount === 0">
                            {{ isForward ? 'ส่งหนังสือเวียนต่อ' : 'บันทึกการส่ง' }} <span v-if="selectedCount">({{ selectedCount }})</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
