<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    backups: { type: Array, default: () => [] },
});
const flash = computed(() => usePage().props.flash ?? {});

const clearCache = () => router.post(route('admin.maintenance.clear-cache'), {}, { preserveScroll: true });
const optimize = () => router.post(route('admin.maintenance.optimize'), {}, { preserveScroll: true });
const backup = () => router.post(route('admin.maintenance.backup'), {}, { preserveScroll: true });
const removeBackup = (name) => {
    if (confirm(`ลบไฟล์สำรอง "${name}"?`)) router.delete(route('admin.maintenance.backup.destroy', name), { preserveScroll: true });
};
</script>

<template>
    <Head title="ปรับปรุงดูแลฐานข้อมูล" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">ปรับปรุงดูแลฐานข้อมูล</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash.success }}</div>
                <div v-if="flash.error" class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-100">{{ flash.error }}</div>

                <!-- Cache -->
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <p class="mb-1 text-sm font-semibold text-gray-700">แคชระบบ (Cache)</p>
                    <p class="mb-3 text-xs text-gray-400">ล้างแคชเมื่อระบบทำงานผิดปกติ · สร้างแคชเพื่อเพิ่มความเร็ว</p>
                    <div class="flex flex-wrap gap-2">
                        <button class="rounded-md bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600" @click="clearCache">ล้างแคชทั้งหมด</button>
                        <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" @click="optimize">สร้างแคช (Optimize)</button>
                    </div>
                </div>

                <!-- Backup -->
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-700">สำรองฐานข้อมูล (Backup)</p>
                            <p class="text-xs text-gray-400">สร้างไฟล์ .sql ของฐานข้อมูลปัจจุบัน (ต้องมี mysqldump)</p>
                        </div>
                        <button class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" @click="backup">สำรองข้อมูลตอนนี้</button>
                    </div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3">ไฟล์</th><th class="px-4 py-3">ขนาด</th><th class="px-4 py-3">สร้างเมื่อ</th><th class="px-4 py-3 text-center">ลบ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="backups.length === 0"><td colspan="4" class="px-6 py-8"><EmptyState title="ยังไม่มีไฟล์สำรอง" /></td></tr>
                            <tr v-for="b in backups" :key="b.name" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-gray-800">{{ b.name }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ b.size_kb }} KB</td>
                                <td class="px-4 py-3 text-gray-500">{{ b.created_at }}</td>
                                <td class="px-4 py-3 text-center"><button class="text-rose-600 hover:underline" @click="removeBackup(b.name)">ลบ</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
