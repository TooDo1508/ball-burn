<script setup>
import { ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { updateBlogSeo } from '../lib/blogSeo.js';
import { getServerPageData } from '../lib/serverPageData.js';
import '../assets/styles/blog.css';

const route = useRoute();
const serverData = getServerPageData('article');
const post = ref(null);
const loading = ref(true);
const error = ref('');

watch(() => route.params.slug, loadPost, { immediate: true });

async function loadPost(slug) {
    if (serverData?.post?.slug === slug) {
        post.value = serverData.post;
        updateBlogSeo({
            title: serverData.seo.title,
            description: serverData.seo.description,
            image: serverData.post.cover_image_url,
            canonical: serverData.seo.canonical,
            type: 'article',
            publishedAt: serverData.post.published_at,
            modifiedAt: serverData.seo.modified_at,
            structuredData: serverData.seo.structured_data,
        });
        loading.value = false;
        return;
    }

    post.value = null;
    error.value = '';
    loading.value = true;
    try {
        const response = await fetch(`/api/blog/posts/${encodeURIComponent(slug)}`, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error(response.status === 404 ? 'Không tìm thấy bài viết này.' : 'Không tải được bài viết.');
        const result = await response.json();
        post.value = result.data;
        updateBlogSeo({
            title: result.data.seo.title,
            description: result.data.seo.description,
            image: result.data.cover_image_url,
            canonical: `${window.location.origin}/blog/${result.data.slug}`,
            type: 'article',
            publishedAt: result.data.published_at,
            modifiedAt: result.data.updated_at,
            structuredData: result.data.seo.structured_data,
        });
    } catch (loadError) {
        error.value = loadError instanceof Error ? loadError.message : 'Không tải được bài viết.';
    } finally {
        loading.value = false;
    }
}

function formatDate(value) {
    return value ? new Intl.DateTimeFormat('vi-VN', { dateStyle: 'long' }).format(new Date(value)) : '';
}
</script>

<template>
    <main class="blog-article-page">
        <p v-if="loading" class="blog-status">Đang tải bài viết…</p>
        <section v-else-if="error" class="blog-not-found"><p class="blog-eyebrow">BALLBURN · TIN TỨC</p><h1>{{ error }}</h1><RouterLink class="blog-read-link" to="/blog">←︎ VỀ TẤT CẢ BÀI VIẾT</RouterLink></section>
        <article v-else-if="post" class="blog-article">
            <nav class="blog-breadcrumb" aria-label="Điều hướng"><a href="/">Trang chủ</a><span aria-hidden="true">/</span><RouterLink to="/blog">Tin tức</RouterLink><span aria-hidden="true">/</span><span>{{ post.title }}</span></nav>
            <header class="blog-article-header">
                <div class="blog-card-meta"><span v-for="category in post.categories" :key="category">{{ category }}</span><time v-if="post.published_at" :datetime="post.published_at">{{ formatDate(post.published_at) }}</time></div>
                <h1>{{ post.title }}</h1>
                <p class="blog-article-excerpt">{{ post.excerpt }}</p>
                <p class="blog-article-author">Biên tập bởi {{ post.author }}<span v-if="post.published_at"> · {{ formatDate(post.published_at) }}</span></p>
            </header>
            <img v-if="post.cover_image_url" class="blog-article-cover" :src="post.cover_image_url" :alt="post.cover_image_alt || post.title" fetchpriority="high" />
            <div class="blog-article-content" v-html="post.body" />
            <footer class="blog-article-footer"><p>Chia sẻ bài viết với bạn bè cùng chơi pickleball.</p><RouterLink class="blog-read-link" to="/blog">←︎ XEM CÁC BÀI VIẾT KHÁC</RouterLink></footer>
        </article>
    </main>
</template>
