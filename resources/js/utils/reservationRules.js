/** Formata segundos como MM:SS ou HH:MM:SS */
export function formatCountdown(totalSeconds) {
    const seconds = Math.max(0, Math.floor(totalSeconds ?? 0));
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    if (h > 0) {
        return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    }
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
}

export function refreshRulesMeta(rules, { isAdmin = false } = {}) {
    if (!rules?.editable_until) {
        return rules;
    }
    const now = Date.now();
    const editableUntil = new Date(rules.editable_until).getTime();
    const cancelDeadline = new Date(rules.cancel_deadline).getTime();
    const inEditWindow = now < editableUntil;
    const cancelCountdownVisible = !inEditWindow && !isAdmin && !rules.admin_can_cancel;

    const editSecondsRemaining = inEditWindow
        ? Math.max(0, Math.floor((editableUntil - now) / 1000))
        : 0;

    let cancelSecondsRemaining = null;
    if (cancelCountdownVisible) {
        cancelSecondsRemaining = Math.max(0, Math.floor((cancelDeadline - now) / 1000));
    }

    const startsAt = new Date(rules.starts_at).getTime();
    const canCancel = isAdmin || rules.admin_can_cancel || (now < cancelDeadline && now < startsAt);

    let cancelBlockedReason = rules.cancel_blocked_reason;
    if (!canCancel && cancelCountdownVisible) {
        if (now >= startsAt) {
            cancelBlockedReason = 'Não é possível cancelar uma reserva que já começou ou terminou.';
        } else {
            cancelBlockedReason = `Cancelamento permitido apenas até ${rules.cancel_hours_before ?? 3} horas antes do início da reserva.`;
        }
    }

    return {
        ...rules,
        in_edit_window: inEditWindow,
        cancel_countdown_visible: cancelCountdownVisible,
        edit_seconds_remaining: editSecondsRemaining,
        cancel_seconds_remaining: cancelSecondsRemaining,
        can_edit: inEditWindow && editSecondsRemaining > 0 && now < startsAt,
        can_cancel: canCancel,
        cancel_blocked_reason: canCancel ? null : cancelBlockedReason,
    };
}
