<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    byGroup: { type: Array, default: () => [] },
    byDepartment: { type: Array, default: () => [] },
});

const showByGroup = ref(false);
const showByDepartment = ref(false);
const expanded = ref({});
const toggleExpand = (key) => (expanded.value[key] = !expanded.value[key]);

const form = useForm({ title: '', content: '', target_users: [], files: [null, null, null, null] });
const setFile = (i, e) => (form.files[i] = e.target.files[0] ?? null);

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

// บุคลากรทั้งหมด (รวมจากทุกกลุ่ม ไม่ซ้ำ) + ค้นหา + ส่งทุกคน
const allPeople = computed(() => {
    const seen = new Map();
    [...props.byGroup, ...props.byDepartment].forEach((b) => b.users.forEach((u) => seen.set(u.id, u)));
    return [...seen.values()].sort((a, b) => a.name.localeCompare(b.name, 'th'));
});
const search = ref('');
const filteredPeople = computed(() => {
    const q = search.value.trim().toLowerCase();
    return q ? allPeople.value.filter((u) => u.name.toLowerCase().includes(q)) : [];
});
const everyoneSelected = computed(() => allPeople.value.length > 0 && allPeople.value.every((u) => form.target_users.includes(u.id)));
const toggleEveryone = () => (form.target_users = everyoneSelected.value ? [] : allPeople.value.map((u) => u.id));
const clearEveryone = () => (form.target_users = []);

const submit = () =>
    form
        .transform((d) => ({ ...d, files: d.files.filter(Boolean) }))
        .post(route('saraban.personal-documents.store'), { forceFormData: true });
</script>

<template>
    <Head title="ส่งเอกสารทั่วไป" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ส่งเอกสารทั่วไป</h2>
                <Link :href="route('saraban.personal-documents.index')" class="text-sm text-gray-500 hover:text-gray-700">← แฟ้มรับเอกสารทั่วไป</Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form class="space-y-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100" @submit.prevent="submit">
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

                    <!-- ส่งถึง -->
                    <div>
                        <div class="flex items-center justify-between">
                            <InputLabel value="ส่งถึง" />
                            <span v-if="selectedCount" class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700">เลือกแล้ว {{ selectedCount }} คน</span>
                        </div>

                        <!-- ส่งทุกคน + ล้าง -->
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button type="button" class="rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 transition hover:bg-indigo-100" @click="toggleEveryone">
                                {{ everyoneSelected ? '✕ ยกเลิกทุกคน' : 'ส่งทุกคนในหน่วยงาน (' + allPeople.length + ')' }}
                            </button>
                            <button v-if="selectedCount" type="button" class="rounded-full border border-rose-200 px-3 py-1 text-xs font-medium text-rose-600 transition hover:bg-rose-50" @click="clearEveryone">ล้างทั้งหมด</button>
                        </div>

                        <!-- ค้นหาบุคลากร -->
                        <div class="relative mt-2">
                            <svg class="pointer-events-none absolute left-2.5 top-2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                            <input v-model="search" type="text" placeholder="ค้นหาชื่อบุคลากร..." class="w-full rounded-md border-gray-300 py-1.5 pl-8 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>

                        <!-- ผลค้นหา (แสดงเฉพาะตอนค้นหา) -->
                        <div v-if="search" class="mt-2 max-h-56 overflow-y-auto rounded-lg border border-gray-100 bg-gray-50 p-3">
                            <label v-for="u in filteredPeople" :key="'s' + u.id" class="flex items-center gap-2 rounded px-1 py-1 text-sm text-gray-700 hover:bg-white">
                                <input v-model="form.target_users" :value="u.id" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                {{ u.name }}
                            </label>
                            <p v-if="!filteredPeople.length" class="px-1 py-2 text-center text-xs text-gray-400">ไม่พบบุคลากรที่ค้นหา</p>
                        </div>

                        <div v-show="!search" class="mt-3 flex flex-wrap gap-5">
                            <label class="inline-flex items-center gap-2">
                                <input v-model="showByGroup" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-sm text-gray-700">เลือกบุคลากรตามกลุ่ม</span>
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input v-model="showByDepartment" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-sm text-gray-700">เลือกตามกลุ่มสาระ</span>
                            </label>
                        </div>

                        <div v-if="showByGroup && !search" class="mt-3 rounded-lg border border-gray-100 bg-gray-50 p-3">
                            <div class="mb-2 flex gap-3 text-xs">
                                <button type="button" class="font-medium text-indigo-600 hover:underline" @click="selectAll(byGroup)">เลือกทั้งหมด</button>
                                <button type="button" class="font-medium text-gray-500 hover:underline" @click="clearAll(byGroup)">เอาออกทั้งหมด</button>
                            </div>
                            <div v-for="b in byGroup" :key="'g' + b.id" class="border-t border-gray-100 py-1.5 first:border-t-0">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" :checked="bucketChecked(b)" :indeterminate="bucketPartial(b)" @change="toggleBucket(b)" />
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

                        <div v-if="showByDepartment && !search" class="mt-3 rounded-lg border border-gray-100 bg-gray-50 p-3">
                            <div class="mb-2 flex gap-3 text-xs">
                                <button type="button" class="font-medium text-indigo-600 hover:underline" @click="selectAll(byDepartment)">เลือกทั้งหมด</button>
                                <button type="button" class="font-medium text-gray-500 hover:underline" @click="clearAll(byDepartment)">เอาออกทั้งหมด</button>
                            </div>
                            <div v-for="b in byDepartment" :key="'d' + b.id" class="border-t border-gray-100 py-1.5 first:border-t-0">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" :checked="bucketChecked(b)" :indeterminate="bucketPartial(b)" @change="toggleBucket(b)" />
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
                        <InputError :message="form.errors.target_users" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="แนบเอกสาร (สูงสุด 4 ไฟล์)" />
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
                        <Link :href="route('saraban.personal-documents.index')">
                            <SecondaryButton type="button">ยกเลิก</SecondaryButton>
                        </Link>
                        <PrimaryButton :disabled="form.processing">บันทึกการส่ง</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
