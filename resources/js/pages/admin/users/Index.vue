<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { CheckCircle2, ChevronLeft, ChevronRight, Clock3, Crown, Download, LoaderCircle, Pencil, Plus, Search, ShieldCheck, Trash2, UserRound, UserRoundX, Users } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { confirmAction } from '@/lib/confirmDialog';
import { dashboard } from '@/routes';

defineOptions({ layout: { breadcrumbs: [{ title: 'Quản trị', href: dashboard() }, { title: 'Quản lý người dùng', href: '/admin/users' }] } });

type Role = 'ADMIN' | 'MANAGER' | 'STAFF';
type AccountStatus = 'ACTIVE' | 'LOCKED' | 'DISABLED';
type UserItem = { id: number; name: string; email: string; phone: string | null; role: Role; account_status: AccountStatus; created_at: string | null; can_edit: boolean; can_delete: boolean };
type PageLink = { url: string | null; label: string; active: boolean };
type UserPage = { data: UserItem[]; links: PageLink[]; from: number | null; to: number | null; total: number };
const props = defineProps<{
    users: UserPage;
    stats: { total: number; active: number; locked: number; disabled: number };
    filters: { search: string; role: string; status: string };
    assignableRoles: Role[];
}>();
const roleLabels: Record<Role, string> = { ADMIN: 'Admin', MANAGER: 'Quản lý', STAFF: 'Nhân viên' };
const statusLabels: Record<AccountStatus, string> = { ACTIVE: 'Hoạt động', LOCKED: 'Tạm khóa', DISABLED: 'Vô hiệu hóa' };
const filters = reactive({ ...props.filters });
watch(() => props.filters, value => Object.assign(filters, value));
const selected = ref<number[]>([]);
watch(() => props.users.data, () => { selected.value = []; });
const allSelected = computed(() => props.users.data.length > 0 && selected.value.length === props.users.data.length);
const open = ref(false);
const editing = ref<UserItem | null>(null);
const notice = ref('');
const deleting = ref<number | null>(null);
const form = useForm({ name: '', email: '', phone: '', role: 'STAFF' as Role, account_status: 'ACTIVE' as AccountStatus, password: '', password_confirmation: '' });
const cards = computed(() => [
    { label: 'Tổng người dùng', value: props.stats.total, icon: Users, tone: 'orange' },
    { label: 'Đang hoạt động', value: props.stats.active, icon: CheckCircle2, tone: 'green' },
    { label: 'Tạm khóa', value: props.stats.locked, icon: Clock3, tone: 'amber' },
    { label: 'Đã vô hiệu hóa', value: props.stats.disabled, icon: UserRoundX, tone: 'red' },
]);
function applyFilters() {
    router.get('/admin/users', filters, { preserveState: true, preserveScroll: true, replace: true });
}
function edit(user: UserItem | null = null) {
    editing.value = user;
    form.reset(); form.clearErrors();
    if (user) Object.assign(form, { name: user.name, email: user.email, phone: user.phone ?? '', role: user.role, account_status: user.account_status });
    open.value = true;
}
function save() {
    const options = { preserveScroll: true, onSuccess: () => { notice.value = editing.value ? 'Đã cập nhật người dùng.' : 'Đã thêm người dùng.'; open.value = false; form.reset(); } };
    if (editing.value) form.put(`/admin/users/${editing.value.id}`, options);
    else form.post('/admin/users', options);
}
async function remove(user: UserItem) {
    if (!await confirmAction({ title: 'Xóa người dùng?', description: `Xóa tài khoản của ${user.name} (${user.email})? Người này sẽ không thể đăng nhập. Thao tác không thể hoàn tác.`, confirmLabel: 'Xóa người dùng', destructive: true })) return;
    deleting.value = user.id;
    router.delete(`/admin/users/${user.id}`, { preserveScroll: true, onSuccess: () => { notice.value = 'Đã xóa người dùng.'; }, onError: errors => { notice.value = errors.user ?? 'Không thể xóa người dùng.'; }, onFinish: () => { deleting.value = null; } });
}
function initials(name: string) { return name.trim().split(/\s+/).slice(-2).map(part => part.charAt(0)).join('').toLocaleUpperCase('vi'); }
function toggleAll(checked: boolean) { selected.value = checked ? props.users.data.map(user => user.id) : []; }
function exportCsv() {
    const rows = props.users.data.filter(user => !selected.value.length || selected.value.includes(user.id));
    const cell = (value: string) => `"${value.replace(/^[=+@\t\r-]/, "'$&").replaceAll('"', '""')}"`;
    const csv = [['Họ tên', 'Email', 'Số điện thoại', 'Vai trò', 'Trạng thái', 'Ngày tạo'], ...rows.map(user => [user.name, user.email, user.phone ?? '', roleLabels[user.role], statusLabels[user.account_status], user.created_at ?? ''])].map(row => row.map(cell).join(',')).join('\r\n');
    const url = URL.createObjectURL(new Blob(['\uFEFF', csv], { type: 'text/csv;charset=utf-8' }));
    const link = document.createElement('a'); link.href = url; link.download = 'ballburn-nguoi-dung.csv'; link.click(); URL.revokeObjectURL(url);
}
</script>

<template>
    <Head title="Quản lý người dùng" />
    <main class="min-h-full bg-background p-4 text-foreground sm:p-6 xl:p-8">
        <div class="mx-auto max-w-[1500px] space-y-6">
            <header class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-orange-500/10 text-orange-600 dark:text-orange-400"><Users class="size-7" /></span>
                    <div><h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Quản lý người dùng</h1><p class="mt-1 text-sm text-muted-foreground">Quản lý tài khoản truy cập hệ thống, phân quyền và trạng thái hoạt động.</p></div>
                </div>
                <button class="primary-button" @click="edit()"><Plus class="size-5" />Thêm người dùng</button>
            </header>
            <p v-if="notice" role="status" class="rounded-xl border border-border bg-muted p-3 text-sm">{{ notice }}</p>
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Thống kê tài khoản">
                <article v-for="card in cards" :key="card.label" :class="['stat-card', `tone-${card.tone}`]">
                    <span class="stat-icon"><component :is="card.icon" class="size-7" /></span>
                    <div><p class="text-sm text-foreground/80">{{ card.label }}</p><p class="mt-1 text-3xl font-bold tracking-tight">{{ card.value }}</p></div>
                </article>
            </section>
            <section class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                <form class="grid items-center gap-3 p-4 lg:grid-cols-[minmax(220px,2fr)_1fr_1fr_auto] lg:p-5" @submit.prevent="applyFilters">
                    <label class="relative block"><span class="sr-only">Tìm tên, email, số điện thoại</span><Search class="absolute left-3 top-1/2 size-5 -translate-y-1/2 text-muted-foreground" /><input v-model="filters.search" type="search" maxlength="120" placeholder="Tìm theo tên, email, số điện thoại..." class="field search-field" /><button class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-md px-2 py-1.5 text-xs font-semibold text-orange-700 hover:bg-orange-500/10 dark:text-orange-300" type="submit">Tìm kiếm</button></label>
                    <label><span class="sr-only">Lọc vai trò</span><select v-model="filters.role" class="field" @change="applyFilters"><option value="">Tất cả vai trò</option><option v-for="(label, role) in roleLabels" :key="role" :value="role">{{ label }}</option></select></label>
                    <label><span class="sr-only">Lọc trạng thái</span><select v-model="filters.status" class="field" @change="applyFilters"><option value="">Tất cả trạng thái</option><option v-for="(label, status) in statusLabels" :key="status" :value="status">{{ label }}</option></select></label>
                    <button type="button" class="secondary-button text-orange-700 dark:text-orange-300" :disabled="!users.data.length" title="Xuất các dòng đã chọn hoặc trang hiện tại; mở được bằng Excel" @click="exportCsv"><Download class="size-4" />Xuất CSV{{ selected.length ? ` (${selected.length})` : '' }}</button>
                </form>
                <div class="overflow-x-auto px-4 lg:px-5">
                    <table class="w-full min-w-[1020px] text-left text-sm">
                        <thead class="bg-muted/60"><tr><th class="w-12 rounded-l-lg px-3 py-4"><input type="checkbox" class="size-4 accent-orange-600" aria-label="Chọn trang hiện tại" :checked="allSelected" :indeterminate="selected.length > 0 && !allSelected" @change="toggleAll(($event.target as HTMLInputElement).checked)" /></th><th class="px-3 py-4">Người dùng</th><th class="px-3 py-4">Email</th><th class="px-3 py-4">Số điện thoại</th><th class="px-3 py-4">Vai trò</th><th class="px-3 py-4">Trạng thái</th><th class="px-3 py-4">Ngày tạo</th><th class="rounded-r-lg px-3 py-4 text-right">Thao tác</th></tr></thead>
                        <tbody class="divide-y divide-border/60">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-muted/30">
                                <td class="px-3 py-3.5"><input v-model="selected" :value="user.id" type="checkbox" class="size-4 accent-orange-600" :aria-label="`Chọn ${user.name}`" /></td>
                                <td class="px-3 py-3.5"><div class="flex items-center gap-3"><span :class="['grid size-11 shrink-0 place-items-center rounded-full font-bold', user.id % 2 ? 'bg-orange-500/10 text-orange-700 dark:text-orange-300' : 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300']">{{ initials(user.name) }}</span><span class="max-w-48 truncate font-semibold" :title="user.name">{{ user.name }}</span></div></td>
                                <td class="px-3 py-3.5"><span class="block max-w-60 truncate" :title="user.email">{{ user.email }}</span></td>
                                <td class="whitespace-nowrap px-3 py-3.5">{{ user.phone || '—' }}</td>
                                <td class="px-3 py-3.5"><span :class="['badge', user.role === 'ADMIN' ? 'role-admin' : user.role === 'MANAGER' ? 'role-manager' : 'role-staff']"><component :is="user.role === 'ADMIN' ? Crown : user.role === 'MANAGER' ? ShieldCheck : UserRound" class="size-3.5" />{{ roleLabels[user.role] }}</span></td>
                                <td class="px-3 py-3.5"><span :class="['badge', `status-${user.account_status}`]"><component :is="user.account_status === 'ACTIVE' ? CheckCircle2 : user.account_status === 'LOCKED' ? Clock3 : UserRoundX" class="size-3.5" />{{ statusLabels[user.account_status] }}</span></td>
                                <td class="whitespace-nowrap px-3 py-3.5 text-xs text-muted-foreground">{{ user.created_at || '—' }}</td>
                                <td class="px-3 py-3.5"><div class="flex justify-end gap-2"><button v-if="user.can_edit" class="icon-button bg-muted hover:bg-accent" :aria-label="`Sửa ${user.name}`" @click="edit(user)"><Pencil class="size-4" /></button><button v-if="user.can_delete" class="icon-button bg-rose-500/10 text-rose-600 hover:bg-rose-500/20 dark:text-rose-300" :disabled="deleting !== null" :aria-label="`Xóa ${user.name}`" @click="remove(user)"><LoaderCircle v-if="deleting === user.id" class="size-4 animate-spin" /><Trash2 v-else class="size-4" /></button><span v-if="!user.can_edit && !user.can_delete" class="text-xs text-muted-foreground">Chỉ xem</span></div></td>
                            </tr>
                            <tr v-if="!users.data.length"><td colspan="8" class="py-16 text-center"><Users class="mx-auto size-10 text-muted-foreground" /><p class="mt-3 font-semibold">Không tìm thấy người dùng</p><p class="mt-1 text-muted-foreground">Thử thay đổi từ khóa hoặc bộ lọc.</p></td></tr>
                        </tbody>
                    </table>
                </div>
                <footer class="flex flex-wrap items-center justify-between gap-4 p-5">
                    <p class="text-sm text-muted-foreground">Hiển thị <strong class="font-medium text-foreground">{{ users.from ?? 0 }}–{{ users.to ?? 0 }}</strong> của <strong class="font-medium text-foreground">{{ users.total }}</strong> người dùng<span v-if="selected.length" class="ml-2">· Đã chọn {{ selected.length }}</span></p>
                    <nav class="flex gap-2" aria-label="Phân trang"><template v-for="(link, index) in users.links" :key="index"><Link v-if="link.url" :href="link.url" preserve-scroll :aria-current="link.active ? 'page' : undefined" :aria-label="index === 0 ? 'Trang trước' : index === users.links.length - 1 ? 'Trang sau' : `Trang ${link.label}`" :class="['page-button', link.active && 'page-active']"><ChevronLeft v-if="index === 0" class="size-4" /><ChevronRight v-else-if="index === users.links.length - 1" class="size-4" /><span v-else>{{ link.label }}</span></Link><span v-else class="page-button opacity-40" aria-disabled="true"><ChevronLeft v-if="index === 0" class="size-4" /><ChevronRight v-else-if="index === users.links.length - 1" class="size-4" /><span v-else>{{ link.label }}</span></span></template></nav>
                </footer>
            </section>
            <p class="text-xs text-muted-foreground">Admin &gt; Quản lý &gt; Nhân viên. Chỉ có thể cấp và quản lý tài khoản cùng cấp hoặc thấp hơn. CSV xuất các dòng đã chọn hoặc trang hiện tại.</p>
        </div>
        <Dialog v-model:open="open" @update:open="value => { if (!value) { form.reset('password', 'password_confirmation'); form.clearErrors(); } }">
            <DialogContent class="max-h-[90dvh] overflow-y-auto sm:max-w-xl">
                <DialogHeader><DialogTitle>{{ editing ? 'Chỉnh sửa người dùng' : 'Thêm người dùng' }}</DialogTitle><DialogDescription>Cấp tài khoản truy cập hệ thống BALLBURN. Mật khẩu cần ít nhất 12 ký tự, gồm chữ hoa, chữ thường và số.</DialogDescription></DialogHeader>
                <form class="grid gap-4" @submit.prevent="save">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="form-label">Họ và tên <input v-model="form.name" name="name" class="field" required maxlength="255" autocomplete="name" :aria-invalid="!!form.errors.name" /><InputError :message="form.errors.name" /></label>
                        <label class="form-label">Email <input v-model="form.email" name="email" class="field" type="email" required maxlength="255" autocomplete="email" :aria-invalid="!!form.errors.email" /><InputError :message="form.errors.email" /></label>
                        <label class="form-label">Số điện thoại <span class="text-xs font-normal text-muted-foreground">Không bắt buộc</span><input v-model="form.phone" name="phone" class="field" type="tel" maxlength="30" autocomplete="tel" :aria-invalid="!!form.errors.phone" /><InputError :message="form.errors.phone" /></label>
                        <label class="form-label">Vai trò <select v-model="form.role" name="role" class="field"><option v-for="role in assignableRoles" :key="role" :value="role">{{ roleLabels[role] }}</option></select><InputError :message="form.errors.role" /></label>
                    </div>
                    <label class="form-label">Trạng thái <select v-model="form.account_status" name="account_status" class="field"><option v-for="(label, status) in statusLabels" :key="status" :value="status">{{ label }}</option></select><InputError :message="form.errors.account_status" /></label>
                    <p v-if="editing" class="text-xs text-muted-foreground">Để trống mật khẩu nếu không muốn thay đổi.</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="form-label">{{ editing ? 'Mật khẩu mới' : 'Mật khẩu' }}<input v-model="form.password" name="password" class="field" type="password" autocomplete="new-password" :required="!editing" minlength="12" maxlength="255" :aria-invalid="!!form.errors.password" /><InputError :message="form.errors.password" /></label>
                        <label class="form-label">Xác nhận mật khẩu <input v-model="form.password_confirmation" name="password_confirmation" class="field" type="password" autocomplete="new-password" :required="!editing || !!form.password" /></label>
                    </div>
                    <DialogFooter class="mt-2"><button type="button" class="secondary-button" :disabled="form.processing" @click="open = false">Đóng</button><button type="submit" class="primary-button" :disabled="form.processing"><LoaderCircle v-if="form.processing" class="size-4 animate-spin" />{{ editing ? 'Lưu thay đổi' : 'Thêm người dùng' }}</button></DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </main>
</template>

<style scoped>
@reference "../../../../css/app.css";
.field { @apply h-11 w-full rounded-lg border border-input bg-background px-3 text-sm font-normal text-foreground outline-none transition focus:border-orange-500 focus:ring-2 focus:ring-orange-500/15; }
.search-field { @apply pl-10 pr-20; }
.primary-button { @apply inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-orange-500 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 disabled:cursor-not-allowed disabled:opacity-50; }
.secondary-button { @apply inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-input bg-background px-4 text-sm font-semibold transition hover:bg-accent disabled:cursor-not-allowed disabled:opacity-40; }
.stat-card { @apply flex items-center gap-4 rounded-2xl border border-border p-5; background: linear-gradient(120deg, var(--color-card), color-mix(in srgb, var(--tone) 5%, var(--color-card))); }
.stat-icon { @apply grid size-16 shrink-0 place-items-center rounded-full; background: color-mix(in srgb, var(--tone) 10%, transparent); color: var(--tone); }
.tone-orange { --tone: #f97316; } .tone-green { --tone: #16a34a; } .tone-amber { --tone: #d97706; } .tone-red { --tone: #ef4444; }
.badge { @apply inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold; }
.role-admin { @apply bg-orange-500/10 text-orange-700 dark:text-orange-300; }
.role-manager, .status-ACTIVE { @apply bg-emerald-500/10 text-emerald-700 dark:text-emerald-300; }
.role-staff { @apply bg-muted text-muted-foreground; }
.status-LOCKED { @apply bg-amber-500/10 text-amber-700 dark:text-amber-300; }
.status-DISABLED { @apply bg-rose-500/10 text-rose-700 dark:text-rose-300; }
.icon-button { @apply grid size-9 place-items-center rounded-lg transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 disabled:opacity-40; }
.page-button { @apply grid size-9 place-items-center rounded-lg border border-input text-sm font-medium hover:bg-accent; }
.page-active { @apply border-orange-500 bg-orange-500 text-white hover:bg-orange-600; }
.form-label { @apply grid gap-1.5 text-sm font-medium; }
</style>
