import { createApp } from 'vue';
import App from './App.vue';
import router from './router/index.js';
import './assets/styles/global.css';

const app = createApp(App);

function showBookingFailure(error) {
    console.error('Booking page failed to initialize.', error);

    if (!window.location.pathname.startsWith('/booking')) return;

    const root = document.getElementById('app');
    if (!root) return;

    const message = document.createElement('main');
    message.className = 'app-container';
    message.setAttribute('role', 'alert');
    message.style.cssText = 'min-height:45vh;display:grid;align-content:center;justify-items:start;gap:14px;padding-block:48px;color:var(--color-ink,#171717)';

    const heading = document.createElement('h1');
    heading.textContent = 'Không thể tải màn hình đặt sân';

    const description = document.createElement('p');
    description.textContent = 'Đã xảy ra lỗi khi mở lịch sân. Hãy tải lại trang để thử lại.';

    const retry = document.createElement('button');
    retry.type = 'button';
    retry.className = 'button button--primary';
    retry.textContent = 'Tải lại trang';
    retry.addEventListener('click', () => window.location.reload());

    message.append(heading, description, retry);
    root.replaceChildren(message);
}

app.config.errorHandler = showBookingFailure;
router.onError(showBookingFailure);

app.use(router).mount('#app');
