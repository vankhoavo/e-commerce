<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import InputError from '@/components/InputError.vue';

const open = ref(false);
</script>

<template>
    <section class="danger-account-card">
        <div class="danger-account-icon"><i class="bi bi-trash3-fill" /></div>
        <div class="danger-account-copy">
            <span class="danger-kicker">VÙNG NGUY HIỂM</span>
            <h2>Xóa tài khoản</h2>
            <p>Xóa vĩnh viễn tài khoản và toàn bộ dữ liệu liên quan. Hành động này không thể hoàn tác.</p>
        </div>
        <button type="button" class="btn btn-outline-danger danger-delete-btn" @click="open = true">
            <i class="bi bi-trash3 me-2" />Xóa tài khoản
        </button>
    </section>

    <div v-if="open" class="danger-modal-backdrop" @click.self="open = false">
        <div class="danger-modal" role="dialog" aria-modal="true" aria-labelledby="delete-account-title">
            <div class="danger-modal-icon"><i class="bi bi-exclamation-triangle-fill" /></div>
            <span class="danger-kicker">XÁC NHẬN XÓA TÀI KHOẢN</span>
            <h2 id="delete-account-title">Bạn chắc chắn muốn xóa tài khoản?</h2>
            <p>Toàn bộ thông tin cá nhân, dữ liệu và tài nguyên của tài khoản sẽ bị xóa vĩnh viễn. Vui lòng nhập mật khẩu hiện tại để xác nhận.</p>
            <Form v-bind="ProfileController.destroy.form()" reset-on-success :options="{ preserveScroll: true }" v-slot="{ errors, processing }">
                <label for="delete-password" class="form-label">Mật khẩu hiện tại</label>
                <input id="delete-password" name="password" type="password" class="form-control form-control-lg" placeholder="Nhập mật khẩu để xác nhận" required autocomplete="current-password" autofocus>
                <InputError :message="errors.password" class="mt-2" />
                <div class="danger-modal-actions">
                    <button type="button" class="btn btn-light btn-lg" @click="open = false" :disabled="processing">Hủy</button>
                    <button type="submit" class="btn btn-danger btn-lg" :disabled="processing">
                        <i class="bi bi-trash3 me-2" />{{ processing ? 'Đang xóa...' : 'Xác nhận xóa' }}
                    </button>
                </div>
            </Form>
        </div>
    </div>
</template>
