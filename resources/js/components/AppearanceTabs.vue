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
        <button v-for="{ value, Icon, label, description } in tabs" :key="value" type="button" class="appearance-option" :class="{ active: appearance === value }" :aria-checked="appearance === value" role="radio" @click="updateAppearance(value)">
            <span class="appearance-option-icon"><component :is="Icon" :size="20" stroke-width="2" /></span>
            <span class="appearance-option-copy"><strong>{{ label }}</strong><small>{{ description }}</small></span>
            <span class="appearance-check" aria-hidden="true"><Check v-if="appearance === value" :size="12" stroke-width="3" /></span>
        </button>
    </div>
</template>

<style scoped>
.appearance-picker{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;width:100%}
.appearance-option{position:relative;display:flex;align-items:center;gap:12px;min-height:74px;padding:11px 12px;border:1px solid #e5e7eb!important;border-radius:15px!important;outline:none!important;appearance:none;color:#344054!important;background:#fff!important;box-shadow:0 2px 6px rgba(16,24,40,.035)!important;text-align:left;cursor:pointer;transition:all .2s ease}
.appearance-option:hover{border-color:#bfd3ff!important;background:#fafcff!important;box-shadow:0 8px 20px rgba(37,99,235,.08)!important;transform:translateY(-2px)}
.appearance-option.active{border-color:#6f9cff!important;background:linear-gradient(135deg,#f7faff,#eef5ff)!important;box-shadow:0 0 0 3px rgba(37,99,235,.07),0 9px 22px rgba(37,99,235,.09)!important}
.appearance-option-icon{width:42px;height:42px;flex:0 0 42px;display:grid;place-items:center;border:1px solid #e8edf5;border-radius:12px;color:#667085;background:#f8fafc;transition:all .2s ease}
.appearance-option.active .appearance-option-icon{border-color:#cfe0ff;color:#2563eb;background:#fff;box-shadow:0 3px 9px rgba(37,99,235,.08)}
.appearance-option-copy{min-width:0;flex:1;display:flex;flex-direction:column;gap:4px}.appearance-option-copy strong{color:#1d2939;font-size:.78rem;font-weight:850;line-height:1.1}.appearance-option-copy small{color:#98a2b3;font-size:.62rem;font-weight:600;line-height:1.15;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.appearance-check{width:22px;height:22px;flex:0 0 22px;display:grid;place-items:center;border:1.5px solid #d0d5dd;border-radius:50%;color:#fff;background:#fff;transition:all .2s ease}.appearance-option.active .appearance-check{border-color:#2563eb;background:#2563eb;box-shadow:0 2px 6px rgba(37,99,235,.22)}
.appearance-option:focus-visible{box-shadow:0 0 0 4px rgba(37,99,235,.13)!important}@media(max-width:720px){.appearance-picker{grid-template-columns:1fr}}
</style>
