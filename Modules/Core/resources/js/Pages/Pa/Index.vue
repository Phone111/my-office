<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    mine: { type: Array, default: () => [] },
    toReview: { type: Array, default: () => [] },
    currentFiscalYear: { type: Number, default: 0 },
    isApprover: { type: Boolean, default: false },
});
</script>

<template>
    <Head title="ว.PA ข้อตกลงพัฒนางาน" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">ว.PA — ข้อตกลงในการพัฒนางาน</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- รอเห็นชอบ/ประเมิน (ผู้บริหาร) -->
                <div v-if="isApprover && toReview.length" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-amber-100">
                    <h3 class="mb-3 text-base font-bold text-gray-800">รอเห็นชอบ / ประเมิน ({{ toReview.length }})</h3>
                    <ul class="divide-y divide-gray-50">
                        <li v-for="a in toReview" :key="a.id" class="flex items-center justify-between py-2.5">
                            <div>
                                <span class="font-medium text-gray-800">{{ a.owner }}</span>
                                <span class="ml-2 text-xs text-gray-400">ปีงบ {{ a.fiscal_year }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <StatusBadge :status="a.status" :label="a.status_label" />
                                <Link :href="route('pa.show', a.id)" class="text-sm font-medium text-indigo-600 hover:underline">ดู ›</Link>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- ว.PA ของฉัน -->
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-base font-bold text-gray-800">ว.PA ของฉัน</h3>
                        <Link :href="route('pa.edit', { year: currentFiscalYear })" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            จัดทำ ว.PA ปีงบ {{ currentFiscalYear }}
                        </Link>
                    </div>
                    <p v-if="!mine.length" class="py-6 text-center text-sm text-gray-400">ยังไม่มีข้อตกลง — กดปุ่มด้านบนเพื่อจัดทำ</p>
                    <ul v-else class="divide-y divide-gray-50">
                        <li v-for="a in mine" :key="a.id" class="flex items-center justify-between py-2.5">
                            <div>
                                <span class="font-medium text-gray-800">ปีงบประมาณ {{ a.fiscal_year }}</span>
                                <span v-if="a.challenge_issue" class="ml-2 truncate text-xs text-gray-400">· {{ a.challenge_issue }}</span>
                                <span v-if="a.score != null" class="ml-2 text-xs font-semibold" :class="a.score >= 70 ? 'text-emerald-600' : 'text-rose-500'">{{ a.score }} คะแนน</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <StatusBadge :status="a.status" :label="a.status_label" />
                                <Link v-if="a.status === 'draft'" :href="route('pa.edit', { year: a.fiscal_year })" class="text-sm font-medium text-indigo-600 hover:underline">แก้ไข ›</Link>
                                <Link v-else :href="route('pa.show', a.id)" class="text-sm font-medium text-indigo-600 hover:underline">ดู ›</Link>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
