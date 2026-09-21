/**
 * Client-side mirrors of server config, used for UX only. The API is the source of truth
 * and re-validates everything (see config/proposals.php).
 */
export const RATING = { min: 1, max: 10 } as const;

export const ATTACHMENT = {
    maxBytes: 4 * 1024 * 1024,
    mimeType: 'application/pdf',
} as const;

export const LIMITS = {
    titleMax: 255,
    descriptionMax: 5000,
    commentMax: 2000,
    tagsMax: 10,
    tagMin: 2,
    tagMax: 30,
} as const;
