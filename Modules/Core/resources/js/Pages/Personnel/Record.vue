<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    person: { type: Object, default: () => ({}) },
    profile: { type: Object, default: () => ({}) },
    decorations: { type: Array, default: () => [] },
    standings: { type: Array, default: () => [] },
    educationLevels: { type: Array, default: () => [] },
});

const form = useForm({ ...props.profile });
const save = () => form.put(route('personnel-records.update', props.person.id), { preserveScroll: true });

const deco = useForm({ name: '', year: null });
const addDeco = () => deco.post(route('personnel-records.decorations.add', props.person.id), { preserveScroll: true, onSuccess: () => deco.reset() });
const removeDeco = (id) => {
    if (confirm('ลบรายการนี้?')) router.delete(route('personnel-records.decorations.remove', id), { preserveScroll: true });
};

const field = 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
const label = 'block text-xs font-medium text-gray-600';
</script>

<template>
    <Head :title="'ประวัติ: ' + person.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">{{ person.name }}</h2>
                    <p class="text-xs text-gray-400">{{ person.position ?? '—' }} · {{ person.unit ?? '—' }}</p>
                </div>
                <Link :href="route('personnel-records.index')" class="text-sm text-gray-500 hover:text-gray-700">← รายชื่อบุคลากร</Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-4xl space-y-5 px-4 sm:px-6 lg:px-8">
                <!-- ก.พ.7 -->
                <form class="space-y-4 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100" @submit.prevent="save">
                    <h3 class="text-base font-bold text-gray-800">ทะเบียนประวัติ (ก.พ.7)</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label :class="label">เลขประจำตัวประชาชน</label>
                            <input v-model="form.citizen_id" type="text" maxlength="13" :class="field" />
                        </div>
                        <div>
                            <label :class="label">เพศ</label>
                            <select v-model="form.gender" :class="field">
                                <option :value="null">—</option>
                                <option value="M">ชาย</option>
                                <option value="F">หญิง</option>
                            </select>
                        </div>
                        <div>
                            <label :class="label">วันเดือนปีเกิด</label>
                            <input v-model="form.birthdate" type="date" :class="field" />
                        </div>
                        <div>
                            <label :class="label">วันบรรจุเข้ารับราชการ</label>
                            <input v-model="form.appointed_date" type="date" :class="field" />
                        </div>
                        <div>
                            <label :class="label">วุฒิการศึกษา</label>
                            <select v-model="form.education_level" :class="field">
                                <option :value="null">—</option>
                                <option v-for="e in educationLevels" :key="e" :value="e">{{ e }}</option>
                            </select>
                        </div>
                        <div>
                            <label :class="label">วิชาเอก</label>
                            <input v-model="form.education_major" type="text" :class="field" />
                        </div>
                        <div>
                            <label :class="label">วิทยฐานะ</label>
                            <select v-model="form.academic_standing" :class="field">
                                <option :value="null">—</option>
                                <option v-for="s in standings" :key="s" :value="s">{{ s }}</option>
                            </select>
                        </div>
                        <div>
                            <label :class="label">วันได้รับวิทยฐานะ</label>
                            <input v-model="form.academic_standing_date" type="date" :class="field" />
                        </div>
                        <div>
                            <label :class="label">ระดับ/อันดับ (เช่น คศ.2)</label>
                            <input v-model="form.rank" type="text" :class="field" />
                        </div>
                    </div>
                    <div>
                        <label :class="label">ที่อยู่</label>
                        <textarea v-model="form.address" rows="2" :class="field" />
                    </div>
                    <div>
                        <label :class="label">หมายเหตุ</label>
                        <textarea v-model="form.note" rows="2" :class="field" />
                    </div>
                    <div class="flex items-center justify-end gap-3">
                        <span v-if="form.recentlySuccessful" class="text-sm text-emerald-600">บันทึกแล้ว ✓</span>
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-60">บันทึกประวัติ</button>
                    </div>
                </form>

                <!-- เครื่องราชอิสริยาภรณ์ -->
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h3 class="mb-3 text-base font-bold text-gray-800">เครื่องราชอิสริยาภรณ์</h3>
                    <ul class="mb-3 divide-y divide-gray-50">
                        <li v-for="d in decorations" :key="d.id" class="flex items-center justify-between py-2 text-sm">
                            <span class="text-gray-800">{{ d.name }} <span v-if="d.year" class="text-gray-400">(พ.ศ. {{ d.year }})</span></span>
                            <button type="button" class="text-xs text-rose-500 hover:underline" @click="removeDeco(d.id)">ลบ</button>
                        </li>
                        <li v-if="!decorations.length" class="py-3 text-center text-xs text-gray-400">ยังไม่มีรายการ</li>
                    </ul>
                    <form class="flex flex-wrap items-end gap-2" @submit.prevent="addDeco">
                        <div class="flex-1">
                            <label :class="label">ชื่อเครื่องราชอิสริยาภรณ์</label>
                            <input v-model="deco.name" type="text" placeholder="เช่น จัตุรถาภรณ์ช้างเผือก" :class="field" />
                        </div>
                        <div class="w-28">
                            <label :class="label">ปี (พ.ศ.)</label>
                            <input v-model="deco.year" type="number" min="2400" max="2700" :class="field" />
                        </div>
                        <button type="submit" :disabled="!deco.name || deco.processing" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-50">เพิ่ม</button>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
