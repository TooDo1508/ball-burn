import { createRouter, createWebHistory } from 'vue-router';
const HomePage = () => import('../pages/HomePage.vue');
const AboutPage = () => import('../pages/AboutPage.vue');
const BlogPage = () => import('../pages/BlogPage.vue');
const BlogArticlePage = () => import('../pages/BlogArticlePage.vue');
const BookingPage = () => import('../pages/BookingPage.vue');
const ContactPage = () => import('../pages/ContactPage.vue');
const PublicLayout = () => import('../layouts/PublicLayout.vue');

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            component: PublicLayout,
            children: [
                { path: '', name: 'home', component: HomePage },
                { path: 'about', name: 'about', component: AboutPage },
                { path: 'blog', name: 'blog', component: BlogPage },
                { path: 'blog/:slug', name: 'blog-article', component: BlogArticlePage },
                { path: 'booking', name: 'booking', component: BookingPage },
                { path: 'contact', name: 'contact', component: ContactPage },
            ],
        },
    ],
});

export default router;
