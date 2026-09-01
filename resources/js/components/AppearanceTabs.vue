<script setup lang="ts">
import { Check, Monitor, Moon, Sun } from '@lucide/vue';
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
            <span class="appearance-option-icon" aria-hidden="true">
                <component :is="Icon" :size="21" stroke-width="2" />
            </span>

            <span class="appearance-option-copy">
                <strong>{{ label }}</strong>
                <small>{{ description }}</small>
            </span>

            <span class="appearance-radio" :class="{ checked: appearance === value }" aria-hidden="true">
                <Check v-if="appearance === value" :size="12" stroke-width="3" />
            </span>
        </button>
    </div>
</template>

<style scoped>
.appearance-picker {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    width: 100%;
}

.appearance-option {
    position: relative;
    min-width: 0;
    min-height: 82px;
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 13px 14px;
    border: 1px solid #e7eaf0 !important;
    border-radius: 16px !important;
    outline: none !important;
    appearance: none;
    color: #344054 !important;
    background: #ffffff !important;
    box-shadow: 0 2px 7px rgba(15, 23, 42, .035) !important;
    text-align: left;
    cursor: pointer;
    transition: border-color .2s ease, background .2s ease, box-shadow .2s ease, transform .2s ease;
}

.appearance-option::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    pointer-events: none;
    opacity: 0;
    box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .08);
    transition: opacity .2s ease;
}

.appearance-option:hover {
    border-color: #c9d8f7 !important;
    background: #fbfdff !important;
    box-shadow: 0 8px 22px rgba(15, 23, 42, .07) !important;
    transform: translateY(-1px);
}

.appearance-option:hover::after,
.appearance-option.active::after {
    opacity: 1;
}

.appearance-option.active {
    border-color: #8fb2ff !important;
    background: linear-gradient(135deg, #f8fbff 0%, #f1f6ff 100%) !important;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .055), 0 10px 25px rgba(37, 99, 235, .09) !important;
}

.appearance-option-icon {
    width: 46px;
    height: 46px;
    flex: 0 0 46px;
    display: grid;
    place-items: center;
    border: 1px solid #e7ebf2;
    border-radius: 14px;
    color: #667085;
    background: #f8fafc;
    transition: all .2s ease;
}

.appearance-option.active .appearance-option-icon {
    border-color: #d5e2ff;
    color: #2563eb;
    background: #ffffff;
    box-shadow: 0 5px 12px rgba(37, 99, 235, .09);
}

.appearance-option-copy {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.appearance-option-copy strong {
    color: #1d2939;
    font-size: .82rem;
    line-height: 1.1;
    font-weight: 800;
    letter-spacing: -.01em;
}

.appearance-option-copy small {
    overflow: hidden;
    color: #98a2b3;
    font-size: .65rem;
    font-weight: 600;
    line-height: 1.2;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.appearance-radio {
    width: 24px;
    height: 24px;
    flex: 0 0 24px;
    display: grid;
    place-items: center;
    border: 1.5px solid #d6dbe4;
    border-radius: 50%;
    color: #ffffff;
    background: #ffffff;
    transition: all .2s ease;
}

.appearance-radio.checked {
    border-color: #2563eb;
    background: #2563eb;
    box-shadow: 0 3px 8px rgba(37, 99, 235, .2);
}

.appearance-option:focus-visible {
    box-shadow: 0 0 0 4px rgba(37, 99, 235, .12) !important;
}

@media (max-width: 820px) {
    .appearance-picker {
        grid-template-columns: 1fr;
    }
}
</style>
