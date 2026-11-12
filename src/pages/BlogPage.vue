<script setup>
import { computed, onMounted, ref } from 'vue';
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
const selectedCategory = ref('Tất cả');
const defaultImage = '/images/pickleball/court-rally-wide.jpg';
const pageDescription = 'Tin tức, kinh nghiệm và câu chuyện pickleball từ BALLBURN PickleClub & Bistro tại Hà Nội.';

const categories = computed(() => [...new Set(posts.value.flatMap((post) => post.categories ?? []))]);
const visiblePosts = computed(() => selectedCategory.value === 'Tất cả'
    ? posts.value
    : posts.value.filter((post) => (post.categories ?? []).includes(selectedCategory.value)));

onMounted(async () => {
    updateBlogSeo({
        title: 'Tin tức pickleball | BALLBURN PickleClub & Bistro',
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
        if (!response.ok) throw new Error('Không tải được danh sách bài viết. Vui lòng thử lại sau.');
        const result = await response.json();
        posts.value = [...posts.value, ...(result.data ?? [])];
        nextPage.value = result.next_page_url;
        error.value = '';
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
        <section class="blog-hero" aria-labelledby="blog-title">
            <div class="blog-hero-image" aria-hidden="true" :style="{ backgroundImage: `url('${defaultImage}')` }" />
            <div class="blog-hero-copy">
                <p class="blog-eyebrow">BALLBURN · PICKLECLUB & BISTRO</p>
                <h1 id="blog-title">Tin tức<br /><em>pickleball</em></h1>
                <p>Kinh nghiệm trên sân, câu chuyện cộng đồng và cảm hứng để mỗi trận đấu thêm trọn vẹn.</p>
            </div>
            <span class="blog-hero-index" aria-hidden="true">PLAY · LEARN · CONNECT</span>
        </section>

        <section class="blog-listing" aria-labelledby="blog-list-title">
            <header class="blog-section-heading">
                <div><p class="blog-eyebrow">GÓC KIẾN THỨC</p><h2 id="blog-list-title">Mới trên sân</h2></div>
                <p>Cập nhật từ BALLBURN dành cho người mới chơi lẫn những tay vợt đã quen sân.</p>
            </header>

            <div class="blog-layout">
                <div class="blog-feed">
                    <nav v-if="categories.length" class="blog-filters" aria-label="Lọc bài viết theo chủ đề">
                        <button type="button" :class="{ active: selectedCategory === 'Tất cả' }" @click="selectedCategory = 'Tất cả'">Tất cả</button>
                        <button v-for="category in categories" :key="category" type="button" :class="{ active: selectedCategory === category }" @click="selectedCategory = category">{{ category }}</button>
                    </nav>

                    <p v-if="loading" class="blog-status" role="status">Đang tải bài viết…</p>
                    <p v-else-if="error && !posts.length" class="blog-status blog-status-error" role="alert">{{ error }}</p>
                    <div v-else-if="visiblePosts.length" class="blog-post-list">
                        <article v-for="post in visiblePosts" :key="post.slug" class="blog-post-row">
                            <RouterLink class="blog-post-thumb" :to="`/blog/${post.slug}`" :aria-label="`Đọc bài: ${post.title}`">
                                <img :src="post.cover_image_url || defaultImage" :alt="post.cover_image_alt || post.title" loading="lazy" />
                            </RouterLink>
                            <div class="blog-post-copy">
                                <div class="blog-card-meta"><span v-for="category in post.categories" :key="category">{{ category }}</span><time v-if="post.published_at" :datetime="post.published_at">{{ formatDate(post.published_at) }}</time></div>
                                <h3><RouterLink :to="`/blog/${post.slug}`">{{ post.title }}</RouterLink></h3>
                                <p>{{ post.excerpt }}</p>
                                <RouterLink class="blog-read-link" :to="`/blog/${post.slug}`">ĐỌC BÀI VIẾT <span aria-hidden="true">→︎</span></RouterLink>
                            </div>
                        </article>
                    </div>
                    <p v-else class="blog-status">{{ selectedCategory === 'Tất cả' ? 'Chưa có bài viết được xuất bản.' : 'Chủ đề này chưa có bài viết.' }}</p>
                    <p v-if="error && posts.length" class="blog-inline-error" role="alert">{{ error }}</p>
                    <div v-if="nextPage && selectedCategory === 'Tất cả'" class="blog-more"><button class="button button-outline" type="button" :disabled="loadingMore" @click="loadMore">{{ loadingMore ? 'Đang tải…' : 'Xem thêm bài viết' }}</button></div>
                </div>

                <aside class="blog-sidebar" aria-label="Khám phá thêm">
                    <section class="blog-sidebar-card blog-newsletter-card">
                        <span class="blog-sidebar-mark" aria-hidden="true">✳︎</span>
                        <p class="blog-eyebrow">HẸN GẶP BẠN TRÊN SÂN</p>
                        <h2>Chơi một trận nhé?</h2>
                        <p>Chọn khung giờ phù hợp và khám phá không khí pickleball tại BALLBURN.</p>
                        <BaseButton to="/booking">Đặt sân ngay <span aria-hidden="true">↗︎</span></BaseButton>
                    </section>
                    <section v-if="categories.length" class="blog-sidebar-tags">
                        <h2>Chủ đề nổi bật</h2>
                        <div class="blog-tag-list">
                            <button v-for="category in categories" :key="category" type="button" @click="selectedCategory = category">{{ category }}</button>
                        </div>
                    </section>
                    <RouterLink class="blog-sidebar-feature" to="/about">
                        <img :src="'/images/pickleball/player-action.jpg'" alt="Người chơi pickleball trên sân BALLBURN" loading="lazy" />
                        <span><strong>Chơi. Kết nối.<br />Lặp lại.</strong><small>Khám phá BALLBURN <span aria-hidden="true">↗︎</span></small></span>
                    </RouterLink>
                </aside>
            </div>
        </section>

        <section class="blog-cta"><p class="blog-eyebrow">SẴN SÀNG LÊN SÂN?</p><h2>Kiến thức tốt.<br /><em>Trận đấu vui hơn.</em></h2><BaseButton to="/booking">Đặt sân tại BALLBURN <span aria-hidden="true">↗︎</span></BaseButton></section>
    </main>
</template>
