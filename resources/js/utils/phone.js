/** Apenas dígitos; remove DDI 55 se presente. */
export function digitsOnly(value) {
    let digits = String(value).replace(/\D/g, '');
    if (digits.startsWith('55') && digits.length > 11) {
        digits = digits.slice(2);
    }
    return digits;
}

export function isValidBrazilMobile(value) {
    const digits = digitsOnly(value);
    if (digits.length !== 11) {
        return false;
    }
    const ddd = parseInt(digits.slice(0, 2), 10);
    return ddd >= 11 && ddd <= 99 && digits[2] === '9';
}

/** Máscara (99) 9999-9999 enquanto digita (ex.: 99999999999 → (99) 99999-9999). */
export function formatBrazilPhoneInput(value) {
    const digits = digitsOnly(value).slice(0, 11);
    if (digits.length <= 2) {
        return digits.length ? `(${digits}` : '';
    }
    if (digits.length <= 7) {
        return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
    }
    return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
}

/** Exibe telefone com máscara; se inválido, retorna o valor original. */
export function formatBrazilPhoneDisplay(value) {
    if (!value) {
        return '';
    }
    const formatted = formatBrazilPhoneInput(value);
    return formatted || String(value);
}

/** Link wa.me com DDI 55 (Brasil). Retorna null se não houver dígitos suficientes. */
export function whatsappUrl(phone) {
    const digits = digitsOnly(phone);
    if (digits.length < 10 || digits.length > 11) {
        return null;
    }
    return `https://wa.me/55${digits}`;
}
