<script setup>
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    counters: { type: Array, default: () => [] }, // [{id,book,book_label,year,last_no}]
    books: { type: Object, default: () => ({}) }, // {key:label}
    prefixes: { type: Object, default: () => ({}) },
});

const flash = computed(() => usePage().props.flash ?? {});
const bookList = computed(() => Object.entries(props.books).map(([key, label]) => ({ key, label })));

const thisYear = new Date().getFullYear() + 543;
const form = useForm({ book: '', year: thisYear, last_no: 0 });
const create = () => form.post(route('saraban.settings.numbers.store'), { preserveScroll: true, onSuccess: () => form.reset('last_no') });

const edits = ref({});
const saveRow = (c) => {
    const val = edits.value[c.id] ?? c.last_no;
    router.put(route('saraban.settings.numbers.update', c.id), { last_no: val }, { preserveScroll: true });
};
const removeRow = (c) => {
    if (confirm(`ลบเล่มทะเบียน "${c.book_label}" ปี ${c.year}?`)) {
        router.delete(route('saraban.settings.numbers.destroy', c.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="ตั้งค่าเลขทะเบียน" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">ตั้งค่าเลขทะเบียนเอกสาร</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ flash.success }}</div>

                <!-- สร้าง/ตั้งเลขเริ่มต้น -->
                <form class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100" @submit.prevent="create">
                    <p class="mb-3 font-semibold text-gray-800">ตั้งเลขเริ่มต้น / สร้างเล่มทะเบียน</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                        <div class="sm:col-span-2">
                            <label class="text-sm text-gray-600">เล่มทะเบียน</label>
                            <select v-model="form.book" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">— เลือกเล่ม —</option>
                                <option v-for="b in bookList" :key="b.key" :value="b.key">{{ b.label }}<span v-if="prefixes[b.key]"> ({{ prefixes[b.key] }})</span></option>
                            </select>
                            <InputError :message="form.errors.book" class="mt-1" />
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">ปี (พ.ศ.)</label>
                            <input v-model.number="form.year" type="number" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <InputError :message="form.errors.year" class="mt-1" />
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">เลขล่าสุด</label>
                            <input v-model.number="form.last_no" type="number" min="0" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <PrimaryButton :disabled="form.processing || !form.book">บันทึก</PrimaryButton>
                    </div>
                </form>

                <!-- รายการเล่มทะเบียน -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3">เล่มทะเบียน</th>
                                <th class="px-6 py-3 text-center">ปี พ.ศ.</th>
                                <th class="px-6 py-3 text-center">เลขล่าสุด</th>
                                <th class="px-6 py-3 text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="counters.length === 0"><td colspan="4" class="px-6 py-10 text-center text-sm text-gray-400">ยังไม่มีเล่มทะเบียน</td></tr>
                            <tr v-for="c in counters" :key="c.id" class="text-sm text-gray-700 hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium text-gray-900">{{ c.book_label }}</td>
                                <td class="px-6 py-3 text-center">{{ c.year }}</td>
                                <td class="px-6 py-3 text-center">
                                    <input :value="edits[c.id] ?? c.last_no" type="number" min="0" class="w-24 rounded-md border-gray-300 text-center text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @input="edits[c.id] = $event.target.valueAsNumber" />
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <button class="mr-3 font-medium text-indigo-600 hover:text-indigo-800" @click="saveRow(c)">บันทึก</button>
                                    <button class="font-medium text-rose-600 hover:text-rose-800" @click="removeRow(c)">ลบ</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
