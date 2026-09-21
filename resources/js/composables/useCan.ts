import { useAuthStore } from '@/stores/auth';
import type { Permission } from '@/types/api';
import { computed, type ComputedRef } from 'vue';

/** UI visibility is driven by permissions, never role names. The API enforces the same rules. */
export function useCan(permission: Permission): ComputedRef<boolean> {
    const auth = useAuthStore();

    return computed(() => auth.can(permission));
}
