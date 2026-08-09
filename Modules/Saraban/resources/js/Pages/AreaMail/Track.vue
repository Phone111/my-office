<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    q: { type: String, default: '' },
    result: { type: Object, default: null },
    notFound: { type: Boolean, default: false },
});

const no = ref(props.q ?? '');
const search = () => router.get(route('saraban.area-mail.track'), { no: no.value.trim() }, { preserveState: true, preserveScroll: true });

const dot = (key) => (key === 'sent' ? 'bg-indigo-500' : key === 'received' ? 'bg-emerald-500' : 'bg-amber-500');
const statusColor = (s) => (s === 'sent' ? 'text-indigo-600' : s === 'received' ? 'text-emerald-600' : 'text-amber-600');
</script>

<template>
    <Head title="ติดตามหนังสือ" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">ติดตามสถานะหนังสือ (เลขติดตาม)</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-2xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- ช่องค้นหา -->
                <form class="flex gap-2 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100" @submit.prevent="search">
                    <input
                        v-model="no"
                        type="text"
                        placeholder="กรอกเลขติดตาม เช่น RB26000123"
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-400 focus:ring-indigo-400"
                        autofocus
                    />
                    <button type="submit" class="shrink-0 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">ค้นหา</button>
                </form>

                <!-- ไม่พบ -->
                <div v-if="notFound" class="rounded-2xl bg-rose-50 p-6 text-center text-sm text-rose-600 ring-1 ring-rose-100">
                    ไม่พบหนังสือที่มีเลขติดตาม "{{ q }}"
                </div>

                <!-- ผลลัพธ์ -->
                <div v-if="result" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-mono text-sm font-bold text-indigo-700">{{ result.tracking_no }}</p>
                            <h3 class="mt-1 text-lg font-bold text-gray-900">{{ result.subject }}</h3>
                            <p class="mt-0.5 text-sm text-gray-500">เลขที่หนังสือ {{ result.number ?? '—' }}</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold" :class="statusColor(result.status)">{{ result.status_label }}</span>
                    </div>

                    <div class="mt-2 text-sm text-gray-600">
                        <span class="font-medium">จาก</span> {{ result.from }} <span class="text-gray-400">→</span> <span class="font-medium">ถึง</span> {{ result.to }}
                    </div>

                    <!-- ไทม์ไลน์ -->
                    <ol class="mt-5 space-y-0 border-l-2 border-gray-100">
                        <li v-for="(ev, i) in result.timeline" :key="i" class="relative pb-5 pl-6 last:pb-0">
                            <span class="absolute -left-[7px] top-1 h-3 w-3 rounded-full ring-2 ring-white" :class="dot(ev.key)" />
                            <p class="text-sm font-semibold text-gray-800">{{ ev.label }}</p>
                            <p class="text-sm text-gray-500">
                                {{ ev.at }} <span v-if="ev.time">เวลา {{ ev.time }} น.</span>
                            </p>
                            <p v-if="ev.who || ev.where" class="text-sm text-gray-400">
                                <span v-if="ev.who">โดย {{ ev.who }}</span><span v-if="ev.who && ev.where"> · </span><span v-if="ev.where">{{ ev.where }}</span>
                            </p>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
