import type { ClientConfig, Resource } from '@/types/api';
import { http } from './http';

export async function fetchClientConfig(): Promise<ClientConfig> {
    const { data } = await http.get<Resource<ClientConfig>>('/config');

    return data.data;
}
