<script setup lang="ts">
import { usePasskeyRegister } from '@laravel/passkeys/vue';
import { KeyRound, ShieldCheck, X } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';

const emit = defineEmits<{ success: [] }>();

const getDefaultPasskeyName = () => {
    const ua = navigator.userAgent;
    const browser = [{ pattern: /Edg|Edge/, name: 'Edge' }, { pattern: /OPR|Opera|OPiOS/, name: 'Opera' }, { pattern: /Firefox|FxiOS/, name: 'Firefox' }, { pattern: /Chrome|CriOS/, name: 'Chrome' }, { pattern: /Safari/, name: 'Safari' }].find(({ pattern }) => pattern.test(ua))?.name;
    const os = [{ pattern: /iPhone/, name: 'iPhone' }, { pattern: /iPad|Macintosh(?=.*Mobile)/, name: 'iPad' }, { pattern: /Android/, name: 'Android' }, { pattern: /Mac/, name: 'Mac' }, { pattern: /Windows/, name: 'Windows' }].find(({ pattern }) => pattern.test(ua))?.name;
    return [browser, os].filter(Boolean).join(' trên ') || '';
};

const name = ref(getDefaultPasskeyName());
const showForm = ref(false);
const { register, isLoading, error, isSupported } = usePasskeyRegister({
    onSuccess: () => { name.value = ''; showForm.value = false; emit('success'); },
});

const handleSubmit = async (event: Event) => {
    event.preventDefault();
    if (name.value.trim()) await register(name.value.trim());
};
const handleCancel = () => { showForm.value = false; name.value = getDefaultPasskeyName(); };
</script>

<template>
    <div v-if="!isSupported" class="passkey-unsupported"><i class="bi bi-exclamation-circle" /> Trình duyệt này chưa hỗ trợ Passkey.</div>
    <>
        <button v-if="isSupported && !showForm" type="button" class="passkey-add-btn" @click="showForm = true"><KeyRound :size="15" /> Thêm Passkey</button>
        <Teleport to="body">
            <Transition name="passkey-modal">
                <div v-if="showForm" class="passkey-modal-backdrop" @click.self="handleCancel">
                    <section class="passkey-modal" role="dialog" aria-modal="true" aria-labelledby="passkey-title">
                        <button type="button" class="passkey-close" aria-label="Đóng" @click="handleCancel"><X :size="17" /></button>
                        <div class="passkey-modal-icon"><KeyRound :size="25" /></div>
                        <span class="passkey-eyebrow">ĐĂNG NHẬP AN TOÀN</span>
                        <h3 id="passkey-title">Thêm Passkey</h3>
                        <p class="passkey-modal-desc">Tạo phương thức đăng nhập nhanh bằng vân tay, khuôn mặt, mã PIN hoặc thiết bị tin cậy.</p>
                        <div class="passkey-benefits"><span><ShieldCheck :size="15" /> Bảo mật cao</span><span><i class="bi bi-lightning-charge-fill" /> Đăng nhập nhanh</span></div>
                        <form @submit="handleSubmit">
                            <label for="passkey-name">Tên Passkey</label>
                            <input id="passkey-name" v-model="name" type="text" autocomplete="off" placeholder="Ví dụ: Chrome trên Windows" autofocus />
                            <small>Đặt tên để bạn dễ nhận biết thiết bị này sau này.</small>
                            <InputError v-if="error" :message="error" />
                            <div class="passkey-modal-actions"><button type="button" class="passkey-cancel" :disabled="isLoading" @click="handleCancel">Hủy</button><button type="submit" class="passkey-confirm" :disabled="isLoading || !name.trim()"><span v-if="isLoading" class="spinner-border spinner-border-sm" /><KeyRound v-else :size="15" />{{ isLoading ? 'Đang tạo...' : 'Tạo Passkey' }}</button></div>
                        </form>
                    </section>
                </div>
            </Transition>
        </Teleport>
    </>
</template>

<style scoped>
.passkey-add-btn{display:inline-flex;align-items:center;gap:7px;min-height:40px;padding:0 15px;border:1px solid #d6dce8;border-radius:10px;color:#344054;background:#fff;box-shadow:0 2px 6px rgba(16,24,40,.04);font-size:11px;font-weight:800;cursor:pointer;transition:.18s}.passkey-add-btn:hover{border-color:#a8c3fa;color:#2563eb;background:#f7faff;transform:translateY(-1px);box-shadow:0 7px 16px rgba(37,99,235,.1)}.passkey-unsupported{display:flex;align-items:center;gap:7px;color:#b42318;font-size:11px}.passkey-modal-backdrop{position:fixed;inset:0;z-index:3000;display:grid;place-items:center;padding:20px;background:rgba(15,23,42,.55);backdrop-filter:blur(7px)}.passkey-modal{position:relative;width:min(440px,100%);padding:28px;border:1px solid #e5eaf2;border-radius:22px;background:#fff;box-shadow:0 30px 80px rgba(15,23,42,.25)}.passkey-close{position:absolute;top:15px;right:15px;width:32px;height:32px;display:grid;place-items:center;border:1px solid #e8ebf0;border-radius:9px;color:#667085;background:#f8fafc;cursor:pointer}.passkey-modal-icon{width:56px;height:56px;display:grid;place-items:center;margin-bottom:13px;border:1px solid #ddd4ff;border-radius:16px;color:#7c3aed;background:#f7f3ff;box-shadow:0 8px 22px rgba(124,58,237,.1)}.passkey-eyebrow{color:#7c3aed;font-size:9px;font-weight:900;letter-spacing:.15em}.passkey-modal h3{margin:5px 0 0;color:#101828;font-size:23px;font-weight:850}.passkey-modal-desc{margin:7px 0 14px;color:#667085;font-size:11px;line-height:1.55}.passkey-benefits{display:flex;gap:8px;margin-bottom:18px}.passkey-benefits span{display:inline-flex;align-items:center;gap:5px;padding:6px 8px;border-radius:8px;color:#52627a;background:#f6f8fb;font-size:9px;font-weight:750}.passkey-modal label{display:block;margin-bottom:6px;color:#344054;font-size:11px;font-weight:800}.passkey-modal input{width:100%;height:44px;padding:0 12px;border:1px solid #dfe4ec;border-radius:10px;outline:0;color:#344054;background:#fff;font-size:11px}.passkey-modal input:focus{border-color:#8fb2f4;box-shadow:0 0 0 4px rgba(37,99,235,.08)}.passkey-modal small{display:block;margin-top:6px;color:#98a2b3;font-size:9px}.passkey-modal-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:18px}.passkey-cancel,.passkey-confirm{min-height:40px;padding:0 14px;border-radius:10px;font-size:11px;font-weight:800;cursor:pointer}.passkey-cancel{border:1px solid #dfe4ec;color:#475467;background:#fff}.passkey-confirm{display:inline-flex;align-items:center;gap:7px;border:0;color:#fff;background:#2563eb;box-shadow:0 7px 16px rgba(37,99,235,.18)}.passkey-confirm:disabled{opacity:.55;cursor:not-allowed}.passkey-modal-enter-active,.passkey-modal-leave-active{transition:.2s ease}.passkey-modal-enter-from,.passkey-modal-leave-to{opacity:0}.passkey-modal-enter-from .passkey-modal,.passkey-modal-leave-to .passkey-modal{transform:translateY(8px) scale(.98)}
</style>
