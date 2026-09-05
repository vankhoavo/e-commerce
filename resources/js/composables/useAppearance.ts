import type { ComputedRef, Ref } from 'vue';
import { computed, onMounted, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };
export type UseAppearanceReturn = { appearance: Ref<Appearance>; resolvedAppearance: ComputedRef<ResolvedAppearance>; updateAppearance: (value: Appearance) => void };

export function updateTheme(value: Appearance): void {
    if (typeof window === 'undefined') return;
    if (value === 'system') {
        const mediaQueryList = window.matchMedia('(prefers-color-scheme: dark)');
        document.documentElement.classList.toggle('dark', mediaQueryList.matches);
    } else {
        document.documentElement.classList.toggle('dark', value === 'dark');
    }
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') return;
    document.cookie = `${name}=${value};path=/;max-age=${days * 24 * 60 * 60};SameSite=Lax`;
};

const mediaQuery = () => typeof window === 'undefined' ? null : window.matchMedia('(prefers-color-scheme: dark)');
const prefersDark = (): boolean => typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches;

const handleSystemThemeChange = () => {
    const currentAppearance = localStorage.getItem('appearance') as Appearance | null;
    updateTheme(currentAppearance || 'light');
};

export function initializeTheme(): void {
    if (typeof window === 'undefined') return;
    const savedAppearance = localStorage.getItem('appearance') as Appearance | null;
    // Preserve TechStore's original light palette until the user explicitly chooses another mode.
    updateTheme(savedAppearance || 'light');
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

const appearance = ref<Appearance>('light');

export function useAppearance(): UseAppearanceReturn {
    onMounted(() => {
        const savedAppearance = localStorage.getItem('appearance') as Appearance | null;
        if (savedAppearance) appearance.value = savedAppearance;
    });

    const resolvedAppearance = computed<ResolvedAppearance>(() => {
        if (appearance.value === 'system') return prefersDark() ? 'dark' : 'light';
        return appearance.value;
    });

    function updateAppearance(value: Appearance) {
        appearance.value = value;
        localStorage.setItem('appearance', value);
        setCookie('appearance', value);
        updateTheme(value);
    }

    return { appearance, resolvedAppearance, updateAppearance };
}
