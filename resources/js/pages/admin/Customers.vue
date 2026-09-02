<script setup lang="ts">
import Users from './Users.vue';

defineProps<{ users: any }>();
</script>
<template><Users :users="users" role="customer" /></template>
