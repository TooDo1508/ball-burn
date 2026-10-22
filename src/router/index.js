import { createRouter, createWebHistory } from 'vue-router';
import HomePage from '../pages/HomePage.vue';
import AboutPage from '../pages/AboutPage.vue';
import BlogPage from '../pages/BlogPage.vue';
import BookingPage from '../pages/BookingPage.vue';
import ContactPage from '../pages/ContactPage.vue';
import PublicLayout from '../layouts/PublicLayout.vue';

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
                { path: 'booking', name: 'booking', component: BookingPage },
                { path: 'contact', name: 'contact', component: ContactPage },
            ],
        },
    ],
});

export default router;
