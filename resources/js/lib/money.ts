export function formatMoney(amountMinor: string, currency = 'TRY'): string {
    const amount = BigInt(amountMinor);
    const isNegative = amount < 0n;
    const absolute = isNegative ? -amount : amount;
    const whole = absolute / 100n;
    const formatter = new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });
    const parts = formatter.formatToParts(isNegative ? -whole : whole);

    if (isNegative && whole === 0n) {
        parts.unshift({ type: 'minusSign', value: '-' });
    }

    const lastIntegerPart = parts.reduce(
        (lastIndex, part, index) =>
            part.type === 'integer' || part.type === 'group'
                ? index
                : lastIndex,
        -1,
    );

    parts.splice(lastIntegerPart + 1, 0, {
        type: 'decimal',
        value: ',',
    });
    parts.splice(lastIntegerPart + 2, 0, {
        type: 'fraction',
        value: (absolute % 100n).toString().padStart(2, '0'),
    });

    return parts.map((part) => part.value).join('');
}
