<script setup lang="ts">
import { Monitor, Moon, Sun, Check } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Sáng', description: 'Giao diện sáng' },
    { value: 'dark', Icon: Moon, label: 'Tối', description: 'Giao diện tối' },
    { value: 'system', Icon: Monitor, label: 'Hệ thống', description: 'Theo thiết bị' },
] as const;
</script>

<template>
    <div class="appearance-picker" role="radiogroup" aria-label="Chế độ hiển thị">
        <button
            v-for="{ value, Icon, label, description } in tabs"
            :key="value"
            type="button"
            class="appearance-option"
            :class="{ active: appearance === value }"
            :aria-checked="appearance === value"
            role="radio"
            @click="updateAppearance(value)"
        >
            <span class="appearance-option-icon">
                <component :is="Icon" :size="19" stroke-width="1.9" />
            </span>
            <span class="appearance-option-copy">
                <strong>{{ label }}</strong>
                <small>{{ description }}</small>
            </span>
            <span class="appearance-check" aria-hidden="true">
                <Check v-if="appearance === value" :size="13" stroke-width="2.5" />
            </span>
        </button>
    </div>
</template>

<style scoped>
.appearance-picker {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    width: 100%;
}

.appearance-option {
    position: relative;
    min-width: 0;
    min-height: 68px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 11px;
    border: 1px solid #e4e7ec !important;
    border-radius: 12px !important;
    outline: none !important;
    appearance: none;
    color: #475467 !important;
    background: #fff !important;
    box-shadow: 0 1px 2px rgba(16,24,40,.03) !important;
    text-align: left;
    cursor: pointer;
    transition: border-color .16s ease, background-color .16s ease, box-shadow .16s ease, transform .16s ease;
}

.appearance-option:hover {
    border-color: #b2c6ff !important;
    background: #f8fbff !important;
    transform: translateY(-1px);
}

.appearance-option.active {
    border-color: #93b4ff !important;
    background: #f5f8ff !important;
    box-shadow: 0 5px 16px rgba(37,99,235,.08) !important;
}

.appearance-option-icon {
    width: 38px;
    height: 38px;
    flex: 0 0 38px;
    display: grid;
    place-items: center;
    border: 1px solid #eaecf0;
    border-radius: 10px;
    color: #667085;
    background: #f8fafc;
    transition: .16s ease;
}

.appearance-option.active .appearance-option-icon {
    border-color: #dbeafe;
    color: #2563eb;
    background: #fff;
}

.appearance-option-copy {
    min-width: 0;
    flex: 1;
}

.appearance-option-copy strong,
.appearance-option-copy small {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.appearance-option-copy strong {
    color: #344054;
    font-size: .76rem;
    font-weight: 800;
}

.appearance-option-copy small {
    margin-top: 3px;
    color: #98a2b3;
    font-size: .62rem;
    font-weight: 600;
}

.appearance-check {
    width: 20px;
    height: 20px;
    flex: 0 0 20px;
    display: grid;
    place-items: center;
    border: 1px solid #d0d5dd;
    border-radius: 50%;
    color: #fff;
    background: #fff;
}

.appearance-option.active .appearance-check {
    border-color: #2563eb;
    background: #2563eb;
}

.appearance-option:focus-visible {
    box-shadow: 0 0 0 3px rgba(37,99,235,.12) !important;
}

@media (max-width: 720px) {
    .appearance-picker { grid-template-columns: 1fr; }
}
</style>
