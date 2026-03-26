const PHONE_CHARACTERS_REGEX = /^\+?[\d\s\-()]+$/;
const MIN_PHONE_DIGITS = 7;
const MAX_PHONE_DIGITS = 15;

export const validatePhone = (phone: string): boolean => {
    const trimmed = phone.trim();

    if (!trimmed) {
        return false;
    }

    if (!PHONE_CHARACTERS_REGEX.test(trimmed)) {
        return false;
    }

    const digitsOnly = trimmed.replace(/\D/g, '');

    return digitsOnly.length >= MIN_PHONE_DIGITS && digitsOnly.length <= MAX_PHONE_DIGITS;
};
