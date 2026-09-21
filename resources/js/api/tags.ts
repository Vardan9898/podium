import type { Resource, Tag } from '@/types/api';
import { http } from './http';

export async function searchTags(search: string, signal?: AbortSignal): Promise<Tag[]> {
    const { data } = await http.get<Resource<Tag[]>>('/tags', { params: { search }, signal });

    return data.data;
}
