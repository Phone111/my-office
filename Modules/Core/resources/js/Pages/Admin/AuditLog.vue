<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    logs: { type: Array, default: () => [] },
    actions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    total: { type: Number, default: 0 },
});

const action = ref(props.filters.action ?? '');
const q = ref(props.filters.q ?? '');
const date = ref(props.filters.date ?? '');
const apply = () => router.get(route('admin.audit-log.index'), {
    action: action.value || undefined,
    q: q.value || undefined,
    date: date.value || undefined,
}, { preserveScroll: true });
const reset = () => { action.value = ''; q.value = ''; date.value = ''; apply(); };

const tone = (a) =>
    ({
        created: 'bg-emerald-100 text-emerald-700',
        updated: 'bg-amber-100 text-amber-700',
        deleted: 'bg-rose-100 text-rose-700',
        destroy: 'bg-rose-100 text-rose-700',
        restore: 'bg-sky-100 text-sky-700',
        role: 'bg-violet-100 text-violet-700',
    })[a] ?? 'bg-gray-100 text-gray-600';
</script>

<template>
    <Head title="บันทึกการใช้งาน" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-xl font-semibold text-gray-800">บันทึกการใช้งาน (Audit Log)</h2>
                <span class="text-sm text-gray-400">ทั้งหมด {{ total }} รายการ</span>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
                <!-- ตัวกรอง -->
                <div class="flex flex-wrap items-end gap-2 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100">
                    <div>
                        <label class="block text-xs text-gray-500">การกระทำ</label>
                        <select v-model="action" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="apply">
                            <option value="">ทั้งหมด</option>
                            <option v-for="a in actions" :key="a.key" :value="a.key">{{ a.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">วันที่</label>
                        <input v-model="date" type="date" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="apply" />
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500">ค้นหา (ผู้ทำ / รายละเอียด)</label>
                        <input v-model="q" type="text" placeholder="พิมพ์แล้ว Enter" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @keyup.enter="apply" />
                    </div>
                    <button type="button" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" @click="apply">ค้นหา</button>
                    <button type="button" class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50" @click="reset">ล้าง</button>
                </div>

                <!-- ตาราง -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-medium text-gray-500">
                                    <th class="px-4 py-2.5">เวลา</th>
                                    <th class="px-4 py-2.5">ผู้ทำ</th>
                                    <th class="px-4 py-2.5 text-center">การกระทำ</th>
                                    <th class="px-4 py-2.5">ชนิด</th>
                                    <th class="px-4 py-2.5">รายละเอียด</th>
                                    <th class="px-4 py-2.5">IP</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-if="!logs.length"><td colspan="6" class="px-6 py-12"><EmptyState title="ไม่มีบันทึก" /></td></tr>
                                <tr v-for="l in logs" :key="l.id" class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-4 py-2 text-gray-500">{{ l.at }}</td>
                                    <td class="px-4 py-2 font-medium text-gray-800">{{ l.user }}</td>
                                    <td class="px-4 py-2 text-center"><span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold" :class="tone(l.action)">{{ l.action_label }}</span></td>
                                    <td class="px-4 py-2 text-gray-500">{{ l.type ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ l.description ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-2 text-xs text-gray-400">{{ l.ip ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <p class="text-center text-xs text-gray-400">แสดงสูงสุด 500 รายการล่าสุด</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
