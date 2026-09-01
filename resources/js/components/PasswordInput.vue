<script setup lang="ts">
import { Eye, EyeOff } from '@lucide/vue';
import { ref, useTemplateRef } from 'vue';
import type { HTMLAttributes } from 'vue';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();

const showPassword = ref(false);
const inputRef = useTemplateRef('inputRef');

defineExpose({
    $el: inputRef,
    focus: () => inputRef.value?.$el?.focus(),
});
</script>

<template>
    <div class="password-input-wrap">
        <Input
            ref="inputRef"
            :type="showPassword ? 'text' : 'password'"
            :class="cn('password-input-field pr-12', props.class)"
            v-bind="$attrs"
        />
        <button
            type="button"
            class="password-visibility-button"
            @click="showPassword = !showPassword"
            :aria-label="showPassword ? 'Ẩn mật khẩu' : 'Hiển thị mật khẩu'"
            :aria-pressed="showPassword"
            tabindex="-1"
        >
            <EyeOff v-if="showPassword" :size="18" stroke-width="1.9" />
            <Eye v-else :size="18" stroke-width="1.9" />
        </button>
    </div>
</template>

<style scoped>
.password-input-wrap {
    position: relative;
    width: 100%;
}

.password-input-field {
    width: 100%;
    min-height: 42px;
    padding-right: 50px !important;
    border-radius: 10px !important;
}

.password-visibility-button {
    position: absolute;
    top: 50%;
    right: 5px;
    z-index: 2;
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    transform: translateY(-50%);
    border: 0 !important;
    border-radius: 8px !important;
    outline: none !important;
    appearance: none;
    color: #98a2b3;
    background: transparent !important;
    box-shadow: none !important;
    cursor: pointer;
    transition: color .16s ease, background-color .16s ease, transform .16s ease;
}

.password-visibility-button:hover {
    color: #2563eb;
    background: #eff6ff !important;
}

.password-visibility-button:active {
    transform: translateY(-50%) scale(.94);
}

.password-visibility-button:focus-visible {
    color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .12) !important;
}
</style>
