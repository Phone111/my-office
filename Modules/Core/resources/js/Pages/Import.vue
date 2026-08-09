<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    roles: { type: Array, default: () => [] },
    unitCount: { type: Number, default: 0 },
    userCount: { type: Number, default: 0 },
});

const page = usePage();
const flash = computed(() => page.props.flash?.success);
const importErrors = computed(() => page.props.flash?.importErrors ?? []);

const unitForm = useForm({ file: null });
const userForm = useForm({ file: null });
const submitUnits = () => unitForm.post(route('import.units'), { forceFormData: true, preserveScroll: true });
const submitUsers = () => userForm.post(route('import.users'), { forceFormData: true, preserveScroll: true });
</script>

<template>
    <Head title="นำเข้าข้อมูล" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">นำเข้าข้อมูล (จากระบบเก่า)</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>
                <div v-if="importErrors.length" class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-700 ring-1 ring-amber-100">
                    <p class="font-semibold">รายการที่ข้าม/มีปัญหา ({{ importErrors.length }}):</p>
                    <ul class="mt-1 list-inside list-disc max-h-40 overflow-y-auto">
                        <li v-for="(e, i) in importErrors" :key="i">{{ e }}</li>
                    </ul>
                </div>

                <div class="rounded-xl bg-sky-50 px-4 py-3 text-sm text-sky-800 ring-1 ring-sky-100">
                    วิธีใช้: export ข้อมูลจากระบบเก่าเป็น <b>CSV</b> (หรือดาวน์โหลดเทมเพลตด้านล่างไปกรอก) แล้วอัปโหลด — ระบบจะ<b>เพิ่ม/อัปเดต</b>ให้อัตโนมัติ (กันซ้ำด้วย username/ชื่อโรงเรียน)
                </div>

                <!-- บุคลากร -->
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">นำเข้าบุคลากร</h3>
                            <p class="text-xs text-gray-400">ปัจจุบันมี {{ userCount }} คน</p>
                        </div>
                        <a :href="route('import.template', 'users')" class="text-sm font-medium text-indigo-600 hover:underline">ดาวน์โหลดเทมเพลต</a>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">คอลัมน์: <code class="rounded bg-gray-100 px-1">name, username, email, phone, role, unit, group, position</code></p>
                    <p class="mt-1 text-xs text-gray-400">role ที่ใช้ได้: {{ roles.join(', ') }} · บัญชีใหม่รหัสผ่าน = <b>123456</b></p>
                    <div class="mt-3 flex items-center gap-3">
                        <input type="file" accept=".csv" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700" @change="userForm.file = $event.target.files[0]" />
                        <button :disabled="!userForm.file || userForm.processing" class="shrink-0 rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50" @click="submitUsers">นำเข้า</button>
                    </div>
                    <p v-if="userForm.errors.file" class="mt-1 text-xs text-rose-500">{{ userForm.errors.file }}</p>
                </div>

                <!-- โรงเรียน -->
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">นำเข้าโรงเรียน/หน่วยงาน</h3>
                            <p class="text-xs text-gray-400">ปัจจุบันมี {{ unitCount }} โรงเรียน</p>
                        </div>
                        <a :href="route('import.template', 'units')" class="text-sm font-medium text-indigo-600 hover:underline">ดาวน์โหลดเทมเพลต</a>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">คอลัมน์: <code class="rounded bg-gray-100 px-1">name, code, address, phone</code></p>
                    <div class="mt-3 flex items-center gap-3">
                        <input type="file" accept=".csv" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700" @change="unitForm.file = $event.target.files[0]" />
                        <button :disabled="!unitForm.file || unitForm.processing" class="shrink-0 rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50" @click="submitUnits">นำเข้า</button>
                    </div>
                    <p v-if="unitForm.errors.file" class="mt-1 text-xs text-rose-500">{{ unitForm.errors.file }}</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
