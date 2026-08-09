<script setup>
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    survey: { type: Object, required: true },
    questions: { type: Array, default: () => [] },
    already: { type: Boolean, default: false },
    closed: { type: Boolean, default: false },
});

const form = useForm({ answers: {} });
const submit = () => form.post(route('surveys.submit', props.survey.id));
const ratings = [1, 2, 3, 4, 5];
</script>

<template>
    <Head :title="survey.title" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ survey.title }}</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-2xl space-y-5 px-4 sm:px-6 lg:px-8">
                <div v-if="survey.description" class="rounded-2xl bg-white p-5 text-sm text-gray-600 shadow-sm ring-1 ring-gray-100">{{ survey.description }}</div>

                <div v-if="already" class="rounded-xl bg-emerald-50 px-4 py-4 text-center text-sm text-emerald-700 ring-1 ring-emerald-100">คุณได้ตอบแบบสอบถามนี้แล้ว ขอบคุณครับ</div>
                <div v-else-if="closed" class="rounded-xl bg-rose-50 px-4 py-4 text-center text-sm text-rose-600 ring-1 ring-rose-100">แบบสอบถามนี้ปิดรับคำตอบแล้ว</div>

                <form v-else class="space-y-4" @submit.prevent="submit">
                    <div v-for="(q, i) in questions" :key="q.id" class="space-y-3 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                        <p class="text-sm font-medium text-gray-800">{{ i + 1 }}. {{ q.text }} <span v-if="q.required" class="text-rose-500">*</span></p>

                        <!-- rating -->
                        <div v-if="q.type === 'rating'" class="flex gap-2">
                            <label v-for="r in ratings" :key="r" class="flex cursor-pointer flex-col items-center">
                                <input v-model="form.answers[q.id]" type="radio" :value="String(r)" class="peer sr-only" />
                                <span class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-300 text-sm font-semibold text-gray-600 peer-checked:border-indigo-600 peer-checked:bg-indigo-600 peer-checked:text-white">{{ r }}</span>
                            </label>
                            <span class="ml-2 self-center text-xs text-gray-400">1 = น้อยที่สุด, 5 = มากที่สุด</span>
                        </div>

                        <!-- choice -->
                        <div v-else-if="q.type === 'choice'" class="space-y-1.5">
                            <label v-for="(o, oi) in q.options" :key="oi" class="flex items-center gap-2 rounded-md px-2 py-1 text-sm hover:bg-gray-50">
                                <input v-model="form.answers[q.id]" type="radio" :value="o" class="text-indigo-600 focus:ring-indigo-500" /> {{ o }}
                            </label>
                        </div>

                        <!-- text -->
                        <textarea v-else v-model="form.answers[q.id]" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <p v-if="form.errors.answers" class="text-sm text-rose-500">{{ form.errors.answers }}</p>
                    <div class="flex justify-end gap-3">
                        <SecondaryButton type="button" @click="router.visit(route('surveys.index'))">ยกเลิก</SecondaryButton>
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60">ส่งคำตอบ</button>
                    </div>
                </form>

                <SecondaryButton v-if="already || closed" @click="router.visit(route('surveys.index'))">← กลับ</SecondaryButton>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
