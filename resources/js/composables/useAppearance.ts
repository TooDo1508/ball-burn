import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value: Appearance) => void;
};

const appearance = ref<Appearance>('system');
const systemIsDark = ref(false);
let themeListenersInstalled = false;

function isAppearance(value: unknown): value is Appearance {
    return value === 'light' || value === 'dark' || value === 'system';
}

function getStoredAppearance(): Appearance | null {
    if (typeof window === 'undefined') return null;

    try {
        const value = window.localStorage.getItem('appearance');
        return isAppearance(value) ? value : null;
    } catch {
        return null;
    }
}

function prefersDark(): boolean {
    if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

function applyTheme(value: Appearance): void {
    if (typeof document === 'undefined') return;

    const dark = value === 'dark' || (value === 'system' && systemIsDark.value);
    const root = document.documentElement;

    root.classList.toggle('dark', dark);
    root.style.colorScheme = dark ? 'dark' : 'light';
}

export function updateTheme(value: Appearance): void {
    if (!isAppearance(value)) return;

    systemIsDark.value = prefersDark();
    applyTheme(value);
}

function setCookie(name: string, value: string, days = 365): void {
    if (typeof document === 'undefined') return;

    const maxAge = days * 24 * 60 * 60;
    const secure = window.location.protocol === 'https:' ? ';Secure' : '';
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax${secure}`;
}

function installThemeListeners(): void {
    if (typeof window === 'undefined' || themeListenersInstalled) return;

    const media = window.matchMedia('(prefers-color-scheme: dark)');
    systemIsDark.value = media.matches;
    media.addEventListener('change', (event) => {
        systemIsDark.value = event.matches;
        if (appearance.value === 'system') applyTheme('system');
    });

    window.addEventListener('storage', (event) => {
        if (event.key !== 'appearance') return;

        appearance.value = isAppearance(event.newValue) ? event.newValue : 'system';
        systemIsDark.value = prefersDark();
        setCookie('appearance', appearance.value);
        applyTheme(appearance.value);
    });

    themeListenersInstalled = true;
}

export function initializeTheme(): void {
    if (typeof window === 'undefined') return;

    installThemeListeners();
    appearance.value = getStoredAppearance() ?? 'system';
    systemIsDark.value = prefersDark();
    applyTheme(appearance.value);
}

const resolvedAppearance = computed<ResolvedAppearance>(() => {
    if (appearance.value === 'system') {
        return systemIsDark.value ? 'dark' : 'light';
    }

    return appearance.value;
});

export function useAppearance(): UseAppearanceReturn {
    function updateAppearance(value: Appearance): void {
        if (!isAppearance(value)) return;

        appearance.value = value;
        systemIsDark.value = prefersDark();

        try {
            window.localStorage.setItem('appearance', value);
        } catch {
            // The current page still receives the selected appearance if storage is unavailable.
        }

        setCookie('appearance', value);
        applyTheme(value);
    }

    return { appearance, resolvedAppearance, updateAppearance };
}
