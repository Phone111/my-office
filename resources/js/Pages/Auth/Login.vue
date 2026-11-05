<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const showPassword = ref(false);

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const features = [
    {
        title: 'ระบบงานสารบรรณอิเล็กทรอนิกส์',
        desc: 'รับ-ส่งหนังสือราชการ เสนอแฟ้มอนุมัติ และลงนามด้วยลายเซ็นอิเล็กทรอนิกส์',
        icon: 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
    },
    {
        title: 'บริหารเวลาปฏิบัติงานและงานลา',
        desc: 'บันทึกเวลาเข้า-ออกงาน ยื่นใบลาออนไลน์ และขออนุญาตไปราชการแบบเรียลไทม์',
        icon: 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    },
    {
        title: 'จองห้องประชุมและรถยนต์ส่วนกลาง',
        desc: 'ตรวจสอบตารางคิวว่างและส่งคำขอใช้ทรัพยากรของหน่วยงานได้สะดวกรวดเร็ว',
        icon: 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
    },
    {
        title: 'แดชบอร์ดผู้บริหารและงบประมาณ',
        desc: 'ติดตามสถิติภาพรวม ผลการปฏิบัติงาน (ว.PA) และบริหารจัดการงบประมาณการเงิน',
        icon: 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
    },
];
</script>

<template>
    <Head title="เข้าสู่ระบบ - My-Office" />

    <div class="relative flex min-h-screen w-full overflow-hidden bg-slate-50">
        <!-- แถบซ้าย: แบรนด์และจุดเด่นของระบบ (แสดงบนจอใหญ่) -->
        <div
            class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-gradient-to-br from-indigo-950 via-indigo-900 to-blue-900 p-12 text-white lg:flex xl:w-7/12"
        >
            <!-- ลวดลายพื้นหลัง -->
            <div class="pointer-events-none absolute -left-24 -top-24 h-96 w-96 rounded-full bg-indigo-500/20 blur-3xl" />
            <div class="pointer-events-none absolute bottom-0 right-0 h-[28rem] w-[28rem] rounded-full bg-sky-400/15 blur-3xl" />
            <div
                class="pointer-events-none absolute inset-0 opacity-10"
                style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 28px 28px;"
            />

            <!-- โลโก้ด้านบน -->
            <div class="relative z-10 flex items-center gap-3.5">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 shadow-inner ring-1 ring-white/25 backdrop-blur-md">
                    <svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xl font-bold tracking-tight text-white">My-Office</span>
                        <span class="rounded-full bg-indigo-400/20 px-2.5 py-0.5 text-[11px] font-semibold text-indigo-200 ring-1 ring-indigo-300/30">
                            e-Office Platform
                        </span>
                    </div>
                    <p class="text-xs text-indigo-200/80">Smart Electronic Office Management System</p>
                </div>
            </div>

            <!-- เนื้อหากลาง -->
            <div class="relative z-10 my-auto max-w-xl py-8">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3.5 py-1 text-xs font-medium text-indigo-100 ring-1 ring-white/20 backdrop-blur-sm">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    ระบบสำนักงานอัจฉริยะครบวงจร
                </span>
                <h1 class="mt-4 text-3xl font-extrabold leading-tight tracking-tight text-white xl:text-4xl">
                    ระบบบริหารจัดการสำนักงานอิเล็กทรอนิกส์
                </h1>
                <p class="mt-3 text-base leading-relaxed text-indigo-100/85">
                    ยกระดับการบริหารงานเอกสาร สารบรรณ การลงเวลา และการจัดการทรัพยากรภายในองค์กรให้สะดวก รวดเร็ว ปลอดภัย และตรวจสอบได้ทุกขั้นตอน
                </p>

                <!-- รายการฟีเจอร์ -->
                <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div
                        v-for="item in features"
                        :key="item.title"
                        class="group rounded-2xl bg-white/10 p-4 ring-1 ring-white/15 backdrop-blur-sm transition hover:bg-white/15"
                    >
                        <div class="flex items-start gap-3.5">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-500/30 text-indigo-200 ring-1 ring-white/20">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-white">{{ item.title }}</h3>
                                <p class="mt-1 text-xs leading-relaxed text-indigo-100/75">{{ item.desc }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ส่วนท้าย -->
            <div class="relative z-10 flex items-center justify-between border-t border-white/10 pt-6 text-xs text-indigo-200/70">
                <span>© {{ new Date().getFullYear() }} My-Office (e-Office System)</span>
                <span>ปลอดภัยด้วยมาตรฐานการยืนยันตัวตนและสิทธิ์ตามบทบาท</span>
            </div>
        </div>

        <!-- แถบขวา: แบบฟอร์มล็อกอิน -->
        <div class="flex w-full flex-col justify-center px-6 py-12 sm:px-12 lg:w-1/2 xl:w-5/12">
            <div class="mx-auto w-full max-w-md">
                <!-- โลโก้สำหรับจอมือถือ/แท็บเล็ต -->
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/30">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">My-Office</h2>
                        <p class="text-xs text-slate-500">ระบบบริหารจัดการสำนักงานอิเล็กทรอนิกส์</p>
                    </div>
                </div>

                <!-- กล่องการ์ดฟอร์ม -->
                <div class="rounded-3xl bg-white p-8 shadow-xl shadow-slate-200/70 ring-1 ring-slate-100 sm:p-10">
                    <div class="mb-7">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                            </svg>
                        </div>
                        <h2 class="mt-4 text-2xl font-bold tracking-tight text-slate-900">
                            ยินดีต้อนรับเข้าสู่ระบบ
                        </h2>
                        <p class="mt-1.5 text-sm text-slate-500">
                            กรอกชื่อผู้ใช้งานหรืออีเมลและรหัสผ่านเพื่อเข้าใช้งานระบบ My-Office
                        </p>
                    </div>

                    <div
                        v-if="status"
                        class="mb-6 flex items-center gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200"
                    >
                        <svg class="h-5 w-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span>{{ status }}</span>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- ชื่อผู้ใช้ หรือ อีเมล -->
                        <div>
                            <label for="login" class="block text-sm font-semibold text-slate-700">
                                ชื่อผู้ใช้งาน หรือ อีเมล
                            </label>
                            <div class="relative mt-1.5">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                </div>
                                <input
                                    id="login"
                                    v-model="form.login"
                                    type="text"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="เช่น admin หรือ admin@example.com"
                                    class="block w-full rounded-xl border-slate-200 bg-slate-50/60 py-3 pl-11 pr-4 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-500/20"
                                />
                            </div>
                            <InputError class="mt-2" :message="form.errors.login" />
                        </div>

                        <!-- รหัสผ่าน -->
                        <div>
                            <div class="flex items-center justify-between">
                                <label for="password" class="block text-sm font-semibold text-slate-700">
                                    รหัสผ่าน
                                </label>
                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="text-xs font-medium text-indigo-600 transition hover:text-indigo-700 hover:underline"
                                >
                                    ลืมรหัสผ่านใช่หรือไม่?
                                </Link>
                            </div>
                            <div class="relative mt-1.5">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                </div>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    autocomplete="current-password"
                                    placeholder="กรอกรหัสผ่านของคุณ"
                                    class="block w-full rounded-xl border-slate-200 bg-slate-50/60 py-3 pl-11 pr-11 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-indigo-600 focus:bg-white focus:ring-2 focus:ring-indigo-500/20"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 transition hover:text-slate-600 focus:outline-none"
                                    :title="showPassword ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน'"
                                    @click="showPassword = !showPassword"
                                >
                                    <svg v-if="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </button>
                            </div>
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <!-- จดจำการเข้าสู่ระบบ -->
                        <div class="flex items-center justify-between pt-1">
                            <label class="inline-flex cursor-pointer items-center">
                                <Checkbox name="remember" v-model:checked="form.remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                <span class="ms-2.5 text-sm text-slate-600">จดจำการเข้าสู่ระบบไว้ในเครื่องนี้</span>
                            </label>
                        </div>

                        <!-- ปุ่มเข้าสู่ระบบ -->
                        <div class="pt-2">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-blue-600 px-5 py-3.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:from-indigo-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <svg
                                    v-if="form.processing"
                                    class="h-5 w-5 animate-spin text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ form.processing ? 'กำลังตรวจสอบข้อมูล...' : 'เข้าสู่ระบบ' }}</span>
                                <svg v-if="!form.processing" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ลิงก์กลับหน้าหลัก -->
                <div class="mt-6 text-center">
                    <Link
                        href="/"
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 transition hover:text-indigo-600"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                        กลับสู่หน้าแรกของเว็บไซต์
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
