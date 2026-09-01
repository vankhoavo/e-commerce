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
            <span class="appearance-icon"><component :is="Icon" :size="17" stroke-width="2" /></span>
            <span class="appearance-copy"><strong>{{ label }}</strong><small>{{ description }}</small></span>
            <span v-if="appearance === value" class="appearance-check" aria-hidden="true"><Check :size="11" stroke-width="3" /></span>
        </button>
    </div>
</template>

<style scoped>
.appearance-picker{display:flex;align-items:center;gap:8px;width:100%;max-width:570px}
.appearance-option{position:relative;flex:1 1 0;min-width:0;height:56px;display:flex;align-items:center;gap:9px;padding:7px 10px;border:1px solid #e7eaf0!important;border-radius:12px!important;outline:none!important;appearance:none;color:#344054!important;background:#fff!important;box-shadow:0 1px 4px rgba(15,23,42,.035)!important;text-align:left;cursor:pointer;transition:border-color .18s ease,background .18s ease,box-shadow .18s ease,transform .18s ease}
.appearance-option:hover{border-color:#cbd8ef!important;background:#fbfdff!important;box-shadow:0 5px 14px rgba(15,23,42,.06)!important;transform:translateY(-1px)}
.appearance-option.active{border-color:#79a5ff!important;background:#f5f8ff!important;box-shadow:0 0 0 2px rgba(37,99,235,.06),0 5px 14px rgba(37,99,235,.07)!important}
.appearance-option.active::after{content:'';position:absolute;left:12px;right:12px;bottom:-1px;height:2px;border-radius:2px;background:#2563eb}
.appearance-icon{width:32px;height:32px;flex:0 0 32px;display:grid;place-items:center;border:1px solid #e8edf4;border-radius:9px;color:#667085;background:#f8fafc}
.appearance-option.active .appearance-icon{border-color:#d5e2ff;color:#2563eb;background:#fff}
.appearance-copy{min-width:0;display:flex;flex:1;flex-direction:column;gap:2px}.appearance-copy strong{color:#1d2939;font-size:.74rem;line-height:1.1;font-weight:800}.appearance-copy small{overflow:hidden;color:#98a2b3;font-size:.56rem;font-weight:600;line-height:1.1;white-space:nowrap;text-overflow:ellipsis}
.appearance-check{width:18px;height:18px;flex:0 0 18px;display:grid;place-items:center;border-radius:50%;color:#fff;background:#2563eb;box-shadow:0 2px 5px rgba(37,99,235,.18)}
.appearance-option:focus-visible{box-shadow:0 0 0 3px rgba(37,99,235,.13)!important}
@media(max-width:560px){.appearance-picker{max-width:none;gap:6px}.appearance-option{padding:7px 8px;gap:6px}.appearance-icon{width:29px;height:29px;flex-basis:29px}.appearance-copy small{display:none}.appearance-check{width:17px;height:17px;flex-basis:17px}}
@media(max-width:390px){.appearance-picker{flex-direction:column;align-items:stretch}.appearance-option{flex:none;width:100%}}
</style>
