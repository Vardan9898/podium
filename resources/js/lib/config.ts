/**
 * Field length limits mirrored from the FormRequests, used for counters and `maxlength` only.
 * Anything configurable on the server (rating range, upload size, tag count) comes from /api/config.
 */
export const LIMITS = {
    searchMax: 255,
    titleMax: 255,
    descriptionMax: 5000,
    commentMax: 2000,
    tagMin: 2,
    tagMax: 30,
} as const;

export const PDF_MIME_TYPE = 'application/pdf';
