export const SPACE_COMPUTER = 'computer';
export const SPACE_MEETING_ROOM = 'meeting_room';
export const SPACE_BOTH = 'both';

export const COMPUTER_IDS = [1, 2];

export const SPACE_OPTIONS = [
    {
        value: SPACE_COMPUTER,
        label: 'Uso de computador',
        description: 'Selecione o(s) computador(es) que irá utilizar (máx. 2 disponíveis).',
    },
    {
        value: SPACE_MEETING_ROOM,
        label: 'Sala para reunião / apresentação',
        description: 'Reserva exclusiva da sala de reunião.',
    },
    {
        value: SPACE_BOTH,
        label: 'Sala de reunião + computador(es)',
        description: 'Utilize a sala junto com um ou dois computadores.',
    },
];
