<script setup>
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    priorities: { type: Array, default: () => [] }, // [{value,label}]
    numberHint: { type: String, default: '' },
    ownerBuckets: { type: Array, default: () => [] }, // [{key,name,users:[{id,name}]}]
    justIssued: { type: Object, default: null }, // {number,title}
});

const issuerName = computed(() => usePage().props.auth?.user?.name ?? '');

const dotColor = {
    normal: 'bg-emerald-500',
    urgent: 'bg-fuchsia-500',
    very_urgent: 'bg-orange-500',
    most_urgent: 'bg-rose-600',
};

const form = useForm({
    priority: 'normal',
    source_date: '',
    title: '',
    owners: [],
});

// ===== เลือกเจ้าของเรื่อง (จัดกลุ่ม — กางเลือกได้) =====
const expanded = ref({}); // หุบทุกกลุ่มไว้เริ่มต้น (กดเองเพื่อกาง)
const toggleExpand = (key) => (expanded.value[key] = !expanded.value[key]);

const bucketIds = (b) => b.users.map((u) => u.id);
const bucketChecked = (b) => b.users.length > 0 && b.users.every((u) => form.owners.includes(u.id));
const bucketPartial = (b) => !bucketChecked(b) && b.users.some((u) => form.owners.includes(u.id));
const toggleBucket = (b) => {
    const ids = bucketIds(b);
    if (bucketChecked(b)) form.owners = form.owners.filter((id) => !ids.includes(id));
    else form.owners = [...new Set([...form.owners, ...ids])];
};
const allIds = computed(() => props.ownerBuckets.flatMap(bucketIds));
const selectAll = () => (form.owners = [...new Set([...form.owners, ...allIds.value])]);
const clearAll = () => (form.owners = []);
const selectedCount = computed(() => form.owners.length);

const submit = () => form.post(route('saraban.outgoing.store'));
</script>

<template>
    <Head title="ออกเลขทะเบียนส่ง" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ออกเลขทะเบียนส่ง</h2>
                <Link :href="route('saraban.documents.index', { category: 'outgoing' })" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    &larr; แฟ้มส่งหนังสือราชการ
                </Link>
            </div>
        </template>

        <div class="py-10">
            <!-- ผลการออกเลขล่าสุด -->
            <div v-if="justIssued" class="mx-auto mb-6 max-w-2xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-6 py-5 text-center">
                    <p class="text-sm font-medium text-emerald-700">ออกเลขทะเบียนส่งเรียบร้อย</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-800">เลขทะเบียนส่ง : {{ justIssued.number }}</p>
                    <p class="mt-1 text-sm text-emerald-600">{{ justIssued.title }}</p>
                    <Link :href="route('saraban.documents.index', { category: 'outgoing' })" class="mt-3 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        ดูแฟ้มส่งหนังสือราชการ &rarr;
                    </Link>
                </div>
            </div>

            <form class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8" @submit.prevent="submit">
                <div class="space-y-5 rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
                    <!-- ประเภท -->
                    <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                        <span class="w-28 shrink-0 text-right text-[15px] font-semibold text-gray-800">ประเภท :</span>
                        <label v-for="p in priorities" :key="p.value" class="inline-flex cursor-pointer items-center gap-1.5 text-[15px]">
                            <input v-model="form.priority" type="radio" :value="p.value" class="text-indigo-600 focus:ring-indigo-500" />
                            <span class="h-4 w-4 rounded-sm" :class="dotColor[p.value]" />
                            <span class="text-gray-700">{{ p.label }}</span>
                        </label>
                    </div>

                    <!-- เลขทะเบียน (ตัวอย่าง) -->
                    <div class="flex items-center gap-3">
                        <span class="w-28 shrink-0 text-right text-[15px] font-semibold text-gray-800">เลขทะเบียน :</span>
                        <span class="font-mono text-[15px] text-gray-500">{{ numberHint }} <span class="text-xs text-gray-400">(ระบบออกเลขอัตโนมัติเมื่อบันทึก)</span></span>
                    </div>

                    <!-- ลงวันที่ -->
                    <div class="flex items-center gap-3">
                        <label class="w-28 shrink-0 text-right text-[15px] font-semibold text-gray-800">ลงวันที่ :</label>
                        <input v-model="form.source_date" type="date" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <!-- เรื่อง -->
                    <div class="flex items-center gap-3">
                        <label class="w-28 shrink-0 text-right text-[15px] font-semibold text-gray-800">เรื่อง :</label>
                        <input v-model="form.title" type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" autofocus />
                    </div>
                    <InputError :message="form.errors.title" class="ml-28 pl-3" />

                    <!-- ผู้ออกเลข -->
                    <div class="flex items-center gap-3">
                        <span class="w-28 shrink-0 text-right text-[15px] font-semibold text-gray-800">ผู้ออกเลข :</span>
                        <span class="text-[15px] text-gray-600">{{ issuerName }}</span>
                    </div>

                    <!-- เจ้าของเรื่อง (เลือกรายบุคคล จัดกลุ่ม) -->
                    <div class="flex items-start gap-3">
                        <span class="w-28 shrink-0 pt-1 text-right text-[15px] font-semibold text-gray-800">เจ้าของเรื่อง :</span>
                        <div class="min-w-0 flex-1">
                            <div class="mb-2 flex items-center gap-3 text-xs">
                                <button type="button" class="font-medium text-indigo-600 hover:underline" @click="selectAll">เลือกทั้งหมด</button>
                                <button type="button" class="font-medium text-gray-500 hover:underline" @click="clearAll">เอาออกทั้งหมด</button>
                                <span v-if="selectedCount" class="ml-auto font-medium text-indigo-600">เลือกแล้ว {{ selectedCount }} คน</span>
                            </div>
                            <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                                <div v-for="b in ownerBuckets" :key="b.key" class="border-t border-gray-100 py-1.5 first:border-t-0">
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            :checked="bucketChecked(b)"
                                            :indeterminate="bucketPartial(b)"
                                            @change="toggleBucket(b)"
                                        />
                                        <button type="button" class="flex flex-1 items-center gap-1 text-left text-sm font-medium text-gray-800" @click="toggleExpand(b.key)">
                                            <svg class="h-3.5 w-3.5 text-gray-400 transition" :class="expanded[b.key] ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                                            {{ b.name }} <span class="text-xs font-normal text-gray-400">({{ b.users.length }})</span>
                                        </button>
                                    </div>
                                    <div v-if="expanded[b.key]" class="ml-7 mt-1 grid grid-cols-1 gap-1 sm:grid-cols-2">
                                        <label v-for="u in b.users" :key="u.id" class="inline-flex items-center gap-2 text-sm text-gray-600">
                                            <input v-model="form.owners" :value="u.id" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                            {{ u.name }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <InputError :message="form.errors.owners" class="mt-1" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-center pt-4">
                    <PrimaryButton :disabled="form.processing || selectedCount === 0">บันทึกออกเลข</PrimaryButton>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
