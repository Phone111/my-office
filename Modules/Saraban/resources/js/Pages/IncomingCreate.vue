<script setup>
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    divisions: { type: Array, default: () => [] },
    priorities: { type: Array, default: () => [] }, // [{value,label}]
    approverOptions: { type: Array, default: () => [] }, // [{role,label,users:[{id,name}]}]
    justReceived: { type: Object, default: null }, // {number,title,forwarded}
});

const SCHOOL = 'โรงเรียนเศรษฐบุตรบำเพ็ญ';

const dotColor = {
    normal: 'bg-emerald-500',
    urgent: 'bg-fuchsia-500',
    very_urgent: 'bg-orange-500',
    most_urgent: 'bg-rose-600',
};

const form = useForm({
    priority: 'normal',
    source_number: '',
    source_date: '',
    title: '',
    source_name: '',
    division: '',
    content: '',
    cover_file: null,
    attachments: [],
    first_approver_id: null,
});

// ===== ไฟล์แนบ (หนังสือนำ + เอกสารแนบ 1-4) =====
const fileSlots = ref([null, null, null, null]);
const onCover = (e) => (form.cover_file = e.target.files[0] ?? null);
const onSlot = (i, e) => (fileSlots.value[i] = e.target.files[0] ?? null);

// ===== เสนอผู้บริหาร (บังคับเลือก) =====
const selectedRole = ref(props.approverOptions[0]?.role ?? null);
const currentGroup = computed(() => props.approverOptions.find((g) => g.role === selectedRole.value) ?? null);
watch(
    selectedRole,
    () => {
        form.first_approver_id = currentGroup.value?.users[0]?.id ?? null;
    },
    { immediate: true },
);

const submit = () => {
    form.transform((data) => ({
        ...data,
        attachments: fileSlots.value.filter(Boolean),
    })).post(route('saraban.incoming.store'), { forceFormData: true });
};
</script>

<template>
    <Head title="ลงทะเบียนรับหนังสือนอกระบบ" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ลงทะเบียนรับหนังสือนอกระบบ</h2>
                <Link :href="route('saraban.documents.index', { category: 'incoming' })" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    &larr; แฟ้มรับหนังสือราชการ
                </Link>
            </div>
        </template>

        <div class="py-10">
            <!-- ผลการลงรับล่าสุด — โชว์เลขทะเบียนรับเด่นๆ -->
            <div v-if="justReceived" class="mx-auto mb-6 max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-6 py-5 text-center">
                    <p class="text-sm font-medium text-emerald-700">ลงรับหนังสือภายนอกเรียบร้อย</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-800">เลขทะเบียนรับ : {{ justReceived.number }}</p>
                    <p class="mt-1 text-sm text-emerald-600">{{ justReceived.title }}<span v-if="justReceived.forwarded"> · เสนอผู้บริหารแล้ว</span></p>
                    <Link :href="route('saraban.documents.index', { category: 'incoming' })" class="mt-3 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        ดูแฟ้มรับหนังสือราชการ &rarr;
                    </Link>
                </div>
            </div>

            <form class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8" @submit.prevent="submit">
                <div class="space-y-5 rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
                    <!-- ประเภท -->
                    <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                        <span class="w-32 shrink-0 text-right text-[15px] font-semibold text-gray-800">ประเภท</span>
                        <label v-for="p in priorities" :key="p.value" class="inline-flex cursor-pointer items-center gap-1.5 text-[15px]">
                            <input v-model="form.priority" type="radio" :value="p.value" class="text-indigo-600 focus:ring-indigo-500" />
                            <span class="h-4 w-4 rounded-sm" :class="dotColor[p.value]" />
                            <span class="text-gray-700">{{ p.label }}</span>
                        </label>
                    </div>

                    <!-- เลขที่หนังสือ -->
                    <div class="flex items-center gap-3">
                        <label class="w-32 shrink-0 text-right text-[15px] font-semibold text-gray-800">เลขที่หนังสือ</label>
                        <input v-model="form.source_number" type="text" placeholder="เลขที่หนังสือต้นเรื่อง (ของผู้ส่ง)" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <!-- ลงวันที่ -->
                    <div class="flex items-center gap-3">
                        <label class="w-32 shrink-0 text-right text-[15px] font-semibold text-gray-800">ลงวันที่</label>
                        <input v-model="form.source_date" type="date" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <!-- เรื่อง -->
                    <div class="flex items-center gap-3">
                        <label class="w-32 shrink-0 text-right text-[15px] font-semibold text-gray-800">เรื่อง</label>
                        <input v-model="form.title" type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" autofocus />
                    </div>
                    <InputError :message="form.errors.title" class="ml-32 pl-3" />

                    <!-- จาก -->
                    <div class="flex items-center gap-3">
                        <label class="w-32 shrink-0 text-right text-[15px] font-semibold text-gray-800">จาก</label>
                        <input v-model="form.source_name" type="text" placeholder="หน่วยงาน/ผู้ส่ง" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <InputError :message="form.errors.source_name" class="ml-32 pl-3" />

                    <!-- หนังสือของกลุ่ม -->
                    <div class="flex items-center gap-3">
                        <label class="w-32 shrink-0 text-right text-[15px] font-semibold text-gray-800">หนังสือของกลุ่ม</label>
                        <select v-model="form.division" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— เลือก —</option>
                            <option v-for="d in divisions" :key="d" :value="d">{{ d }}</option>
                        </select>
                    </div>

                    <!-- เกษียนหนังสือ -->
                    <div>
                        <label class="text-[15px] font-semibold text-gray-800">เกษียนหนังสือ</label>
                        <RichTextEditor v-model="form.content" class="mt-1" />
                    </div>

                    <!-- ไฟล์แนบ -->
                    <div class="space-y-2 border-t border-gray-100 pt-4">
                        <div class="flex items-center gap-3">
                            <label class="w-32 shrink-0 text-right text-[15px] font-semibold text-gray-800">หนังสือนำ</label>
                            <input type="file" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100" @change="onCover" />
                        </div>
                        <div v-for="i in 4" :key="i" class="flex items-center gap-3">
                            <label class="w-32 shrink-0 text-right text-[15px] text-gray-600">เอกสารแนบ {{ i }}</label>
                            <input type="file" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-gray-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-600 hover:file:bg-gray-100" @change="(e) => onSlot(i - 1, e)" />
                        </div>
                    </div>
                </div>

                <!-- เสนอผู้บริหาร (บังคับเลือก) -->
                <div class="mt-5 overflow-hidden rounded-2xl bg-yellow-50 shadow-sm ring-1 ring-yellow-200">
                    <div class="border-b border-yellow-200 px-6 py-3 text-center text-base font-semibold text-gray-700">
                        เสนอ <span class="text-rose-600">*</span>
                    </div>
                    <div class="px-6 py-5">
                        <div class="flex flex-wrap justify-center gap-x-5 gap-y-2">
                            <label v-for="g in approverOptions" :key="g.role" class="inline-flex cursor-pointer items-center gap-1.5 text-[15px]">
                                <input v-model="selectedRole" type="radio" :value="g.role" class="text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-gray-700">{{ g.label }}</span>
                            </label>
                        </div>
                        <div v-if="currentGroup" class="mx-auto mt-4 max-w-md space-y-2">
                            <label
                                v-for="u in currentGroup.users"
                                :key="u.id"
                                class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2 text-[15px] transition"
                                :class="form.first_approver_id === u.id ? 'border-indigo-400 bg-indigo-50 text-indigo-800' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                            >
                                <input v-model="form.first_approver_id" type="radio" :value="u.id" class="text-indigo-600 focus:ring-indigo-500" />
                                <span>{{ u.name }}</span>
                            </label>
                        </div>
                        <p v-else class="mt-4 text-center text-sm text-gray-400">ยังไม่มีรายชื่อผู้เสนอในระบบ</p>
                        <InputError :message="form.errors.first_approver_id" class="mt-2 text-center" />
                    </div>
                </div>

                <div class="flex justify-center pt-4">
                    <PrimaryButton :disabled="form.processing || !form.first_approver_id">บันทึกเอกสาร</PrimaryButton>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
