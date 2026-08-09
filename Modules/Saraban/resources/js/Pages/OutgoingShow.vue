<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    doc: { type: Object, required: true },
});

const flash = computed(() => usePage().props.flash ?? {});
</script>

<template>
    <Head title="ตรวจสอบเอกสาร" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ตรวจสอบเอกสาร</h2>
                <Link :href="route('saraban.outgoing.pending')" class="text-sm font-medium text-gray-500 hover:text-gray-700">← แฟ้มรอแนบไฟล์ส่ง</Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ flash.success }}</div>

                <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <h1 class="text-lg font-bold text-rose-600">ตรวจสอบเอกสาร</h1>
                        <Link :href="route('saraban.outgoing.attach-form', doc.id)" class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-800">แก้ไข</Link>
                    </div>

                    <dl class="mt-5 space-y-3 text-[15px] text-gray-800">
                        <div class="flex gap-3"><dt class="w-28 shrink-0 font-semibold">เลขทะเบียน</dt><dd class="font-mono font-semibold text-indigo-700">{{ doc.document_number }}</dd></div>
                        <div class="flex gap-3"><dt class="w-28 shrink-0 font-semibold">เรื่อง</dt><dd>{{ doc.title }}</dd></div>
                        <div class="flex gap-3"><dt class="w-28 shrink-0 font-semibold">ลงวันที่</dt><dd>{{ doc.date_thai ?? '—' }}</dd></div>
                        <div class="flex gap-3"><dt class="w-28 shrink-0 font-semibold">ถึง</dt><dd>{{ doc.to ?? '—' }}</dd></div>
                        <div class="flex gap-3"><dt class="w-28 shrink-0 font-semibold">ผู้ส่ง</dt><dd>{{ doc.issuer ?? '—' }}</dd></div>
                        <div class="flex gap-3"><dt class="w-28 shrink-0 font-semibold">เจ้าของเรื่อง</dt><dd>{{ doc.owner ?? '—' }}</dd></div>
                        <div class="flex gap-3"><dt class="w-28 shrink-0 font-semibold">ว/ด/ป ที่ส่ง</dt><dd>{{ doc.sent_thai ?? '—' }}</dd></div>
                    </dl>

                    <div class="mt-5 border-t border-gray-100 pt-4">
                        <p class="mb-2 text-sm font-semibold text-gray-700">เอกสารแนบ</p>
                        <div v-if="doc.files.length" class="flex flex-wrap gap-2">
                            <a v-for="(f, i) in doc.files" :key="f.url ?? i" :href="f.url" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-1.5 text-sm font-medium text-indigo-700 hover:bg-indigo-100">{{ f.name }}</a>
                        </div>
                        <p v-else class="text-sm text-gray-400">— ไม่มีไฟล์แนบ —</p>
                    </div>

                    <!-- ส่งเข้าระบบรับส่งหนังสือราชการ (AMSS: ปุ่ม "ส่ง ร.ร.") -->
                    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4">
                        <p class="text-sm text-gray-500">ส่งหนังสือฉบับนี้ถึงโรงเรียน/หน่วยงาน — เลขที่ เรื่อง และไฟล์จะถูกนำไปด้วย</p>
                        <Link :href="route('saraban.area-mail.compose', { from_outgoing: doc.id })" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700">ส่ง ร.ร. / หน่วยงาน →</Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
