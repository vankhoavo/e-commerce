<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { KeyRound, Plus } from '@lucide/vue';
import type { Passkey } from '@/types/auth';
import Heading from '@/components/Heading.vue';
import PasskeyItem from '@/components/PasskeyItem.vue';
import PasskeyRegister from '@/components/PasskeyRegister.vue';
import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';

export type Props = {
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
};

withDefaults(defineProps<Props>(), {
    canManagePasskeys: false,
    passkeys: () => [],
});

const handleDelete = (id: number, onError: () => void) => {
    router.delete(destroy.url(id), {
        preserveScroll: true,
        onError,
    });
};

const handleRegisterSuccess = () => {
    router.reload();
};
</script>

<template>
    <div v-if="canManagePasskeys" class="passkey-manager">
        <div class="passkey-heading">
            <div>
                <h3>Passkey</h3>
                <p>Đăng nhập nhanh và an toàn mà không cần nhập mật khẩu.</p>
            </div>
        </div>

        <div class="passkey-list" :class="{ 'has-items': passkeys.length }">
            <template v-if="passkeys.length">
                <PasskeyItem
                    v-for="passkey in passkeys"
                    :key="passkey.id"
                    :passkey="passkey"
                    @remove="handleDelete"
                />
            </template>

            <div v-else class="passkey-empty">
                <div class="passkey-empty-icon">
                    <KeyRound :size="25" stroke-width="1.9" />
                </div>
                <h4>Chưa có Passkey</h4>
                <p>Thêm Passkey để đăng nhập bằng vân tay, khuôn mặt hoặc thiết bị tin cậy.</p>
            </div>
        </div>

        <div class="passkey-action">
            <PasskeyRegister @success="handleRegisterSuccess" />
        </div>
    </div>
</template>

<style scoped>
.passkey-manager {
    width: 100%;
}

.passkey-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 16px;
    border-bottom: 1px solid #eaecf0;
}

.passkey-heading h3 {
    margin: 0;
    color: #101828;
    font-size: 1.35rem;
    line-height: 1.2;
    font-weight: 850;
    letter-spacing: -.035em;
}

.passkey-heading p {
    margin: 5px 0 0;
    color: #667085;
    font-size: .75rem;
}

.passkey-list {
    margin-top: 16px;
    min-height: 126px;
    overflow: hidden;
    border: 1px solid #e4e7ec;
    border-radius: 12px;
    background: #fbfcfe;
}

.passkey-list.has-items {
    background: #fff;
}

.passkey-empty {
    min-height: 126px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    text-align: center;
}

.passkey-empty-icon {
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    margin-bottom: 10px;
    border: 1px solid #e9d5ff;
    border-radius: 13px;
    color: #7c3aed;
    background: #faf5ff;
    box-shadow: 0 5px 14px rgba(124, 58, 237, .08);
}

.passkey-empty h4 {
    margin: 0;
    color: #344054;
    font-size: .88rem;
    font-weight: 800;
}

.passkey-empty p {
    max-width: 420px;
    margin: 5px 0 0;
    color: #98a2b3;
    font-size: .7rem;
    line-height: 1.55;
}

.passkey-action {
    margin-top: 14px;
}

:deep(.passkey-action > button),
:deep(.passkey-action .passkey-add-button) {
    min-height: 38px;
    padding: 0 15px;
    border: 1px solid #d0d5dd !important;
    border-radius: 9px !important;
    color: #344054 !important;
    background: #fff !important;
    box-shadow: 0 1px 2px rgba(16, 24, 40, .04) !important;
    font-size: .75rem !important;
    font-weight: 750 !important;
}

:deep(.passkey-action > button:hover),
:deep(.passkey-action .passkey-add-button:hover) {
    border-color: #a4bcfd !important;
    color: #1d4ed8 !important;
    background: #f8fbff !important;
}
</style>
