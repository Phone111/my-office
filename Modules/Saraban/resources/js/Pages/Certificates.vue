<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import EmptyState from '@/Components/EmptyState.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { thaiDate } from '@/utils/format';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    certificates: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.certificates;
    return props.certificates.filter(
        (c) =>
            (c.title ?? '').toLowerCase().includes(q) ||
            (c.recipient_name ?? '').toLowerCase().includes(q) ||
            (c.certificate_number ?? '').toLowerCase().includes(q),
    );
});

const detail = ref(null);

const today = new Date().toISOString().slice(0, 10);

const showForm = ref(false);
const form = useForm({ title: '', recipient_name: '', issued_date: today, note: '' });

const openForm = () => {
    form.reset();
    form.clearErrors();
    form.issued_date = today;
    showForm.value = true;
};

const submit = () =>
    form.post(route('saraban.certificates.store'), {
        onSuccess: () => (showForm.value = false),
    });
</script>

<template>
    <Head title="ทะเบียนเลขเกียรติบัตร" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    ทะเบียนเลขเกียรติบัตร
                </h2>
                <PrimaryButton @click="openForm">+ ออกเลขเกียรติบัตร</PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div
                    v-if="flash.success"
                    class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
                >
                    {{ flash.success }}
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <div class="border-b border-gray-100 px-6 py-3">
                        <input v-model="search" type="text" placeholder="ค้นหาเลขที่ / กิจกรรม / ผู้รับ" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-80" />
                    </div>
                    <EmptyState v-if="filtered.length === 0" title="ไม่พบรายการในทะเบียน" />

                    <table v-else class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3">เลขที่</th>
                                <th class="px-6 py-3">ชื่อกิจกรรม/หลักสูตร</th>
                                <th class="px-6 py-3">ผู้รับ</th>
                                <th class="px-6 py-3">วันที่ออก</th>
                                <th class="px-6 py-3">ผู้ออก</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="c in filtered"
                                :key="c.id"
                                class="cursor-pointer text-sm text-gray-700 hover:bg-indigo-50/50"
                                @click="detail = c"
                            >
                                <td class="px-6 py-4 font-mono text-xs font-semibold text-indigo-600">
                                    {{ c.certificate_number }}
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900">{{ c.title }}</td>
                                <td class="px-6 py-4">{{ c.recipient_name }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ thaiDate(c.issued_date) }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ c.issuer }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal ออกเลขเกียรติบัตร -->
        <Modal :show="showForm" @close="showForm = false">
            <form class="space-y-5 p-6" @submit.prevent="submit">
                <h2 class="text-lg font-semibold text-gray-900">ออกเลขเกียรติบัตร</h2>

                <div>
                    <InputLabel for="title" value="ชื่อกิจกรรม/หลักสูตร" />
                    <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" autofocus />
                    <InputError :message="form.errors.title" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="recipient_name" value="ชื่อผู้รับเกียรติบัตร" />
                    <TextInput id="recipient_name" v-model="form.recipient_name" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.recipient_name" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="issued_date" value="วันที่ออก" />
                    <TextInput id="issued_date" v-model="form.issued_date" type="date" class="mt-1 block w-full" />
                    <InputError :message="form.errors.issued_date" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="note" value="หมายเหตุ (ไม่บังคับ)" />
                    <textarea
                        id="note"
                        v-model="form.note"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError :message="form.errors.note" class="mt-2" />
                </div>

                <p class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500">
                    ระบบจะออกเลขที่เกียรติบัตรให้อัตโนมัติ (รันเลขใหม่ทุกปี พ.ศ.)
                </p>

                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton type="button" @click="showForm = false">ยกเลิก</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">ออกเลข</PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- รายละเอียดเกียรติบัตร -->
        <Modal :show="detail !== null" @close="detail = null">
            <div v-if="detail" class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">{{ detail.title }}</h2>
                <span class="mt-1 inline-flex rounded-full bg-indigo-50 px-2.5 py-0.5 font-mono text-xs font-semibold text-indigo-700">{{ detail.certificate_number }}</span>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex gap-3"><dt class="w-28 shrink-0 text-gray-400">ผู้รับ</dt><dd class="text-gray-700">{{ detail.recipient_name }}</dd></div>
                    <div class="flex gap-3"><dt class="w-28 shrink-0 text-gray-400">วันที่ออก</dt><dd class="text-gray-700">{{ thaiDate(detail.issued_date) }}</dd></div>
                    <div class="flex gap-3"><dt class="w-28 shrink-0 text-gray-400">ผู้ออก</dt><dd class="text-gray-700">{{ detail.issuer }}</dd></div>
                    <div v-if="detail.note" class="flex gap-3"><dt class="w-28 shrink-0 text-gray-400">หมายเหตุ</dt><dd class="whitespace-pre-line text-gray-700">{{ detail.note }}</dd></div>
                </dl>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="detail = null">ปิด</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
