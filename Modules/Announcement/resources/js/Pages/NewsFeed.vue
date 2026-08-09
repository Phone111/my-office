<script setup>
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    news: { type: Array, default: () => [] },
    canWrite: { type: Boolean, default: false },
    categories: { type: Array, default: () => [] },
});

const flash = computed(() => usePage().props.flash ?? {});

const search = ref('');
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.news;
    return props.news.filter(
        (n) => (n.title ?? '').toLowerCase().includes(q) || (n.content ?? '').toLowerCase().includes(q),
    );
});

const detail = ref(null);

// เขียนข่าว (เฉพาะผู้มีสิทธิ์)
const showForm = ref(false);
const form = useForm({ title: '', category: 'ข่าวประชาสัมพันธ์', content: '', allow_comments: false, image: null, file: null });
const openForm = () => {
    form.reset();
    form.clearErrors();
    showForm.value = true;
};
const submit = () =>
    form.post(route('quick-news.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => (showForm.value = false),
    });

const remove = (n) => {
    if (confirm(`ลบข่าว "${n.title}" ?`)) {
        router.delete(route('quick-news.destroy', n.id), { preserveScroll: true, onSuccess: () => (detail.value = null) });
    }
};
</script>

<template>
    <Head title="ข่าวสาร" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">ข่าวสาร / ประกาศ</h2>
                <div class="flex items-center gap-2">
                    <TextInput v-model="search" type="text" placeholder="ค้นหาข่าว" class="w-56" />
                    <PrimaryButton v-if="canWrite" @click="openForm">+ เพิ่มข่าว</PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ flash.success }}
                </div>

                <div v-if="filtered.length === 0" class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <EmptyState title="ยังไม่มีข่าวสาร" />
                </div>

                <div v-else class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <button
                        v-for="n in filtered"
                        :key="n.id"
                        class="flex gap-4 rounded-2xl bg-white p-5 text-left shadow-sm ring-1 ring-gray-100 transition hover:shadow-md hover:ring-indigo-200"
                        @click="detail = n"
                    >
                        <!-- รูปไอคอนข่าว -->
                        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-gray-100 ring-1 ring-gray-200">
                            <img v-if="n.image_url" :src="n.image_url" alt="" class="h-full w-full object-cover" />
                            <div v-else class="flex h-full w-full items-center justify-center text-gray-300">
                                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M18.75 4.5h.008v.008h-.008V4.5ZM2.25 19.5V6.75a2.25 2.25 0 0 1 2.25-2.25h15a2.25 2.25 0 0 1 2.25 2.25v12.75A2.25 2.25 0 0 1 19.5 21.75h-15A2.25 2.25 0 0 1 2.25 19.5Z" /></svg>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs text-rose-600">ผู้เขียน : {{ n.author ?? '—' }} · {{ n.created_at }}</p>
                            <h3 class="mt-1 font-semibold text-gray-900">
                                <span v-if="n.is_urgent" class="mr-1 inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-700">ด่วน</span>
                                {{ n.title }}
                            </h3>
                            <p v-if="n.excerpt" class="mt-1 line-clamp-2 text-sm text-gray-500">{{ n.excerpt }}</p>
                            <span class="mt-1 inline-block text-sm font-medium text-indigo-600">อ่านต่อ…</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <!-- รายละเอียดข่าว -->
        <Modal :show="detail !== null" max-width="2xl" @close="detail = null">
            <div v-if="detail" class="p-6">
                <h2 class="text-xl font-bold text-gray-900">
                    <span v-if="detail.is_urgent" class="mr-2 inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-sm font-bold text-rose-700">ด่วน</span>
                    {{ detail.title }}
                </h2>
                <p class="mt-1 text-sm text-gray-400">โดย {{ detail.author ?? '—' }} · {{ detail.created_at }}</p>
                <img v-if="detail.image_url" :src="detail.image_url" alt="" class="mt-4 max-h-64 rounded-lg object-contain" />
                <div v-if="detail.content" class="mt-4 whitespace-pre-line leading-relaxed text-gray-700">{{ detail.content }}</div>
                <a v-if="detail.file_url" :href="detail.file_url" target="_blank" class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-800">ดาวน์โหลดไฟล์แนบ</a>
                <div class="mt-6 flex justify-end gap-3">
                    <button v-if="detail.can_delete" class="text-sm font-medium text-red-600 hover:text-red-800" @click="remove(detail)">ลบข่าว</button>
                    <SecondaryButton @click="detail = null">ปิด</SecondaryButton>
                </div>
            </div>
        </Modal>

        <!-- Modal เพิ่มข่าว -->
        <Modal :show="showForm" max-width="2xl" @close="showForm = false">
            <form class="space-y-5 p-6" @submit.prevent="submit">
                <h2 class="text-lg font-semibold text-gray-900">เพิ่มข่าว</h2>
                <div>
                    <InputLabel for="title" value="หัวข้อ" />
                    <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" autofocus />
                    <InputError :message="form.errors.title" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="category" value="หมวดหมู่" />
                    <select id="category" v-model="form.category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                    </select>
                    <InputError :message="form.errors.category" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="image" value="รูปไอคอนข่าวสาร" />
                    <input id="image" type="file" accept="image/jpeg,image/png" class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100" @input="form.image = $event.target.files[0]" />
                    <p class="mt-1 text-xs text-gray-400">ไฟล์ .jpg / .png (ระบบย่อขนาดให้อัตโนมัติเมื่อแสดง)</p>
                    <InputError :message="form.errors.image" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="content" value="เนื้อหา" />
                    <textarea id="content" v-model="form.content" rows="7" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <InputError :message="form.errors.content" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="file" value="ไฟล์แนบ (ไม่บังคับ)" />
                    <input id="file" type="file" class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100" @input="form.file = $event.target.files[0]" />
                    <InputError :message="form.errors.file" class="mt-2" />
                </div>
                <label class="flex items-center gap-2">
                    <input v-model="form.allow_comments" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                    <span class="text-sm text-gray-600">อนุญาตให้มีการแสดงความคิดเห็น</span>
                </label>
                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton type="button" @click="showForm = false">เคลียร์</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">ส่งข้อความ</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
