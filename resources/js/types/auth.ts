export type User = {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'client';
    phone: string | null;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
    isAdmin: boolean;
};

export type DemoAccount = {
    role: string;
    description: string;
    email: string;
    password: string;
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
