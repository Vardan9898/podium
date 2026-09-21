import type { CurrentUser, Resource, Role } from '@/types/api';
import { ensureCsrfCookie, http } from './http';

export interface Credentials {
    email: string;
    password: string;
    remember?: boolean;
}

export interface Registration {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    role: Role;
}

export async function fetchCurrentUser(): Promise<CurrentUser> {
    const { data } = await http.get<Resource<CurrentUser>>('/me');

    return data.data;
}

export async function login(credentials: Credentials): Promise<CurrentUser> {
    await ensureCsrfCookie();
    const { data } = await http.post<Resource<CurrentUser>>('/login', credentials);

    return data.data;
}

export async function register(payload: Registration): Promise<CurrentUser> {
    await ensureCsrfCookie();
    const { data } = await http.post<Resource<CurrentUser>>('/register', payload);

    return data.data;
}

export async function logout(): Promise<void> {
    await http.post('/logout');
}
