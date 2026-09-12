(function (root, factory) {
    const api = factory();

    if (typeof module === 'object' && module.exports) {
        module.exports = api;
    }

    root.PalletRecommendation = api;
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    const capacities = [260, 325, 390];

    function recommend(quantity) {
        const total = Number(quantity);
        if (!Number.isInteger(total) || total <= 0 || total % 65 !== 0) {
            return [];
        }

        const normalizedTotal = total / 65;
        const minimumPallets = Math.ceil(normalizedTotal / 6);
        const maximumPallets = Math.floor(normalizedTotal / 4);

        for (let palletCount = minimumPallets; palletCount <= maximumPallets; palletCount++) {
            const remainder = normalizedTotal - (4 * palletCount);
            const minimum390 = Math.max(0, remainder - palletCount);
            const maximum390 = Math.floor(remainder / 2);

            if (minimum390 > maximum390) continue;

            const count390 = maximum390;
            const count325 = remainder - (2 * count390);
            const count260 = palletCount - count325 - count390;

            return [
                { capacity: 390, count: count390 },
                { capacity: 325, count: count325 },
                { capacity: 260, count: count260 }
            ].filter(item => item.count > 0);
        }

        return [];
    }

    return { capacities, recommend };
}));
