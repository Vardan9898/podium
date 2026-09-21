<script setup lang="ts">
import AppButton from '@/components/ui/AppButton.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import { HOME } from '@/router/guards';
</script>

<template>
    <EmptyState title="This page isn't on the programme." body="The link may be broken, or the page may have moved.">
        <AppButton :to="HOME">Back to proposals</AppButton>
    </EmptyState>
</template>
