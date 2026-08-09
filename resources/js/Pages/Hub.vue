<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { hubCategories, itemHasAccess } from '@/hubCatalog';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ active: { type: String, default: null } });

const page = usePage();
const roles = computed(() => page.props.auth.roles ?? []);

// อ่านค่าป้ายแจ้งเตือน — รองรับ key แบบจุด (logic เดียวกับ sidebar)
const badgeVal = (item) => {
    if (!item.badgeKey) return 0;
    return item.badgeKey.split('.').reduce((o, k) => (o == null ? undefined : o[k]), page.props) ?? 0;
};

// หมวดที่เข้าถึงได้ (มีอย่างน้อย 1 ระบบ) — ใช้ทำแท็บสลับหมวด
const accessibleCats = computed(() =>
    hubCategories
        .map((c) => ({ ...c, items: c.items.filter((i) => itemHasAccess(i, roles.value)) }))
        .filter((c) => c.items.length > 0),
);

// หมวดที่กำลังดู (ตาม slug จาก URL; ถ้าไม่ระบุ/ไม่มีสิทธิ์ ใช้หมวดแรก)
const current = computed(
    () => accessibleCats.value.find((c) => c.slug === props.active) ?? accessibleCats.value[0] ?? null,
);

// สีตามหมวด (เขียนเต็มเพื่อไม่ให้ Tailwind purge)
const accent = {
    indigo: { tab: 'bg-indigo-600 text-white', ring: 'hover:border-indigo-300 hover:bg-indigo-50/60', icon: 'bg-indigo-100 text-indigo-600', bar: 'bg-indigo-500', arrow: 'group-hover:text-indigo-500' },
    emerald: { tab: 'bg-emerald-600 text-white', ring: 'hover:border-emerald-300 hover:bg-emerald-50/60', icon: 'bg-emerald-100 text-emerald-600', bar: 'bg-emerald-500', arrow: 'group-hover:text-emerald-500' },
    amber: { tab: 'bg-amber-500 text-white', ring: 'hover:border-amber-300 hover:bg-amber-50/60', icon: 'bg-amber-100 text-amber-600', bar: 'bg-amber-500', arrow: 'group-hover:text-amber-500' },
    rose: { tab: 'bg-rose-600 text-white', ring: 'hover:border-rose-300 hover:bg-rose-50/60', icon: 'bg-rose-100 text-rose-600', bar: 'bg-rose-500', arrow: 'group-hover:text-rose-500' },
    sky: { tab: 'bg-sky-600 text-white', ring: 'hover:border-sky-300 hover:bg-sky-50/60', icon: 'bg-sky-100 text-sky-600', bar: 'bg-sky-500', arrow: 'group-hover:text-sky-500' },
    violet: { tab: 'bg-violet-600 text-white', ring: 'hover:border-violet-300 hover:bg-violet-50/60', icon: 'bg-violet-100 text-violet-600', bar: 'bg-violet-500', arrow: 'group-hover:text-violet-500' },
    teal: { tab: 'bg-teal-600 text-white', ring: 'hover:border-teal-300 hover:bg-teal-50/60', icon: 'bg-teal-100 text-teal-600', bar: 'bg-teal-500', arrow: 'group-hover:text-teal-500' },
    slate: { tab: 'bg-slate-700 text-white', ring: 'hover:border-slate-300 hover:bg-slate-50/60', icon: 'bg-slate-200 text-slate-600', bar: 'bg-slate-500', arrow: 'group-hover:text-slate-500' },
};
const acc = (name) => accent[name] ?? accent.indigo;
</script>

<template>
    <Head :title="current ? current.heading : 'หมวดระบบงาน'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <span v-if="current" class="h-7 w-1.5 rounded-full" :class="acc(current.accent).bar" />
                <h2 class="text-xl font-semibold text-gray-800">{{ current ? current.heading : 'หมวดระบบงาน' }}</h2>
                <span v-if="current" class="text-sm text-gray-400">{{ current.items.length }} ระบบ</span>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p v-if="!current" class="rounded-xl bg-white p-8 text-center text-gray-400 shadow-sm">
                    ยังไม่มีระบบในสิทธิ์ของคุณ
                </p>

                <!-- การ์ดของหมวดที่เลือก -->
                <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="item in current.items"
                        :key="item.label"
                        :href="route(item.routeName, item.routeParams ?? {})"
                        class="group relative flex items-center gap-4 overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-lg"
                        :class="acc(current.accent).ring"
                    >
                        <!-- แถบสีซ้ายตามหมวด -->
                        <span class="absolute inset-y-0 left-0 w-1.5 origin-bottom scale-y-0 transition-transform duration-200 group-hover:scale-y-100" :class="acc(current.accent).bar" />
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl transition duration-200 group-hover:scale-105" :class="acc(current.accent).icon">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                            </svg>
                        </span>
                        <span class="flex-1 text-base font-semibold leading-snug text-gray-800 group-hover:text-gray-900">{{ item.label }}</span>
                        <!-- ป้ายแจ้งเตือน (โชว์เมื่อมีรายการค้าง) -->
                        <span
                            v-if="badgeVal(item) > 0"
                            class="absolute right-3 top-3 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-rose-500 px-1.5 text-xs font-bold leading-5 text-white shadow"
                        >{{ badgeVal(item) }}</span>
                        <svg class="h-5 w-5 shrink-0 text-gray-300 transition duration-200 group-hover:translate-x-1" :class="acc(current.accent).arrow" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
