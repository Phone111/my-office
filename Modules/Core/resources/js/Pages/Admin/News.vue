<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    news: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});
const currentUserName = computed(() => page.props.auth?.user?.name ?? '');
const fileUrl = (path) => `/storage/${path}`;

/* ---------- เลือกหลายรายการ ---------- */
const selected = ref([]);
const allChecked = computed(
    () => props.news.data.length > 0 && selected.value.length === props.news.data.length,
);
const toggleAll = (e) => {
    selected.value = e.target.checked ? props.news.data.map((n) => n.id) : [];
};
const bulkDelete = () => {
    if (!selected.value.length) return;
    if (confirm(`ลบข่าว ${selected.value.length} รายการ?`)) {
        router.post(
            route('admin.news.bulk-destroy'),
            { ids: selected.value },
            { preserveScroll: true, onSuccess: () => (selected.value = []) },
        );
    }
};

/* ---------- เพิ่ม/แก้ไขข่าว ---------- */
const showForm = ref(false);
const editingId = ref(null);
const form = useForm({
    title: '',
    excerpt: '',
    category: props.categories[0] ?? '',
    content: '',
    author: '',
    allow_comments: false,
    file: null,
});

const openCreate = () => {
    form.reset();
    form.clearErrors();
    form.category = props.categories[0] ?? '';
    form.author = currentUserName.value; // ผู้บันทึก = ผู้ใช้ปัจจุบัน
    editingId.value = null;
    showForm.value = true;
};
const openEdit = (n) => {
    form.reset();
    form.clearErrors();
    editingId.value = n.id;
    form.title = n.title;
    form.excerpt = n.excerpt ?? '';
    form.category = n.category ?? props.categories[0] ?? '';
    form.content = n.content;
    form.author = n.author ?? '';
    form.allow_comments = !!n.allow_comments;
    showForm.value = true;
};
const submit = () => {
    const opts = { onSuccess: () => (showForm.value = false), preserveScroll: true, forceFormData: true };
    if (editingId.value) {
        // อัปเดตพร้อมไฟล์ → spoof เป็น PUT
        form.transform((d) => ({ ...d, _method: 'put' }));
        form.post(route('admin.news.update', editingId.value), opts);
    } else {
        // เพิ่มใหม่ → ล้าง transform เดิม (กันค่า _method: 'put' ค้างจากการแก้ไขก่อนหน้า)
        form.transform((d) => d);
        form.post(route('admin.news.store'), opts);
    }
};
const remove = (n) => {
    if (confirm(`ลบข่าว "${n.title}" ?`)) {
        router.delete(route('admin.news.destroy', n.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="จัดการข่าวประชาสัมพันธ์" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">จัดการข่าวประชาสัมพันธ์</h2>
                <PrimaryButton @click="openCreate">+ เพิ่มข่าว</PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ flash.success }}
                </div>
                <div v-if="flash.error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ flash.error }}
                </div>

                <div v-if="selected.length > 0" class="flex items-center justify-between rounded-lg bg-indigo-50 px-4 py-2 text-sm">
                    <span class="text-indigo-700">เลือก {{ selected.length }} รายการ</span>
                    <DangerButton @click="bulkDelete">ลบที่เลือก</DangerButton>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-3 w-10">
                                    <input type="checkbox" :checked="allChecked" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @change="toggleAll" />
                                </th>
                                <th class="px-6 py-3">หัวข้อข่าว</th>
                                <th class="px-6 py-3">หมวด</th>
                                <th class="px-6 py-3">ลงประกาศ</th>
                                <th class="px-6 py-3">ไฟล์แนบ</th>
                                <th class="px-6 py-3 text-right">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-if="news.data.length === 0">
                                <td colspan="6" class="px-6 py-12">
                                    <EmptyState title="ยังไม่มีข่าว" />
                                </td>
                            </tr>
                            <tr v-for="n in news.data" :key="n.id" class="text-sm text-gray-700 hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <input v-model="selected" :value="n.id" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900">{{ n.title }}</td>
                                <td class="px-6 py-4">
                                    <span v-if="n.category" class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">{{ n.category }}</span>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ n.created_at }}</td>
                                <td class="px-6 py-4">
                                    <a v-if="n.file_path" :href="fileUrl(n.file_path)" target="_blank" class="text-indigo-600 hover:underline">ดาวน์โหลด</a>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <button class="font-medium text-indigo-600 hover:text-indigo-800" @click="openEdit(n)">แก้ไข</button>
                                        <button class="font-medium text-red-600 hover:text-red-800" @click="remove(n)">ลบ</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="news.links.length > 3" class="flex flex-wrap justify-center gap-1">
                    <component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, i) in news.links"
                        :key="i"
                        :href="link.url"
                        class="rounded-md px-3 py-2 text-sm"
                        :class="[
                            link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50',
                            !link.url && 'cursor-default opacity-50',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Modal เพิ่ม/แก้ไขข่าว -->
        <Modal :show="showForm" max-width="2xl" @close="showForm = false">
            <form class="max-h-[85vh] space-y-5 overflow-y-auto p-6" @submit.prevent="submit">
                <h2 class="text-lg font-semibold text-gray-900">{{ editingId ? 'แก้ไขข่าว' : 'เพิ่มข่าว' }}</h2>

                <div>
                    <InputLabel for="title" value="หัวข้อ" />
                    <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" autofocus />
                    <InputError :message="form.errors.title" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="excerpt" value="รายละเอียดย่อสั้นๆ" />
                    <TextInput id="excerpt" v-model="form.excerpt" type="text" class="mt-1 block w-full" placeholder="สรุปสั้นๆ แสดงใต้หัวข้อข่าว" />
                    <InputError :message="form.errors.excerpt" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="category" value="หมวดหมู่" />
                    <select id="category" v-model="form.category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                    </select>
                    <InputError :message="form.errors.category" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="file" value="รูปไอคอนข่าวสาร" />
                    <input
                        id="file"
                        type="file"
                        accept="image/jpeg,image/png"
                        class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                        @input="form.file = $event.target.files[0]"
                    />
                    <p class="mt-1 text-xs text-gray-400">รูปเป็นไฟล์ .jpg .jpeg .png (แนะนำ 80 × 60 pixels)</p>
                    <InputError :message="form.errors.file" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="content" value="เนื้อหา" />
                    <textarea id="content" v-model="form.content" rows="8" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <InputError :message="form.errors.content" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="author" value="ผู้บันทึก" />
                    <TextInput id="author" v-model="form.author" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.author" class="mt-2" />
                </div>

                <label class="flex items-center gap-2">
                    <input v-model="form.allow_comments" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                    <span class="text-sm text-gray-700">อนุญาตให้มีการแสดงความคิดเห็น</span>
                </label>

                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton type="button" @click="showForm = false">ยกเลิก</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">บันทึก</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
