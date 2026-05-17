/** Horários de funcionamento do coworking (faixas de 1h). */
export const START_HOUR = 14;
export const END_HOUR = 22;

export const HOUR_SLOTS = Array.from({ length: END_HOUR - START_HOUR }, (_, i) => START_HOUR + i);
