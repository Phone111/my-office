<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    activities: { type: Array, default: () => [] },
    groupName: { type: String, default: null },
    canManage: { type: Boolean, default: false },
});

const flash = computed(() => usePage().props.flash?.success);

// ===== ปฏิทินรายเดือน =====
const pad = (n) => String(n).padStart(2, '0');
const weekdays = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];
const current = ref(new Date());
const monthLabel = computed(() => current.value.toLocaleDateString('th-TH', { month: 'long', year: 'numeric' }));
const prevMonth = () => (current.value = new Date(current.value.getFullYear(), current.value.getMonth() - 1, 1));
const nextMonth = () => (current.value = new Date(current.value.getFullYear(), current.value.getMonth() + 1, 1));

const itemsOn = (iso) => props.activities.filter((a) => a.date === iso);
const cells = computed(() => {
    const y = current.value.getFullYear();
    const m = current.value.getMonth();
    const startOffset = new Date(y, m, 1).getDay();
    const daysInMonth = new Date(y, m + 1, 0).getDate();
    const list = [];
    for (let i = 0; i < startOffset; i++) list.push({ blank: true });
    for (let d = 1; d <= daysInMonth; d++) {
        const iso = `${y}-${pad(m + 1)}-${pad(d)}`;
        list.push({ day: d, iso, items: itemsOn(iso) });
    }
    while (list.length % 7 !== 0) list.push({ blank: true });
    return list;
});

// ===== ฟอร์มเพิ่มกิจกรรม =====
const showForm = ref(false);
const form = useForm({ activity_date: new Date().toISOString().slice(0, 10), time_text: '', days: 1, title: '', detail: '' });
const openAdd = (iso = null) => {
    form.reset();
    form.clearErrors();
    form.activity_date = iso ?? new Date().toISOString().slice(0, 10);
    showForm.value = true;
};
const submit = () => form.post(route('saraban.group.activities.store'), { preserveScroll: true, onSuccess: () => (showForm.value = false) });
const remove = (a) => {
    if (confirm(`ลบกิจกรรม "${a.title}" ?`)) router.delete(route('saraban.group.activities.destroy', a.id), { preserveScroll: true });
};

const detail = ref(null);
</script>

<template>
    <Head title="ปฏิทินกิจกรรมของกลุ่ม" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">ปฏิทินกิจกรรมของกลุ่ม</h2>
                    <p class="text-xs text-gray-400">{{ groupName ?? 'ทุกกลุ่ม' }}</p>
                </div>
                <button v-if="canManage" type="button" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500" @click="openAdd()">+ เพิ่มกิจกรรม</button>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">
                <div v-if="flash" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ flash }}</div>

                <!-- ปฏิทิน -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                        <button class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" @click="prevMonth"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg></button>
                        <span class="text-sm font-bold text-gray-800">{{ monthLabel }}</span>
                        <button class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" @click="nextMonth"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg></button>
                    </div>
                    <div class="grid grid-cols-7 border-b border-gray-100 bg-gray-50 text-center text-xs font-semibold text-gray-500">
                        <div v-for="(w, i) in weekdays" :key="w" class="py-1.5" :class="i === 0 ? 'text-rose-500' : i === 6 ? 'text-sky-500' : ''">{{ w }}</div>
                    </div>
                    <div class="grid grid-cols-7">
                        <div v-for="(c, i) in cells" :key="i" class="min-h-[84px] border-b border-r border-gray-50 p-1" :class="[c.blank ? 'bg-gray-50/40' : 'cursor-pointer hover:bg-indigo-50/40']" @click="!c.blank && canManage ? openAdd(c.iso) : null">
                            <template v-if="!c.blank">
                                <div class="mb-0.5 text-right text-[11px] font-semibold text-gray-500">{{ c.day }}</div>
                                <div class="space-y-0.5">
                                    <button v-for="a in c.items.slice(0, 3)" :key="a.id" class="block w-full truncate rounded bg-violet-100 px-1 py-0.5 text-left text-[10px] font-medium text-violet-700 hover:bg-violet-200" @click.stop="detail = a">{{ a.title }}</button>
                                    <p v-if="c.items.length > 3" class="px-1 text-[9px] text-gray-400">+{{ c.items.length - 3 }}</p>
                                </div>
                            </template>
                        </div>
                    </div>
                    <p v-if="canManage" class="px-4 py-2 text-xs text-gray-400">คลิกวันเพื่อเพิ่มกิจกรรม</p>
                </div>

                <!-- รายการกิจกรรม -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3 text-sm font-semibold text-gray-700">รายการกิจกรรม ({{ activities.length }})</div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3">วันที่</th>
                                <th class="px-4 py-3">เวลา</th>
                                <th class="px-4 py-3">เรื่อง</th>
                                <th class="px-4 py-3">รายละเอียด</th>
                                <th class="px-4 py-3 text-center">ลบ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="activities.length === 0"><td colspan="5" class="px-6 py-10 text-center text-sm text-gray-400">ยังไม่มีกิจกรรม</td></tr>
                            <tr v-for="a in activities" :key="a.id" class="text-gray-700 hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-600">{{ a.date_thai }}<span v-if="a.days > 1" class="text-xs text-gray-400"> ({{ a.days }} วัน)</span></td>
                                <td class="px-4 py-3 text-gray-500">{{ a.time_text ?? '—' }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ a.title }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ a.detail ?? '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <button v-if="a.can_delete" class="text-rose-600 hover:text-rose-800" @click="remove(a)">ลบ</button>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ฟอร์มเพิ่ม -->
        <Modal :show="showForm" @close="showForm = false">
            <form class="space-y-4 p-6" @submit.prevent="submit">
                <h2 class="text-lg font-semibold text-gray-900">เพิ่มกิจกรรมของกลุ่ม</h2>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">วันที่</label>
                        <input v-model="form.activity_date" type="date" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="form.errors.activity_date" class="mt-1 text-xs text-rose-500">{{ form.errors.activity_date }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">จำนวนวัน</label>
                        <input v-model.number="form.days" type="number" min="1" max="60" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">เวลา</label>
                    <input v-model="form.time_text" type="text" placeholder="เช่น 09:00 - 16:00 น." class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">เรื่อง</label>
                    <input v-model="form.title" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-rose-500">{{ form.errors.title }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">รายละเอียด</label>
                    <textarea v-model="form.detail" rows="3" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton type="button" @click="showForm = false">ยกเลิก</SecondaryButton>
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60">เพิ่มในปฏิทิน</button>
                </div>
            </form>
        </Modal>

        <!-- รายละเอียดกิจกรรม -->
        <Modal :show="detail !== null" @close="detail = null">
            <div v-if="detail" class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">{{ detail.title }}</h2>
                <p class="mt-1 text-sm text-gray-400">{{ detail.date_thai }}<span v-if="detail.days > 1"> · {{ detail.days }} วัน</span><span v-if="detail.time_text"> · {{ detail.time_text }}</span></p>
                <p v-if="detail.detail" class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700">{{ detail.detail }}</p>
                <p class="mt-3 text-xs text-gray-400">บันทึกโดย {{ detail.creator ?? '—' }}<span v-if="detail.group"> · {{ detail.group }}</span></p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="detail = null">ปิด</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
