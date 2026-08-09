<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    registers: { type: Array, default: () => [] },
    years: { type: Array, default: () => [] },
    selected: { type: Object, default: () => ({ register: null, year: null }) },
    candidates: { type: Array, default: null },
    candidateTitle: { type: String, default: null },
    destroyed: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});

const checked = ref([]);
watch(() => props.candidates, () => (checked.value = []));

const allChecked = computed(() => props.candidates && props.candidates.length > 0 && checked.value.length === props.candidates.length);
const checkAll = () => (checked.value = (props.candidates ?? []).map((c) => c.id));
const uncheckAll = () => (checked.value = []);

const processing = ref(false);
const destroySelected = () => {
    if (checked.value.length === 0) return;
    if (!confirm(`ยืนยันทำลายเอกสาร ${checked.value.length} ฉบับ?\nเอกสารจะถูกย้ายเข้าทะเบียนทำลาย (กู้คืนได้)`)) return;
    processing.value = true;
    router.post(route('saraban.destroy.run'), { ids: checked.value }, { preserveScroll: true, onFinish: () => (processing.value = false) });
};

const restore = (id) => {
    if (!confirm('กู้คืนเอกสารนี้กลับสู่ทะเบียน?')) return;
    router.post(route('saraban.destroy.restore', id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="ระบบทำลายหนังสือ" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">ระบบทำลายหนังสือ</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ flash.success }}</div>

                <!-- ตารางทะเบียน × ปี -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 bg-gradient-to-r from-rose-50 to-white px-6 py-4">
                        <p class="font-semibold text-gray-800">คัดเอกสารเพื่อทำลาย</p>
                        <p class="text-xs text-gray-400">เลือกทะเบียนและปี พ.ศ. → เลือกเอกสารที่ครบอายุการเก็บ → ทำลาย</p>
                    </div>
                    <div class="overflow-x-auto p-4">
                        <EmptyState v-if="years.length === 0" title="ยังไม่มีข้อมูลในทะเบียน" />
                        <table v-else class="min-w-full border-separate" style="border-spacing: 0">
                            <thead>
                                <tr>
                                    <th class="sticky left-0 z-10 bg-white px-3 pb-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">ทะเบียน \ ปี พ.ศ.</th>
                                    <th v-for="y in years" :key="y" class="px-2 pb-3 text-center text-sm font-bold text-gray-500">{{ y }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="r in registers" :key="r.key" class="group">
                                    <th class="sticky left-0 z-10 whitespace-nowrap bg-white py-1.5 pr-4 text-left text-sm font-semibold text-gray-700 group-hover:bg-gray-50">{{ r.label }}</th>
                                    <td v-for="y in years" :key="y" class="px-1 py-1.5 text-center align-middle">
                                        <Link
                                            v-if="r.counts[y]"
                                            :href="route('saraban.destroy.index', { register: r.key, year: y })"
                                            class="inline-flex min-w-[3rem] flex-col items-center rounded-lg px-2 py-1.5 text-sm font-semibold transition"
                                            :class="selected.register === r.key && selected.year === y ? 'bg-rose-600 text-white shadow' : 'text-rose-700 ring-1 ring-rose-100 hover:bg-rose-50'"
                                            preserve-scroll
                                        >
                                            <span class="text-base font-bold leading-none">{{ r.counts[y] }}</span>
                                            <span class="text-[10px] font-normal" :class="selected.register === r.key && selected.year === y ? 'text-rose-100' : 'text-gray-400'">ฉบับ</span>
                                        </Link>
                                        <span v-else class="text-gray-200">·</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- รายการเอกสารให้เลือกทำลาย -->
                <div v-if="candidates !== null" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 bg-rose-50/60 px-6 py-3">
                        <span class="font-semibold text-gray-800">{{ candidateTitle }}</span>
                        <span class="rounded-full bg-rose-600 px-2 py-0.5 text-xs font-bold text-white">{{ candidates.length }} ฉบับ</span>
                        <div class="ml-auto flex items-center gap-2">
                            <button type="button" class="rounded-md px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50" @click="checkAll">เลือกทั้งหมด</button>
                            <button type="button" class="rounded-md px-2 py-1 text-xs font-medium text-gray-500 hover:bg-gray-100" @click="uncheckAll">ไม่เลือก</button>
                            <button
                                type="button"
                                :disabled="processing || checked.length === 0"
                                class="rounded-lg bg-rose-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-40"
                                @click="destroySelected"
                            >
                                ทำลายที่เลือก ({{ checked.length }})
                            </button>
                        </div>
                    </div>
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3 text-center">
                                    <input type="checkbox" :checked="allChecked" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500" @change="allChecked ? uncheckAll() : checkAll()" />
                                </th>
                                <th class="px-4 py-3">เลขทะเบียน</th>
                                <th class="px-4 py-3">เรื่อง</th>
                                <th class="px-4 py-3">วันที่</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="candidates.length === 0"><td colspan="4" class="px-6 py-12 text-center text-sm text-gray-400">ไม่มีเอกสารในปีนี้</td></tr>
                            <tr v-for="d in candidates" :key="d.id" class="text-sm text-gray-700 hover:bg-rose-50/40">
                                <td class="px-4 py-3 text-center">
                                    <input v-model="checked" :value="d.id" type="checkbox" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500" />
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 font-mono font-semibold text-indigo-700">{{ d.number }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ d.title }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-500">{{ d.date_thai ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ทะเบียนทำลาย -->
                <div v-if="destroyed.length" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center gap-2 border-b border-gray-100 bg-gray-50 px-6 py-3">
                        <span class="font-semibold text-gray-700">ทะเบียนทำลาย (เอกสารที่ทำลายแล้ว)</span>
                        <span class="rounded-full bg-gray-400 px-2 py-0.5 text-xs font-bold text-white">{{ destroyed.length }}</span>
                    </div>
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3">เลขทะเบียน</th>
                                <th class="px-4 py-3">เรื่อง</th>
                                <th class="px-4 py-3">ทะเบียน</th>
                                <th class="px-4 py-3">วันที่ทำลาย</th>
                                <th class="px-4 py-3 text-right">กู้คืน</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="d in destroyed" :key="d.id" class="text-sm text-gray-500">
                                <td class="whitespace-nowrap px-4 py-3 font-mono">{{ d.number }}</td>
                                <td class="px-4 py-3 text-gray-700 line-through">{{ d.title }}</td>
                                <td class="px-4 py-3">{{ d.category_label }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ d.destroyed_thai }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" class="font-medium text-emerald-600 hover:text-emerald-800" @click="restore(d.id)">↩ กู้คืน</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
