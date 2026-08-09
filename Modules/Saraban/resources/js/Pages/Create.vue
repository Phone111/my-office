<script setup>
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    category: { type: String, default: 'memo' },
    folders: { type: Array, default: () => [] },
    divisions: { type: Array, default: () => [] },
    priorities: { type: Array, default: () => [] }, // [{value,label}]
    document: { type: Object, default: null }, // โหมดแก้ไข+เสนอใหม่ (เอกสารที่ถูกตีกลับ)
});

const isEditing = computed(() => !!props.document);

const SCHOOL = 'โรงเรียนเศรษฐบุตรบำเพ็ญ';

// หัวกระดาษตามหมวดเอกสาร
const HEADINGS = {
    memo: 'บันทึกข้อความ',
    incoming: 'หนังสือรับ',
    outgoing: 'หนังสือส่ง',
    internal_out: 'หนังสือภายใน',
    internal_in: 'หนังสือภายใน',
    general_out: 'เอกสารทั่วไป',
    general_in: 'เอกสารทั่วไป',
    report: 'รายงานโครงการ',
    order: 'คำสั่ง',
};
const heading = computed(() => HEADINGS[props.category] ?? 'บันทึกข้อความ');

// ชื่อ + ตำแหน่งของผู้เขียน (ลงท้ายบันทึก)
const me = computed(() => usePage().props.auth?.user?.name ?? '');
const roles = computed(() => usePage().props.auth?.roles ?? []);
const positionLabel = computed(() => {
    const map = { director: 'ผอ.', deputy_director: 'รองผอ.', secretary: 'เลขานุการ' };
    for (const r of roles.value) if (map[r]) return map[r];
    return me.value;
});

// สีจุดประเภทความเร่งด่วน
const dotColor = {
    normal: 'bg-emerald-500',
    urgent: 'bg-fuchsia-500',
    very_urgent: 'bg-orange-500',
    most_urgent: 'bg-rose-600',
};

const form = useForm({
    category: props.document?.category ?? props.category,
    title: props.document?.title ?? '',
    content: props.document?.content ?? '',
    priority: props.document?.priority ?? 'normal',
    division: props.document?.division ?? '',
});

const submit = () => {
    if (isEditing.value) {
        form.put(route('saraban.documents.resubmit', props.document.id));
    } else {
        form.post(route('saraban.documents.store'));
    }
};
</script>

<template>
    <Head :title="isEditing ? 'แก้ไขบันทึก (เสนอใหม่)' : 'เขียนบันทึกเสนอแฟ้ม'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ isEditing ? 'แก้ไขบันทึก & เสนอใหม่' : 'เขียนบันทึกเสนอแฟ้ม' }}</h2>
                <Link :href="route('saraban.documents.index')" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    &larr; กลับแฟ้มเอกสาร
                </Link>
            </div>
        </template>

        <div class="py-10">
            <form class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8" @submit.prevent="submit">
                <!-- เหตุผลที่ถูกตีกลับ -->
                <div v-if="isEditing" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <span class="font-semibold">เอกสารถูกตีกลับ</span> — แก้ไขแล้วกด "เสนอใหม่" เพื่อส่งกลับเข้าเส้นทางเดิม
                    <span v-if="document.reject_comment" class="mt-1 block text-rose-600">เหตุผล: {{ document.reject_comment }}</span>
                </div>

                <!-- ชั้นความเร็ว -->
                <div class="mb-3 flex flex-wrap items-center gap-x-5 gap-y-2">
                    <span class="text-[15px] font-semibold text-gray-800">ชั้นความเร็ว:</span>
                    <label v-for="p in priorities" :key="p.value" class="inline-flex cursor-pointer items-center gap-1.5 text-[15px]">
                        <input v-model="form.priority" type="radio" :value="p.value" class="text-indigo-600 focus:ring-indigo-500" />
                        <span class="h-4 w-4 rounded-sm" :class="dotColor[p.value]" />
                        <span class="text-gray-700">{{ p.label }}</span>
                    </label>
                </div>

                <!-- กระดาษบันทึกข้อความ -->
                <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
                    <!-- ตราครุฑ + หัวเรื่อง -->
                    <div class="flex items-center justify-between gap-4">
                        <img src="/images/garuda.png" alt="ตราครุฑ" class="h-20 w-20 shrink-0 object-contain" @error="$event.target.style.visibility = 'hidden'" />
                        <h1 class="text-2xl font-bold text-gray-900">{{ heading }}</h1>
                        <div class="h-20 w-20 shrink-0" />
                    </div>

                    <!-- ส่วนราชการ -->
                    <div class="mt-6 flex flex-wrap items-center gap-2 text-[15px]">
                        <span class="font-semibold text-gray-800">ส่วนราชการ</span>
                        <select v-model="form.division" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— เลือก —</option>
                            <option v-for="d in divisions" :key="d" :value="d">{{ d }}</option>
                        </select>
                        <span class="text-gray-700">{{ SCHOOL }}</span>
                    </div>
                    <InputError :message="form.errors.division" class="mt-1" />

                    <!-- เรื่อง -->
                    <div class="mt-4 flex items-center gap-3">
                        <label class="shrink-0 text-[15px] font-semibold text-gray-800" for="title">เรื่อง</label>
                        <input id="title" v-model="form.title" type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" autofocus />
                    </div>
                    <InputError :message="form.errors.title" class="ml-12 mt-1" />

                    <!-- เรียน -->
                    <p class="mt-4 text-[15px] text-gray-800"><span class="font-semibold">เรียน</span> ผู้อำนวยการ{{ SCHOOL }}</p>

                    <!-- รายละเอียด -->
                    <div class="mt-3">
                        <label class="text-[15px] font-semibold text-gray-800">รายละเอียด</label>
                        <RichTextEditor v-model="form.content" class="mt-1" />
                        <InputError :message="form.errors.content" class="mt-1" />
                    </div>

                    <!-- ลายเซ็นผู้เขียน -->
                    <div class="mt-8 text-center">
                        <div class="mx-auto mb-1 h-12 w-32 rounded border border-dashed border-gray-200" />
                        <p class="text-sm text-gray-700">({{ positionLabel }})</p>
                        <p class="text-sm text-gray-500">{{ SCHOOL }}</p>
                    </div>
                </div>

                <p class="mt-4 text-center text-sm text-gray-400">{{ isEditing ? 'เมื่อกดเสนอใหม่ เอกสารจะถูกส่งกลับเข้าเส้นทางเดิมอีกครั้ง' : 'บันทึกเป็นร่างก่อน แล้วค่อยกด "เสนอแฟ้ม" เพื่อเลือกผู้รับและส่ง' }}</p>

                <div class="flex justify-center pt-2">
                    <PrimaryButton :disabled="form.processing">{{ isEditing ? 'แก้ไข & เสนอใหม่' : 'บันทึกร่าง' }}</PrimaryButton>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
