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
            <span class="appearance-icon"><component :is="Icon" :size="21" stroke-width="1.9" /></span>
            <span class="appearance-copy"><strong>{{ label }}</strong><small>{{ description }}</small></span>
            <span class="appearance-state" aria-hidden="true"><Check v-if="appearance === value" :size="13" stroke-width="3" /><span v-else class="state-dot" /></span>
        </button>
    </div>
</template>

<style scoped>
.appearance-picker{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;width:100%}
.appearance-option{position:relative;min-width:0;min-height:78px;display:flex;align-items:center;gap:12px;padding:12px 14px;border:1px solid #e7eaf0!important;border-radius:16px!important;outline:none!important;appearance:none;color:#344054!important;background:#fff!important;box-shadow:0 3px 10px rgba(16,24,40,.035)!important;text-align:left;cursor:pointer;transition:border-color .2s ease,background .2s ease,box-shadow .2s ease,transform .2s ease}
.appearance-option:hover{border-color:#c8d2e2!important;background:#fcfdff!important;box-shadow:0 9px 24px rgba(16,24,40,.075)!important;transform:translateY(-2px)}
.appearance-option.active{border-color:#7fa5ff!important;background:linear-gradient(135deg,#f8fbff 0%,#eef4ff 100%)!important;box-shadow:0 0 0 3px rgba(37,99,235,.055),0 10px 25px rgba(37,99,235,.1)!important}
.appearance-option.active::before{content:'';position:absolute;left:14px;right:14px;bottom:0;height:3px;border-radius:4px 4px 0 0;background:#2563eb}
.appearance-icon{width:46px;height:46px;flex:0 0 46px;display:grid;place-items:center;border:1px solid #e5eaf2;border-radius:14px;color:#64748b;background:#f8fafc;transition:.2s ease}
.appearance-option.active .appearance-icon{color:#2563eb;border-color:#d5e2ff;background:#fff;box-shadow:0 5px 13px rgba(37,99,235,.1)}
.appearance-copy{min-width:0;flex:1;display:flex;flex-direction:column;gap:4px}.appearance-copy strong{color:#172033;font-size:.82rem;font-weight:800;line-height:1.1}.appearance-copy small{overflow:hidden;color:#98a2b3;font-size:.64rem;font-weight:600;line-height:1.2;white-space:nowrap;text-overflow:ellipsis}
.appearance-state{width:24px;height:24px;flex:0 0 24px;display:grid;place-items:center;border:1.5px solid #d6dbe4;border-radius:50%;color:#fff;background:#fff;transition:.2s ease}.state-dot{width:6px;height:6px;border-radius:50%;background:#d5dae3}.appearance-option.active .appearance-state{border-color:#2563eb;background:#2563eb;box-shadow:0 3px 9px rgba(37,99,235,.22)}
.appearance-option:focus-visible{box-shadow:0 0 0 4px rgba(37,99,235,.13)!important}@media(max-width:760px){.appearance-picker{grid-template-columns:1fr}}
</style>
