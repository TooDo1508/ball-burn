<template>
    <RouterView v-slot="{ Component }">
        <component :is="Component" v-if="Component" />
        <main v-else role="status" aria-live="polite" style="min-height:45vh;display:grid;place-content:center;gap:8px;padding:40px 20px;text-align:center;color:#171717">
            <strong>Đang mở trang…</strong>
            <span style="font-size:14px;color:#666">Đang tải nội dung.</span>
        </main>
    </RouterView>
</template>
