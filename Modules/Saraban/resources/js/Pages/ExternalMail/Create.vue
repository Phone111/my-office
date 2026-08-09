<script setup>
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    sources: { type: Array, default: () => [] },
    priorities: { type: Array, default: () => [] },
    groups: { type: Array, default: () => [] },
});

const today = new Date().toISOString().slice(0, 10);
const form = useForm({ source_type: 'obec', source_name: '', number: '', doc_date: today, subject: '', detail: '', priority: 'normal', confidential: false, assigned_group_id: '', attachments: [] });

const onFiles = (e) => (form.attachments = Array.from(e.target.files));
const submit = () => form.post(route('saraban.external-mail.store'), { forceFormData: true });
</script>

<template>
    <Head title="ลงทะเบียนรับหนังสือจากภายนอก" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">ลงทะเบียนรับหนังสือจากหน่วยงานภายนอก</h2>
        </template>

        <div class="py-10">
            <form class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8" @submit.prevent="submit">
                <div class="space-y-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">หน่วยงานต้นทาง</label>
                            <select v-model="form.source_type" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="s in sources" :key="s.key" :value="s.key">{{ s.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">ชื่อหน่วยงาน (ละเอียด)</label>
                            <input v-model="form.source_name" type="text" placeholder="เช่น สำนักงานเขตพื้นที่การศึกษา..." class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">เลขที่หนังสือ (ต้นทาง)</label>
                            <input v-model="form.number" type="text" placeholder="เช่น ศธ 04009/ว1234" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">ลงวันที่</label>
                            <input v-model="form.doc_date" type="date" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <p v-if="form.errors.doc_date" class="mt-1 text-xs text-rose-500">{{ form.errors.doc_date }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">เรื่อง</label>
                        <input v-model="form.subject" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.subject" class="mt-1 text-xs text-rose-500">{{ form.errors.subject }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">เนื้อหาโดยสรุป</label>
                        <textarea v-model="form.detail" rows="3" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">ความสำคัญ</label>
                            <select v-model="form.priority" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="p in priorities" :key="p.key" :value="p.key">{{ p.label }}</option>
                            </select>
                        </div>
                        <label class="mt-6 flex items-center gap-2 text-sm text-gray-700">
                            <input v-model="form.confidential" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" /> ลับ
                        </label>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">มอบกลุ่มงาน (จะมอบภายหลังก็ได้)</label>
                        <select v-model="form.assigned_group_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— ยังไม่มอบ —</option>
                            <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">แนบไฟล์ (หลายไฟล์ได้)</label>
                        <input type="file" multiple class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700" @change="onFiles" />
                        <p v-if="form.attachments.length" class="mt-1 text-xs text-gray-400">{{ form.attachments.length }} ไฟล์</p>
                    </div>
                    <p class="text-xs text-gray-400">เลขทะเบียนรับจะออกอัตโนมัติเมื่อบันทึก (วิ่งต่อปี พ.ศ.)</p>
                </div>

                <div class="flex justify-end gap-3">
                    <SecondaryButton type="button" @click="$inertia.visit(route('saraban.external-mail.index'))">ยกเลิก</SecondaryButton>
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60">ลงทะเบียนรับ</button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
