const assert = require('assert');
const { recommend } = require('../../public/js/pallet-recommendation');

function asMap(items) {
    return Object.fromEntries(items.map(item => [item.capacity, item.count]));
}

assert.deepStrictEqual(asMap(recommend(520)), { 260: 2 });
assert.deepStrictEqual(asMap(recommend(650)), { 260: 1, 390: 1 });
assert.deepStrictEqual(asMap(recommend(780)), { 390: 2 });
assert.deepStrictEqual(asMap(recommend(1040)), { 260: 1, 390: 2 });
assert.deepStrictEqual(recommend(500), []);

for (let quantity = 1; quantity <= 5000; quantity++) {
    const result = recommend(quantity);
    const total = result.reduce((sum, item) => sum + (item.capacity * item.count), 0);
    const palletCount = result.reduce((sum, item) => sum + item.count, 0);
    let minimum = Infinity;

    for (let count260 = 0; count260 <= quantity / 260; count260++) {
        for (let count325 = 0; count325 <= quantity / 325; count325++) {
            const remainder = quantity - (count260 * 260) - (count325 * 325);
            if (remainder >= 0 && remainder % 390 === 0) {
                minimum = Math.min(minimum, count260 + count325 + (remainder / 390));
            }
        }
    }

    if (minimum === Infinity) {
        assert.deepStrictEqual(result, [], `Não deveria recomendar para ${quantity}`);
    } else {
        assert.strictEqual(total, quantity, `Carga incorreta para ${quantity}`);
        assert.strictEqual(palletCount, minimum, `Quantidade de paletes incorreta para ${quantity}`);
    }
}

console.log('Pallet recommendation tests passed.');
