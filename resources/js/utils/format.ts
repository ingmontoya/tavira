export const formatCurrency = (amount: number | string | null | undefined): string => {
    const numAmount = amount ? (typeof amount === 'string' ? parseFloat(amount) : amount) : 0;
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })
        .format(numAmount)
        .replace(/COP\s*/, '$');
};

export const formatDate = (date: string | Date): string => {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    return new Intl.DateTimeFormat('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(dateObj);
};

export const formatDateTime = (date: string | Date): string => {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    return new Intl.DateTimeFormat('es-CO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(dateObj);
};

export const formatNumber = (num: number | string | null | undefined): string => {
    const numValue = num ? (typeof num === 'string' ? parseFloat(num) : num) : 0;
    return new Intl.NumberFormat('es-CO').format(numValue);
};

export const formatPercentage = (value: number): string => {
    return new Intl.NumberFormat('es-CO', {
        style: 'percent',
        minimumFractionDigits: 1,
        maximumFractionDigits: 2,
    }).format(value / 100);
};
