<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Mail, MapPin, Phone, Save, Settings2, Upload } from '@lucide/vue';
import { onBeforeUnmount, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';

type ContactSettings = {
    email: string;
    phone: string;
    address: string;
    business_hours: string;
    venue_description: string;
    map_url: string;
    facebook_url: string;
    instagram_url: string;
    payment_qr_url: string | null;
};

const props = defineProps<{ contact: ContactSettings }>();
const form = useForm({
    contact: { ...props.contact },
    payment_qr: undefined as File | undefined,
    remove_payment_qr: false,
});
const qrPreviewUrl = ref(props.contact.payment_qr_url ?? '');
let temporaryPreviewUrl = '';

watch(() => props.contact.payment_qr_url, (url) => {
    if (temporaryPreviewUrl) URL.revokeObjectURL(temporaryPreviewUrl);
    temporaryPreviewUrl = '';
    qrPreviewUrl.value = url ?? '';
    form.payment_qr = undefined;
    form.remove_payment_qr = false;
});

onBeforeUnmount(() => {
    if (temporaryPreviewUrl) URL.revokeObjectURL(temporaryPreviewUrl);
});

function choosePaymentQr(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;

    if (temporaryPreviewUrl) URL.revokeObjectURL(temporaryPreviewUrl);
    temporaryPreviewUrl = URL.createObjectURL(file);
    qrPreviewUrl.value = temporaryPreviewUrl;
    form.payment_qr = file;
    form.remove_payment_qr = false;
}

function save() {
    form.put('/admin/settings/contact', { preserveScroll: true, forceFormData: true });
}
</script>

<template>
    <Head title="Cài đặt website" />
    <section class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-6">
        <Heading title="Cài đặt website" description="Thông tin chung hiển thị trên các trang BALLBURN và mã QR dùng trong luồng thanh toán." />

        <form class="grid gap-6 rounded-xl border bg-card p-5 sm:p-6" @submit.prevent="save">
            <div class="flex items-start gap-3 rounded-lg border border-orange-500/20 bg-orange-500/5 p-4 text-sm leading-6 text-muted-foreground">
                <Settings2 class="mt-0.5 size-5 shrink-0 text-orange-500" />
                <p>Các thông tin ở đây được dùng chung trên website. Email này là địa chỉ liên hệ công khai; thông tin đăng nhập SMTP vẫn được quản lý bằng biến môi trường.</p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-2 sm:col-span-2">
                    <label for="contact-email" class="flex items-center gap-2 text-sm font-semibold"><Mail class="size-4 text-orange-500" />Email chính của website *</label>
                    <input id="contact-email" v-model.trim="form.contact.email" type="email" maxlength="255" required placeholder="hello@tenmiencuaban.vn" class="min-h-11 w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:border-primary" />
                    <p v-if="form.errors['contact.email']" class="text-sm text-destructive">{{ form.errors['contact.email'] }}</p>
                    <p class="text-xs text-muted-foreground">Email này hiển thị trên website và nhận nội dung các liên hệ mới từ khách.</p>
                </div>

                <div class="grid gap-2">
                    <label for="contact-phone" class="flex items-center gap-2 text-sm font-semibold"><Phone class="size-4 text-orange-500" />Số điện thoại</label>
                    <input id="contact-phone" v-model.trim="form.contact.phone" type="tel" maxlength="30" class="min-h-11 w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:border-primary" />
                    <p v-if="form.errors['contact.phone']" class="text-sm text-destructive">{{ form.errors['contact.phone'] }}</p>
                </div>

                <div class="grid gap-2">
                    <label for="contact-hours" class="text-sm font-semibold">Giờ hoạt động</label>
                    <input id="contact-hours" v-model.trim="form.contact.business_hours" maxlength="120" class="min-h-11 w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:border-primary" />
                    <p v-if="form.errors['contact.business_hours']" class="text-sm text-destructive">{{ form.errors['contact.business_hours'] }}</p>
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <label for="contact-address" class="flex items-center gap-2 text-sm font-semibold"><MapPin class="size-4 text-orange-500" />Địa chỉ</label>
                    <input id="contact-address" v-model.trim="form.contact.address" maxlength="255" class="min-h-11 w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:border-primary" />
                    <p v-if="form.errors['contact.address']" class="text-sm text-destructive">{{ form.errors['contact.address'] }}</p>
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <label for="contact-venue-description" class="text-sm font-semibold">Mô tả ngắn địa điểm</label>
                    <textarea id="contact-venue-description" v-model.trim="form.contact.venue_description" rows="2" maxlength="250" class="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:border-primary" />
                    <p v-if="form.errors['contact.venue_description']" class="text-sm text-destructive">{{ form.errors['contact.venue_description'] }}</p>
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <label for="contact-map" class="text-sm font-semibold">Liên kết bản đồ</label>
                    <input id="contact-map" v-model.trim="form.contact.map_url" type="url" maxlength="1000" placeholder="https://maps.google.com/..." class="min-h-11 w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:border-primary" />
                    <p v-if="form.errors['contact.map_url']" class="text-sm text-destructive">{{ form.errors['contact.map_url'] }}</p>
                </div>

                <div class="grid gap-2">
                    <label for="contact-facebook" class="text-sm font-semibold">Facebook</label>
                    <input id="contact-facebook" v-model.trim="form.contact.facebook_url" type="url" maxlength="1000" class="min-h-11 w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:border-primary" />
                    <p v-if="form.errors['contact.facebook_url']" class="text-sm text-destructive">{{ form.errors['contact.facebook_url'] }}</p>
                </div>

                <div class="grid gap-2">
                    <label for="contact-instagram" class="text-sm font-semibold">Instagram</label>
                    <input id="contact-instagram" v-model.trim="form.contact.instagram_url" type="url" maxlength="1000" class="min-h-11 w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:border-primary" />
                    <p v-if="form.errors['contact.instagram_url']" class="text-sm text-destructive">{{ form.errors['contact.instagram_url'] }}</p>
                </div>

                <div class="grid gap-3 border-t pt-5 sm:col-span-2">
                    <div>
                        <h2 class="text-base font-semibold">QR nhận thanh toán</h2>
                        <p class="mt-1 text-sm text-muted-foreground">Ảnh này được dùng chung và hiển thị ở bước thanh toán đặt sân. Hỗ trợ PNG, JPG hoặc WebP, tối đa 5 MB.</p>
                    </div>
                    <div class="flex flex-wrap items-start gap-5">
                        <div v-if="qrPreviewUrl && !form.remove_payment_qr" class="grid gap-2">
                            <img :src="qrPreviewUrl" alt="Ảnh QR nhận thanh toán" class="size-48 rounded-lg border bg-white object-contain p-2" />
                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-destructive">
                                <input v-model="form.remove_payment_qr" type="checkbox" class="size-4 rounded border-border" />
                                Xóa QR hiện tại
                            </label>
                        </div>
                        <div v-else class="grid size-48 place-items-center rounded-lg border border-dashed bg-muted p-4 text-center text-sm text-muted-foreground">
                            Chưa có ảnh QR thanh toán
                        </div>
                        <div class="grid content-start gap-2">
                            <label for="payment-qr" class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-md border bg-background px-4 py-2 text-sm font-semibold hover:bg-muted">
                                <Upload class="size-4" />{{ qrPreviewUrl && !form.remove_payment_qr ? 'Thay ảnh QR' : 'Tải ảnh QR lên' }}
                            </label>
                            <input id="payment-qr" type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="choosePaymentQr" />
                            <p v-if="form.errors.payment_qr" class="text-sm text-destructive">{{ form.errors.payment_qr }}</p>
                            <p v-if="form.errors.remove_payment_qr" class="text-sm text-destructive">{{ form.errors.remove_payment_qr }}</p>
                            <p class="max-w-sm text-xs leading-5 text-muted-foreground">Khách sẽ quét mã này để chuyển khoản và dùng mã tham chiếu hiển thị tại bước đặt sân làm nội dung chuyển khoản.</p>
                        </div>
                    </div>
                </div>
            </div>

            <p v-if="$page.props.flash?.success" class="text-sm font-medium text-green-700">{{ $page.props.flash.success }}</p>
            <div class="flex flex-wrap items-center gap-3 border-t pt-5">
                <button type="submit" :disabled="form.processing" class="inline-flex min-h-11 items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground disabled:opacity-50"><Save class="size-4" />{{ form.processing ? 'Đang lưu…' : 'Lưu cài đặt' }}</button>
                <a href="/contact" target="_blank" class="text-sm font-medium underline underline-offset-4">Xem trang Liên hệ</a>
            </div>
        </form>
    </section>
</template>
