import type { Paginated, Proposal, ProposalStatus, Resource, Review } from '@/types/api';
import { http } from './http';

export interface ProposalQuery {
    search?: string;
    tags?: string[];
    status?: ProposalStatus;
    page?: number;
    per_page?: number;
}

export interface NewProposal {
    title: string;
    description: string;
    tags: string[];
    attachment: File | null;
}

export async function listProposals(query: ProposalQuery, signal?: AbortSignal): Promise<Paginated<Proposal>> {
    const { data } = await http.get<Paginated<Proposal>>('/proposals', { params: query, signal });

    return data;
}

export async function getProposal(id: number, signal?: AbortSignal): Promise<Proposal> {
    const { data } = await http.get<Resource<Proposal>>(`/proposals/${id}`, { signal });

    return data.data;
}

export async function createProposal(payload: NewProposal, onProgress?: (percent: number) => void): Promise<Proposal> {
    const form = new FormData();
    form.append('title', payload.title);
    form.append('description', payload.description);
    payload.tags.forEach((tag) => form.append('tags[]', tag));

    if (payload.attachment) {
        form.append('attachment', payload.attachment);
    }

    const { data } = await http.post<Resource<Proposal>>('/proposals', form, {
        onUploadProgress: (event) => {
            if (onProgress && event.total) {
                onProgress(Math.round((event.loaded / event.total) * 100));
            }
        },
    });

    return data.data;
}

export async function changeStatus(id: number, status: ProposalStatus): Promise<Proposal> {
    const { data } = await http.patch<Resource<Proposal>>(`/proposals/${id}/status`, { status });

    return data.data;
}

export async function saveReview(id: number, review: { rating: number | null; comment: string }): Promise<Review> {
    const { data } = await http.put<Resource<Review>>(`/proposals/${id}/review`, review);

    return data.data;
}
