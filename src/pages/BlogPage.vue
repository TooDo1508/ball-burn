<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import BaseButton from '../components/ui/BaseButton.vue';
import { updateBlogSeo } from '../lib/blogSeo.js';
import { getServerPageData } from '../lib/serverPageData.js';
import '../assets/styles/blog.css';

const serverData = getServerPageData('list');
const posts = ref(serverData?.posts ?? []);
const loading = ref(!serverData);
const error = ref('');
const nextPage = ref(serverData?.next_page_url ?? null);
const loadingMore = ref(false);
const pageTitle = 'Tin tức pickleball, luật chơi và mẹo tập luyện | BALLBURN';
const pageDescription = 'Cẩm nang pickleball bằng tiếng Việt: luật chơi, chiến thuật, dụng cụ, sức khỏe và văn hóa giao lưu từ BALLBURN PickleClub & Bistro tại TP. Hồ Chí Minh.';
const defaultImage = 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1600&q=85';

onMounted(async () => {
    updateBlogSeo({
        title: pageTitle,
        description: pageDescription,
        image: defaultImage,
        canonical: `${window.location.origin}/blog`,
        structuredData: {
            '@context': 'https://schema.org',
            '@type': 'Blog',
            name: 'Tin tức pickleball BALLBURN',
            description: pageDescription,
            url: `${window.location.origin}/blog`,
            inLanguage: 'vi-VN',
            publisher: { '@type': 'Organization', name: 'BALLBURN PickleClub & Bistro' },
        },
    });
    if (!serverData) {
        await loadPosts('/api/blog/posts');
        loading.value = false;
    }
});

async function loadPosts(url) {
    try {
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Không tải được danh sách bài viết.');
        const result = await response.json();
        posts.value = [...posts.value, ...(result.data ?? [])];
        nextPage.value = result.next_page_url;
    } catch (loadError) {
        error.value = loadError instanceof Error ? loadError.message : 'Không tải được danh sách bài viết.';
    }
}

async function loadMore() {
    if (!nextPage.value || loadingMore.value) return;
    loadingMore.value = true;
    await loadPosts(nextPage.value);
    loadingMore.value = false;
}

function formatDate(value) {
    return value ? new Intl.DateTimeFormat('vi-VN', { dateStyle: 'long' }).format(new Date(value)) : '';
}
</script>

<template>
    <main class="blog-page">
        <section class="blog-hero">
            <div class="blog-hero-image" aria-hidden="true" :style="{ backgroundImage: `url('${defaultImage}')` }" />
            <div class="blog-hero-copy">
                <p class="blog-eyebrow">BALLBURN · PICKLECLUB & BISTRO</p>
                <h1>CẨM NANG<br /><em>PICKLEBALL</em></h1>
                <p>Luật chơi, chiến thuật và cảm hứng để bạn tận hưởng từng giờ trên sân — từ những bước đầu tiên đến những trận đôi đầy kết nối.</p>
                <BaseButton to="/booking">Khám phá sân chơi <span aria-hidden="true">↗</span></BaseButton>
            </div>
            <span class="blog-hero-index" aria-hidden="true">PLAY · LEARN · CONNECT</span>
        </section>

        <section class="blog-listing" aria-labelledby="blog-list-title">
            <header class="blog-section-heading"><div><p class="blog-eyebrow">GÓC KIẾN THỨC</p><h2 id="blog-list-title">Mới trên sân</h2></div><p>Thông tin dễ áp dụng cho cộng đồng pickleball tại TP. Hồ Chí Minh và người chơi Việt Nam.</p></header>
            <p v-if="loading" class="blog-status">Đang tải bài viết…</p>
            <p v-else-if="error" class="blog-status blog-status-error" role="alert">{{ error }}</p>
            <div v-else-if="posts.length" class="blog-card-grid">
                <article v-for="(post, index) in posts" :key="post.slug" class="blog-card" :class="{ 'blog-card-featured': index === 0 }">
                    <RouterLink class="blog-card-image" :to="`/blog/${post.slug}`" :aria-label="`Đọc bài: ${post.title}`">
                        <img v-if="post.cover_image_url" :src="post.cover_image_url" :alt="post.cover_image_alt || post.title" loading="lazy" />
                        <span class="blog-card-image-fallback" v-else aria-hidden="true">BALLBURN</span>
                        <span class="blog-card-arrow" aria-hidden="true">↗</span>
                    </RouterLink>
                    <div class="blog-card-copy">
                        <div class="blog-card-meta"><span v-for="category in post.categories" :key="category">{{ category }}</span><time v-if="post.published_at" :datetime="post.published_at">{{ formatDate(post.published_at) }}</time></div>
                        <h3><RouterLink :to="`/blog/${post.slug}`">{{ post.title }}</RouterLink></h3>
                        <p>{{ post.excerpt }}</p>
                        <RouterLink class="blog-read-link" :to="`/blog/${post.slug}`">ĐỌC BÀI VIẾT <span aria-hidden="true">→</span></RouterLink>
                    </div>
                </article>
            </div>
            <p v-else class="blog-status">Chưa có bài viết được xuất bản.</p>
            <div v-if="nextPage" class="blog-more"><button class="button button-outline" type="button" :disabled="loadingMore" @click="loadMore">{{ loadingMore ? 'Đang tải…' : 'Xem thêm bài viết' }}</button></div>
        </section>

        <section class="blog-cta"><p class="blog-eyebrow">SẴN SÀNG LÊN SÂN?</p><h2>Kiến thức tốt.<br /><em>Trận đấu vui hơn.</em></h2><BaseButton to="/booking">Đặt sân tại BALLBURN <span aria-hidden="true">↗</span></BaseButton></section>
    </main>
</template>
