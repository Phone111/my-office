<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    today: String,
    todayAttendance: { type: Object, default: null },
    history: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

// นาฬิกาเรียลไทม์
const now = ref(new Date());
let timer = null;
onMounted(() => {
    timer = setInterval(() => (now.value = new Date()), 1000);
});
onUnmounted(() => clearInterval(timer));

const currentTime = computed(() =>
    now.value.toLocaleTimeString('th-TH', { hour12: false }),
);

const todayLabel = computed(() =>
    new Date(props.today).toLocaleDateString('th-TH', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }),
);

const hasCheckedIn = computed(() => props.todayAttendance !== null);
const hasCheckedOut = computed(() => !!props.todayAttendance?.check_out_time);

const form = useForm({});
const submit = () => form.post(route('attendance.store'), { preserveScroll: true });

const checkoutForm = useForm({});
const submitCheckout = () => checkoutForm.post(route('attendance.check-out'), { preserveScroll: true });

// ป้ายสถานะ
const statusMeta = (status) =>
    ({
        present: { label: 'ปกติ', classes: 'bg-green-100 text-green-700' },
        late: { label: 'มาสาย', classes: 'bg-amber-100 text-amber-700' },
    })[status] ?? { label: status, classes: 'bg-gray-100 text-gray-600' };

const formatDate = (date) =>
    new Date(date).toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });

const formatTime = (time) => (time ? time.slice(0, 5) : '-');
</script>

<template>
    <Head title="ลงเวลาเข้า-ออกงาน" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                ลงเวลาเข้า-ออกงาน
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- ข้อความแจ้งเตือน -->
                <div
                    v-if="flash.success"
                    class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
                >
                    {{ flash.success }}
                </div>
                <div
                    v-if="flash.error"
                    class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                >
                    {{ flash.error }}
                </div>

                <!-- การ์ดลงเวลา -->
                <div
                    class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100"
                >
                    <div
                        class="bg-gradient-to-br from-indigo-600 to-blue-500 px-8 py-10 text-center text-white"
                    >
                        <p class="text-sm font-medium text-indigo-100">
                            {{ todayLabel }}
                        </p>
                        <p
                            class="mt-2 font-mono text-5xl font-bold tracking-tight tabular-nums"
                        >
                            {{ currentTime }}
                        </p>
                    </div>

                    <div class="px-8 py-8">
                        <!-- ยังไม่ได้ลงเวลา -->
                        <div v-if="!hasCheckedIn" class="text-center">
                            <p class="mb-6 text-gray-600">
                                คุณยังไม่ได้ลงชื่อเข้างานสำหรับวันนี้
                            </p>
                            <button
                                type="button"
                                :disabled="form.processing"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-4 text-lg font-semibold text-white shadow-lg shadow-indigo-200 transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                                @click="submit"
                            >
                                <svg
                                    class="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                    />
                                </svg>
                                {{ form.processing ? 'กำลังบันทึก...' : 'ลงชื่อเข้างาน' }}
                            </button>
                        </div>

                        <!-- ลงเวลาแล้ว -->
                        <div v-else class="text-center">
                            <div
                                class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100"
                            >
                                <svg
                                    class="h-9 w-9 text-green-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m4.5 12.75 6 6 9-13.5"
                                    />
                                </svg>
                            </div>
                            <p class="mt-4 text-lg font-semibold text-gray-800">
                                ลงชื่อเข้างานเรียบร้อยแล้ว
                            </p>
                            <div class="mt-3 flex justify-center gap-8">
                                <div>
                                    <p class="text-xs text-gray-400">เวลาเข้างาน</p>
                                    <p class="font-semibold text-gray-700">{{ formatTime(todayAttendance.check_in_time) }} น.</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">เวลาเลิกงาน</p>
                                    <p class="font-semibold" :class="hasCheckedOut ? 'text-gray-700' : 'text-gray-300'">
                                        {{ hasCheckedOut ? formatTime(todayAttendance.check_out_time) + ' น.' : 'ยังไม่ลง' }}
                                    </p>
                                </div>
                            </div>
                            <span
                                class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                :class="statusMeta(todayAttendance.status).classes"
                            >
                                {{ statusMeta(todayAttendance.status).label }}
                            </span>

                            <!-- ปุ่มลงเวลาเลิกงาน -->
                            <div class="mt-6">
                                <button
                                    v-if="!hasCheckedOut"
                                    type="button"
                                    :disabled="checkoutForm.processing"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-rose-600 px-6 py-4 text-lg font-semibold text-white shadow-lg shadow-rose-200 transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                                    @click="submitCheckout"
                                >
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                    </svg>
                                    {{ checkoutForm.processing ? 'กำลังบันทึก...' : 'ลงเวลาเลิกงาน' }}
                                </button>
                                <p v-else class="text-sm font-medium text-green-600">ลงเวลาครบแล้วสำหรับวันนี้</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ประวัติการลงเวลาล่าสุด -->
                <div
                    class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100"
                >
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h3 class="font-semibold text-gray-800">
                            ประวัติการลงเวลาล่าสุด
                        </h3>
                    </div>

                    <EmptyState v-if="history.length === 0" title="ยังไม่มีประวัติการลงเวลา" />

                    <table v-else class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3">วันที่</th>
                                <th class="px-6 py-3">เวลาเข้างาน</th>
                                <th class="px-6 py-3">เวลาเลิกงาน</th>
                                <th class="px-6 py-3">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="item in history" :key="item.id" class="text-sm text-gray-700">
                                <td class="px-6 py-3">{{ formatDate(item.date) }}</td>
                                <td class="px-6 py-3 font-mono tabular-nums">
                                    {{ formatTime(item.check_in_time) }} น.
                                </td>
                                <td class="px-6 py-3 font-mono tabular-nums">
                                    {{ item.check_out_time ? formatTime(item.check_out_time) + ' น.' : '-' }}
                                </td>
                                <td class="px-6 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                        :class="statusMeta(item.status).classes"
                                    >
                                        {{ statusMeta(item.status).label }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
