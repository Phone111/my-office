<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    vehicles: { type: Array, default: () => [] },
    cars: { type: Array, default: () => [] }, // รถยนต์ราชการในทะเบียน [{id,name,license_plate,seats}]
});

const flash = computed(() => usePage().props.flash ?? {});

const form = useForm({
    title: 'ขออนุมัติไปราชการ',
    companions: '',
    purpose: '',
    destination: '',
    reference: '',
    depart_at: '',
    return_at: '',
    vehicle_type: 'official_car',
    vehicle_plate: '',
    vehicle_other: '',
    vehicle_id: null,
    budget_source: '',
    files: [null, null, null, null],
});

const setFile = (i, e) => (form.files[i] = e.target.files[0] ?? null);

const submit = () =>
    form
        .transform((d) => ({ ...d, files: d.files.filter(Boolean) }))
        .post(route('official-trips.store'), { forceFormData: true });
</script>

<template>
    <Head title="ขอไปราชการ" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">เขียนขอไปราชการ</h2>
                <Link :href="route('official-trips.index')" class="text-sm text-gray-500 hover:text-gray-700">← แฟ้มไปราชการ</Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form class="space-y-5 rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100" @submit.prevent="submit">
                    <div v-if="flash.error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ flash.error }}</div>

                    <div>
                        <InputLabel for="title" value="เรื่อง" />
                        <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.title" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="companions" value="พร้อมด้วย (ผู้ร่วมเดินทาง — ถ้ามี)" />
                        <textarea id="companions" v-model="form.companions" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <InputError :message="form.errors.companions" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="purpose" value="วัตถุประสงค์ของการไปราชการ" />
                        <textarea id="purpose" v-model="form.purpose" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <InputError :message="form.errors.purpose" class="mt-2" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel for="destination" value="สถานที่ไป (จังหวัด/หน่วยงาน)" />
                            <TextInput id="destination" v-model="form.destination" type="text" class="mt-1 block w-full" />
                            <InputError :message="form.errors.destination" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="reference" value="ตามคำสั่ง/หนังสือ (ถ้ามี)" />
                            <TextInput id="reference" v-model="form.reference" type="text" class="mt-1 block w-full" />
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel for="depart_at" value="ออกเดินทาง" />
                            <TextInput id="depart_at" v-model="form.depart_at" type="datetime-local" class="mt-1 block w-full" />
                            <InputError :message="form.errors.depart_at" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="return_at" value="กลับ" />
                            <TextInput id="return_at" v-model="form.return_at" type="datetime-local" class="mt-1 block w-full" />
                            <InputError :message="form.errors.return_at" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="พาหนะที่ใช้" />
                        <div class="mt-2 flex flex-wrap gap-x-5 gap-y-2">
                            <label v-for="v in vehicles" :key="v.value" class="inline-flex items-center gap-1.5 text-sm text-gray-700">
                                <input v-model="form.vehicle_type" type="radio" :value="v.value" class="text-indigo-600 focus:ring-indigo-500" />
                                {{ v.label }}
                            </label>
                        </div>
                        <!-- รถยนต์ราชการ → เลือกจากทะเบียน (ลิงค์ระบบจองรถ) -->
                        <div v-if="form.vehicle_type === 'official_car'" class="mt-2">
                            <select v-if="cars.length" v-model="form.vehicle_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-sm">
                                <option :value="null">— เลือกรถยนต์ราชการ —</option>
                                <option v-for="c in cars" :key="c.id" :value="c.id">{{ c.name }} · {{ c.license_plate }}<span v-if="c.seats"> ({{ c.seats }} ที่นั่ง)</span></option>
                            </select>
                            <p v-else class="text-sm text-amber-600">ยังไม่มีรถยนต์ราชการในทะเบียน — เพิ่มได้ที่เมนูจัดการรถยนต์</p>
                            <p class="mt-1 text-xs text-gray-400">เมื่อผู้บริหารอนุมัติแล้ว ระบบจะจองรถให้อัตโนมัติตามช่วงเวลาที่ไป (กันจองซ้ำ)</p>
                            <InputError :message="form.errors.vehicle_id" class="mt-1" />
                        </div>
                        <!-- รถยนต์ส่วนตัว → กรอกทะเบียนเอง -->
                        <div v-if="form.vehicle_type === 'private_car'" class="mt-2">
                            <TextInput v-model="form.vehicle_plate" type="text" placeholder="หมายเลขทะเบียนรถ" class="block w-full sm:max-w-sm" />
                        </div>
                        <div v-if="form.vehicle_type === 'other'" class="mt-2">
                            <TextInput v-model="form.vehicle_other" type="text" placeholder="ระบุพาหนะ" class="block w-full sm:max-w-sm" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="budget_source" value="ขอเบิกค่าใช้จ่ายจากเงินงบประมาณ (ถ้ามี)" />
                        <TextInput id="budget_source" v-model="form.budget_source" type="text" class="mt-1 block w-full" />
                    </div>

                    <div>
                        <InputLabel value="แนบหนังสือเชิญ/เอกสาร (สูงสุด 4 ไฟล์)" />
                        <div class="mt-2 space-y-2">
                            <input
                                v-for="i in 4"
                                :key="i"
                                type="file"
                                class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                                @change="setFile(i - 1, $event)"
                            />
                        </div>
                        <InputError :message="form.errors['files.0']" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                        <Link :href="route('official-trips.index')">
                            <SecondaryButton type="button">ยกเลิก</SecondaryButton>
                        </Link>
                        <PrimaryButton :disabled="form.processing">บันทึก & ส่ง</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
