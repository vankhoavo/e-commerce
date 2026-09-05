<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';

const page = usePage();
const role = computed(() => String((page.props.auth as any)?.user?.role?.value ?? (page.props.auth as any)?.user?.role ?? '').toLowerCase());
const canRequestDeletion = computed(() => role.value === 'customer');
const open = ref(false);
const submitted = ref(false);
</script>

<template>
    <section v-if="canRequestDeletion" class="danger-account-card">
        <div class="danger-account-icon"><i class="bi bi-trash3-fill" /></div>
        <div class="danger-account-copy"><span class="danger-kicker">YÊU CẦU XÓA TÀI KHOẢN</span><h2>Xóa tài khoản</h2><p>Gửi yêu cầu để bộ phận quản trị kiểm tra và xử lý. Tài khoản sẽ không bị xóa ngay khi bạn gửi yêu cầu.</p></div>
        <button type="button" class="btn btn-outline-danger danger-delete-btn" @click="open = true" :disabled="submitted"><i class="bi bi-send me-2" />{{ submitted ? 'Đã gửi yêu cầu' : 'Gửi yêu cầu' }}</button>
    </section>

    <div v-if="open" class="danger-modal-backdrop" @click.self="open = false">
        <div class="danger-modal" role="dialog" aria-modal="true" aria-labelledby="delete-account-title">
            <div class="danger-modal-icon"><i class="bi bi-exclamation-triangle-fill" /></div><span class="danger-kicker">XÁC NHẬN YÊU CẦU</span><h2 id="delete-account-title">Gửi yêu cầu xóa tài khoản?</h2><p>Quản trị viên sẽ tiếp nhận yêu cầu. Bạn có thể ghi rõ lý do để quá trình xử lý nhanh hơn.</p>
            <Form action="/account/deletion-request" method="post" :options="{ preserveScroll: true }" v-slot="{ errors, processing }" @success="submitted = true; open = false">
                <label for="delete-reason" class="form-label">Lý do (không bắt buộc)</label><textarea id="delete-reason" name="reason" class="form-control form-control-lg" rows="4" maxlength="2000" placeholder="Ví dụ: Tôi không còn nhu cầu sử dụng tài khoản..."></textarea><InputError :message="errors.reason" class="mt-2" />
                <div class="danger-modal-actions"><button type="button" class="btn btn-light btn-lg" @click="open = false" :disabled="processing">Hủy</button><button type="submit" class="btn btn-danger btn-lg" :disabled="processing"><i class="bi bi-send me-2" />{{ processing ? 'Đang gửi...' : 'Gửi yêu cầu' }}</button></div>
            </Form>
        </div>
    </div>
</template>
